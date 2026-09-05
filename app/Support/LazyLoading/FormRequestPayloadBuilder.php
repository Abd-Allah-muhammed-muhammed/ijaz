<?php

namespace App\Support\LazyLoading;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Illuminate\Validation\Rules\In;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Builds minimally-valid request payloads from a route's FormRequest rules()
 * so write-route sweeps reach the response layer instead of stopping at 422.
 */
final class FormRequestPayloadBuilder
{
    private const SKIP_FIELD = '__skip__';

    /**
     * @param  array<string, int|string>  $parameterBag
     * @return array{payload: array<string, mixed>}|array{skip: string}
     */
    public function build(Route $route, array $parameterBag, string $method = 'POST', ?string $resolvedUri = null): array
    {
        $formRequestClass = $this->resolveFormRequestClass($route);

        if ($formRequestClass === null) {
            return ['skip' => 'no FormRequest on controller action'];
        }

        try {
            $rules = $this->invokeRules($formRequestClass, $route, $parameterBag, $method, $resolvedUri);
        } catch (\Throwable $e) {
            return ['skip' => 'rules() failed: '.$e->getMessage()];
        }

        if ($rules === []) {
            return ['payload' => []];
        }

        $payload = [];
        foreach ($rules as $field => $ruleSet) {
            if (str_contains((string) $field, '*')) {
                continue;
            }

            $normalized = $this->normalizeRules($ruleSet);
            if ($this->shouldOmit($normalized)) {
                continue;
            }

            $value = $this->valueFor($field, $normalized, $parameterBag);
            if ($value === self::SKIP_FIELD) {
                return ['skip' => "cannot synthesize field [{$field}]"];
            }

            Arr::set($payload, $field, $value);
        }

        try {
            $validator = Validator::make($payload, $rules);
            if ($validator->fails()) {
                return [
                    'skip' => 'synthetic payload failed validation: '.
                        implode('; ', $validator->errors()->all()),
                ];
            }
        } catch (\Throwable $e) {
            return ['skip' => 'validator threw: '.$e->getMessage()];
        }

        return ['payload' => $payload];
    }

    /**
     * @return class-string<FormRequest>|null
     */
    public function resolveFormRequestClass(Route $route): ?string
    {
        $action = $route->getAction('controller');
        if (! is_string($action) || ! str_contains($action, '@')) {
            if (is_string($action) && class_exists($action)) {
                return $this->formRequestFromMethod($action, '__invoke');
            }

            return null;
        }

        [$class, $method] = explode('@', $action, 2);

        return $this->formRequestFromMethod($class, $method);
    }

    /**
     * @param  class-string  $class
     * @return class-string<FormRequest>|null
     */
    private function formRequestFromMethod(string $class, string $method): ?string
    {
        if (! class_exists($class) || ! method_exists($class, $method)) {
            return null;
        }

        $reflection = new ReflectionMethod($class, $method);
        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();
            if (is_subclass_of($typeName, FormRequest::class)) {
                return $typeName;
            }
        }

        return null;
    }

    /**
     * @param  class-string<FormRequest>  $formRequestClass
     * @param  array<string, int|string>  $parameterBag
     * @return array<string, mixed>
     */
    private function invokeRules(
        string $formRequestClass,
        Route $route,
        array $parameterBag,
        string $method = 'POST',
        ?string $resolvedUri = null,
    ): array {
        $uri = $resolvedUri ?? '/'.ltrim($route->uri(), '/');
        $request = Request::create($uri, $method);

        try {
            $route->bind($request);
        } catch (\Throwable) {
            // continue with manual params
        }

        foreach ($parameterBag as $key => $value) {
            try {
                if (in_array($key, $route->parameterNames(), true)) {
                    $route->setParameter($key, $value);
                }
            } catch (\Throwable) {
                // unbound
            }
        }

        /** @var FormRequest $instance */
        $instance = $formRequestClass::createFromBase($request);
        $instance->setContainer(app());
        $instance->setRedirector(app('redirect'));
        $instance->setRouteResolver(static fn () => $route);
        $instance->setUserResolver(static fn () => auth()->user());

        $rules = $instance->rules();

        return is_array($rules) ? $rules : [];
    }

    /**
     * @return list<mixed>
     */
    private function normalizeRules(mixed $ruleSet): array
    {
        if (is_string($ruleSet)) {
            return explode('|', $ruleSet);
        }

        if (is_array($ruleSet)) {
            return array_values($ruleSet);
        }

        return [$ruleSet];
    }

    /**
     * @param  list<mixed>  $rules
     */
    private function shouldOmit(array $rules): bool
    {
        $names = $this->ruleNames($rules);

        if (in_array('required', $names, true) || in_array('required_if', $names, true)) {
            return false;
        }

        return in_array('nullable', $names, true)
            || in_array('sometimes', $names, true)
            || ! in_array('required', $names, true);
    }

    /**
     * @param  list<mixed>  $rules
     * @param  array<string, int|string>  $parameterBag
     */
    private function valueFor(string $field, array $rules, array $parameterBag): mixed
    {
        $names = $this->ruleNames($rules);
        $fieldKey = str_replace(['_id', '.'], ['', '_'], $field);

        foreach ($parameterBag as $param => $value) {
            if ($field === $param || $field === $param.'_id' || $fieldKey === $param) {
                return $value;
            }
        }

        foreach ($rules as $rule) {
            if ($rule instanceof EnumRule) {
                $prop = (new ReflectionClass($rule))->getProperty('type');
                $enumClass = $prop->getValue($rule);
                if (is_string($enumClass) && enum_exists($enumClass)) {
                    $cases = $enumClass::cases();

                    return $cases[0]->value ?? $cases[0]->name;
                }
            }

            if ($rule instanceof In) {
                $prop = (new ReflectionClass($rule))->getProperty('values');
                $values = $prop->getValue($rule);

                return is_array($values) && $values !== [] ? $values[0] : self::SKIP_FIELD;
            }

            if (is_string($rule) && str_starts_with($rule, 'in:')) {
                $options = explode(',', substr($rule, 3));

                return $options[0] ?? self::SKIP_FIELD;
            }

            if (is_string($rule) && str_starts_with($rule, 'exists:')) {
                $parts = explode(',', substr($rule, 7));
                $column = $parts[1] ?? 'id';
                if ($column === 'id' && isset($parameterBag[str_replace('_id', '', $field)])) {
                    return $parameterBag[str_replace('_id', '', $field)];
                }
                foreach ($parameterBag as $value) {
                    if (is_int($value) || ctype_digit((string) $value)) {
                        return $value;
                    }
                }

                return self::SKIP_FIELD;
            }
        }

        if (in_array('boolean', $names, true) || in_array('accepted', $names, true)) {
            return true;
        }

        if (in_array('email', $names, true)) {
            return 'sweep@example.test';
        }

        if (in_array('integer', $names, true) || in_array('numeric', $names, true)) {
            return 1;
        }

        if (in_array('array', $names, true)) {
            return [];
        }

        if (in_array('file', $names, true) || in_array('image', $names, true)) {
            return UploadedFile::fake()->image('sweep.jpg');
        }

        if (in_array('date', $names, true) || in_array('date_format:Y-m-d', $names, true)) {
            return now()->toDateString();
        }

        if (str_contains($field, 'password')) {
            return 'Password1!';
        }

        if (str_contains($field, 'phone')) {
            return '0501234567';
        }

        if (str_contains($field, 'email')) {
            return 'sweep@example.test';
        }

        return 'sweep-value';
    }

    /**
     * @param  list<mixed>  $rules
     * @return list<string>
     */
    private function ruleNames(array $rules): array
    {
        $names = [];
        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $names[] = explode(':', $rule)[0];
            } elseif (is_object($rule)) {
                $names[] = class_basename($rule);
            }
        }

        return $names;
    }
}
