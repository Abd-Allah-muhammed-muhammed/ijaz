<?php

use App\Http\Requests\Api\V1\TicketSupportRequest;
use Illuminate\Support\Facades\Validator;

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

it('validates operation_type is required', function () {
    $request = new TicketSupportRequest;
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('operation_type'))->toBeTrue();
});

it('validates operation_type must be valid', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'invalid_type',
        'operation_id' => 1,
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
        'operation_id' => 1,
        'title' => 'Test',
        'message' => 'Test message',
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});

it('validates operation_type accepts guarantee_request', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'guarantee_request',
        'operation_id' => 1,
        'title' => 'Test',
        'message' => 'Test message',
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});

it('validates operation_id is required', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'order',
        'title' => 'Test',
        'message' => 'Test message',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('operation_id'))->toBeTrue();
});

it('validates operation_id must be integer', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'order',
        'operation_id' => 'not_an_integer',
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
        'operation_id' => 1,
        'message' => 'Test message',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('title'))->toBeTrue();
});

it('validates title max length', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'order',
        'operation_id' => 1,
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
        'operation_id' => 1,
        'title' => 'Test',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('message'))->toBeTrue();
});

it('passes validation with valid data', function () {
    $request = new TicketSupportRequest;

    $validator = Validator::make([
        'operation_type' => 'order',
        'operation_id' => 1,
        'title' => 'Test Ticket',
        'message' => 'This is a test message',
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});
