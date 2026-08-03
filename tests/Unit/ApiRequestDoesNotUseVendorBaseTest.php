<?php

use App\Http\Requests\ApiRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Wallet\Http\Requests\Provider\WithdrawRequestRequest;
use Modules\Wallet\Http\Requests\StoreWithdrawRequest;

test('application ApiRequest extends FormRequest and uses correctly cased HasApiResponse', function () {
    expect(ApiRequest::class)
        ->toExtend(FormRequest::class)
        ->toUseTrait(HasApiResponse::class);

    $uses = array_values(array_filter(
        file(base_path('app/Http/Requests/ApiRequest.php')),
        fn (string $line): bool => str_contains($line, 'use MMAE\\'),
    ));

    expect($uses)->toContain("use MMAE\\ApiResponse\\Traits\\HasApiResponse;\n")
        ->and($uses)->not->toContain("use MMAE\\Apiresponse\\Traits\\HasApiResponse;\n");
});

test('no app or module FormRequest imports the buggy vendor ApiRequest', function () {
    $needle = 'MMAE\\ApiResponse\\Request\\ApiRequest';
    $hits = [];

    foreach ([base_path('app'), base_path('Modules')] as $root) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();

            if (str_ends_with(str_replace('\\', '/', $path), 'app/Http/Requests/ApiRequest.php')) {
                continue;
            }

            $contents = file_get_contents($path);

            if (str_contains($contents, $needle) || str_contains($contents, 'MMAE\\Apiresponse\\Request\\ApiRequest')) {
                $hits[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            }
        }
    }

    expect($hits)->toBeEmpty();
});

test('ApiRequest failedValidation returns the standard JSON envelope', function () {
    $request = new class extends ApiRequest
    {
        public function authorize(): bool
        {
            return true;
        }

        public function rules(): array
        {
            return ['amount' => ['required', 'numeric']];
        }
    };

    $validator = Validator::make([], $request->rules());

    try {
        $request->failedValidation($validator);
        $this->fail('Expected HttpResponseException was not thrown.');
    } catch (HttpResponseException $exception) {
        $response = $exception->getResponse();

        expect($response->getStatusCode())->toBe(422)
            ->and($response->getData(true))->toMatchArray([
                'success' => false,
                'message' => 'Validation Failed',
            ])
            ->and($response->getData(true)['errors'])->toHaveKey('amount');
    }
});

test('wallet withdraw FormRequests extend the application ApiRequest base', function () {
    expect(WithdrawRequestRequest::class)->toExtend(ApiRequest::class)
        ->and(StoreWithdrawRequest::class)->toExtend(ApiRequest::class);
});
