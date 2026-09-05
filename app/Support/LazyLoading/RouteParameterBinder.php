<?php

namespace App\Support\LazyLoading;

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Modules\Catalog\Models\Bank;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\Models\PropertyCategory;
use Modules\Catalog\Models\PropertyType;
use Modules\Catalog\Models\Specialization;
use Modules\Chat\Models\Conversation;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Cms\Models\Banner;
use Modules\Cms\Models\Page;
use Modules\Cms\Models\Question;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Jobs\Models\JobOffer;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\ProviderType;
use Modules\Marketplace\Models\Skill;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\Models\Payment;
use Modules\Payout\Models\PayoutRequest;
use Modules\Support\Models\TicketSupport;
use Modules\Wallet\Models\WithdrawRequest;
use ReflectionMethod;
use ReflectionNamedType;
use Spatie\Permission\Models\Role;

/**
 * Resolves route parameters to Eloquent models the way ImplicitRouteBinding would,
 * so FormRequest rules() that call `$this->route('x')->id` / `$this->x->id` work.
 */
final class RouteParameterBinder
{
    /**
     * Explicit param-name → model map (covers snake_case URI params).
     *
     * @var array<string, class-string<Model>>
     */
    public const PARAM_MODELS = [
        'admin' => Admin::class,
        'provider' => Provider::class,
        'user' => User::class,
        'region' => Region::class,
        'city' => City::class,
        'nationality' => Nationality::class,
        'category' => Category::class,
        'skill' => Skill::class,
        'providerType' => ProviderType::class,
        'provider_type' => ProviderType::class,
        'propertyCategory' => PropertyCategory::class,
        'property_category' => PropertyCategory::class,
        'propertyType' => PropertyType::class,
        'property_type' => PropertyType::class,
        'carCategory' => CarCategory::class,
        'car_category' => CarCategory::class,
        'carBrand' => CarBrand::class,
        'car_brand' => CarBrand::class,
        'carType' => CarType::class,
        'car_type' => CarType::class,
        'deviceCategory' => DeviceCategory::class,
        'device_category' => DeviceCategory::class,
        'electronicBrand' => ElectronicBrand::class,
        'electronic_brand' => ElectronicBrand::class,
        'specialization' => Specialization::class,
        'bank' => Bank::class,
        'page' => Page::class,
        'question' => Question::class,
        'banner' => Banner::class,
        'order' => Order::class,
        'orderOffer' => OrderOffer::class,
        'offer' => OrderOffer::class,
        'opportunity' => Opportunity::class,
        'guarantorRequest' => GuarantorRequest::class,
        'installment' => GuarantorInstallment::class,
        'carAdvisement' => CarAdvisement::class,
        'car_advisement' => CarAdvisement::class,
        'propertyAdvisement' => PropertyAdvisement::class,
        'property_advisement' => PropertyAdvisement::class,
        'electronicAdvisement' => ElectronicAdvisement::class,
        'electronic_advisement' => ElectronicAdvisement::class,
        'instituteAdvisement' => InstituteAdvisement::class,
        'institute_advisement' => InstituteAdvisement::class,
        'ticketSupport' => TicketSupport::class,
        'ticket' => TicketSupport::class,
        'withdrawRequest' => WithdrawRequest::class,
        'withdraw_request' => WithdrawRequest::class,
        'payment' => Payment::class,
        'payoutRequest' => PayoutRequest::class,
        'role' => Role::class,
        'conversation' => Conversation::class,
        'job' => JobOffer::class,
        'opportunityOffer' => OpportunityOffer::class,
    ];

    /**
     * @param  array<string, int|string|Model>  $parameterMap
     */
    public function bind(Route $route, Request $request, array $parameterMap): void
    {
        try {
            $route->bind($request);
        } catch (\Throwable) {
            // Compiled pattern may not match; still inject parameters below.
        }

        $fromSignature = $this->modelsFromControllerSignature($route);

        foreach ($route->parameterNames() as $name) {
            if (! array_key_exists($name, $parameterMap)) {
                continue;
            }

            $raw = $parameterMap[$name];

            if ($raw instanceof Model) {
                $route->setParameter($name, $raw);

                continue;
            }

            $class = $fromSignature[$name]
                ?? self::PARAM_MODELS[$name]
                ?? null;

            if (is_string($class) && is_subclass_of($class, Model::class)) {
                $model = $class::query()->find($raw);
                $route->setParameter($name, $model ?? $raw);

                continue;
            }

            $route->setParameter($name, $raw);
        }
    }

    /**
     * @return array<string, class-string<Model>>
     */
    private function modelsFromControllerSignature(Route $route): array
    {
        $action = $route->getAction('controller');
        if (! is_string($action)) {
            return [];
        }

        if (str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);
        } else {
            $class = $action;
            $method = '__invoke';
        }

        if (! class_exists($class) || ! method_exists($class, $method)) {
            return [];
        }

        $map = [];
        $reflection = new ReflectionMethod($class, $method);
        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();
            if (is_subclass_of($typeName, Model::class)) {
                $map[$parameter->getName()] = $typeName;
            }
        }

        return $map;
    }
}
