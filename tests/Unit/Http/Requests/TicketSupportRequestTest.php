<?php

use App\Http\Requests\Api\V1\TicketSupportRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

it('authorizes the request', function () {
    $request = new TicketSupportRequest;

    expect($request->authorize())->toBeTrue();
});

it('has correct validation rules', function () {
    $request = new TicketSupportRequest;
    $rules = $request->rules();

    expect($rules)
        ->toHaveKey('operation_type')
        ->toHaveKey('operation_id')
        ->toHaveKey('title')
        ->toHaveKey('message');
});

it('allows ticket without operation fields', function () {
    $request = new TicketSupportRequest;
    $validator = Validator::make([
        'title' => 'Test',
        'message' => 'Test message',
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});

it('validates operation_type must be valid', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'invalid_type',
        'operation_id' => Str::uuid()->toString(),
        'title' => 'Test',
        'message' => 'Test message',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('operation_type'))->toBeTrue();
});

it('validates operation_type accepts order', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'order',
        'operation_id' => Str::uuid()->toString(),
        'title' => 'Test',
        'message' => 'Test message',
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});

it('validates operation_id minimum length', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'order',
        'operation_id' => 'short-id',
        'title' => 'Test',
        'message' => 'Test message',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('operation_id'))->toBeTrue();
});

it('validates title is required', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'order',
        'operation_id' => Str::uuid()->toString(),
        'message' => 'Test message',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('title'))->toBeTrue();
});

it('validates title max length', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'order',
        'operation_id' => Str::uuid()->toString(),
        'title' => str_repeat('a', 256),
        'message' => 'Test message',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('title'))->toBeTrue();
});

it('validates message is required', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'order',
        'operation_id' => Str::uuid()->toString(),
        'title' => 'Test',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('message'))->toBeTrue();
});

it('passes validation with valid data', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'order',
        'operation_id' => Str::uuid()->toString(),
        'title' => 'Test Ticket',
        'message' => 'This is a test message',
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});
