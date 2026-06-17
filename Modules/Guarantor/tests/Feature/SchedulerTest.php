<?php

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Jobs\AutoReleaseInstallmentJob;
use Modules\Guarantor\Jobs\NotifyOverdueInstallmentJob;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\InstallmentDueNotification;
use Modules\Guarantor\Notifications\InstallmentOverdueNotification;

test('check-overdue command dispatches jobs for overdue installments', function () {
    Queue::fake();

    $request = GuarantorRequest::factory()->inProgress()->create();
    GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'due_date' => now()->subDay(),
        'status' => InstallmentStatusEnum::Pending,
    ]);

    $this->artisan('guarantor:check-overdue')
        ->assertSuccessful();

    Queue::assertPushed(NotifyOverdueInstallmentJob::class, 1);
    Queue::assertPushedOn('guarantor', NotifyOverdueInstallmentJob::class);
});

test('check-overdue command skips non-overdue installments', function () {
    Queue::fake();

    $request = GuarantorRequest::factory()->inProgress()->create();
    GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'due_date' => now()->addDays(10),
        'status' => InstallmentStatusEnum::Pending,
    ]);

    $this->artisan('guarantor:check-overdue')
        ->assertSuccessful();

    Queue::assertNotPushed(NotifyOverdueInstallmentJob::class);
});

test('check-overdue command skips ended guarantor requests', function () {
    Queue::fake();

    $request = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::Ended]);
    GuarantorInstallment::factory()->for($request, 'guarantorRequest')->overdue()->create([
        'order' => 1,
    ]);

    $this->artisan('guarantor:check-overdue')
        ->assertSuccessful();

    Queue::assertNotPushed(NotifyOverdueInstallmentJob::class);
});

test('NotifyOverdueInstallmentJob notifies counterparty on day 1', function () {
    Notification::fake();

    $request = GuarantorRequest::factory()->inProgress()->create();
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'due_date' => now()->subDay(),
        'status' => InstallmentStatusEnum::Pending,
    ]);

    $counterparty = $request->counterparty;

    (new NotifyOverdueInstallmentJob($installment))
        ->handle(app(LogGuarantorStatusHistoryAction::class));

    Notification::assertSentTo($counterparty, InstallmentDueNotification::class);
    Notification::assertNotSentTo($request->requester, InstallmentOverdueNotification::class);
});

test('NotifyOverdueInstallmentJob notifies both parties on day 3', function () {
    Notification::fake();

    $request = GuarantorRequest::factory()->inProgress()->create();
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'due_date' => now()->subDays(3),
        'status' => InstallmentStatusEnum::Pending,
    ]);

    $requester = $request->requester;
    $counterparty = $request->counterparty;

    (new NotifyOverdueInstallmentJob($installment))
        ->handle(app(LogGuarantorStatusHistoryAction::class));

    Notification::assertSentTo($requester, InstallmentOverdueNotification::class);
    Notification::assertSentTo($counterparty, InstallmentOverdueNotification::class);
});

test('NotifyOverdueInstallmentJob sets status to overdue on day 3', function () {
    Notification::fake();

    $request = GuarantorRequest::factory()->inProgress()->create();
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'due_date' => now()->subDays(3),
        'status' => InstallmentStatusEnum::Pending,
    ]);

    (new NotifyOverdueInstallmentJob($installment))
        ->handle(app(LogGuarantorStatusHistoryAction::class));

    $request->refresh();

    expect($request->status)->toBe(GuarantorStatusEnum::Overdue)
        ->and($request->overdue_at)->not->toBeNull();
});

test('NotifyOverdueInstallmentJob dispatches AutoReleaseInstallmentJob on day 14', function () {
    Queue::fake();

    $request = GuarantorRequest::factory()->inProgress()->create();
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'due_date' => now()->subDays(14),
        'status' => InstallmentStatusEnum::Pending,
    ]);

    (new NotifyOverdueInstallmentJob($installment))
        ->handle(app(LogGuarantorStatusHistoryAction::class));

    Queue::assertPushed(AutoReleaseInstallmentJob::class, 1);
    Queue::assertPushedOn('guarantor', AutoReleaseInstallmentJob::class);
});

test('AutoReleaseInstallmentJob releases paid installment', function () {
    Notification::fake();

    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $requester = $guarantorRequest->requester;
    $requester->wallet->update(['pending_credit' => 500, 'balance' => 0]);

    (new AutoReleaseInstallmentJob($installment))
        ->handle(app(ReleaseInstallmentAction::class));

    $installment->refresh();
    $requester->wallet->refresh();

    expect($installment->status)->toBe(InstallmentStatusEnum::Released)
        ->and((float) $requester->wallet->pending_credit)->toBe(0.0)
        ->and((float) $requester->wallet->balance)->toBeGreaterThan(0);
});

test('AutoReleaseInstallmentJob skips already released installment', function () {
    Notification::fake();

    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Released,
        'released_at' => now(),
    ]);

    $requester = $guarantorRequest->requester;
    $requester->wallet->update(['pending_credit' => 500, 'balance' => 100]);

    (new AutoReleaseInstallmentJob($installment))
        ->handle(app(ReleaseInstallmentAction::class));

    $requester->wallet->refresh();

    expect((float) $requester->wallet->pending_credit)->toBe(500.0)
        ->and((float) $requester->wallet->balance)->toBe(100.0);
});
