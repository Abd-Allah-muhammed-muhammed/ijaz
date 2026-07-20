<?php

namespace Modules\Guarantor\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Guarantor\Console\Commands\CheckOverdueInstallmentsCommand;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\StatusHistoryRepositoryInterface;
use Modules\Guarantor\Handlers\GuarantorChatHandler;
use Modules\Guarantor\Listeners\HandleGuarantorPaymentCompleted;
use Modules\Guarantor\Listeners\HandleGuarantorPaymentFailed;
use Modules\Guarantor\Listeners\NotifyGuarantorPaymentCompleted;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Policies\GuarantorPolicy;
use Modules\Guarantor\Policies\InstallmentPolicy;
use Modules\Guarantor\Repositories\GuarantorRepository;
use Modules\Guarantor\Repositories\InstallmentRepository;
use Modules\Guarantor\Repositories\StatusHistoryRepository;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Nwidart\Modules\Support\ModuleServiceProvider;

class GuarantorServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Guarantor';

    protected string $nameLower = 'guarantor';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(
            GuarantorRepositoryInterface::class,
            GuarantorRepository::class,
        );

        $this->app->bind(
            InstallmentRepositoryInterface::class,
            InstallmentRepository::class,
        );

        $this->app->bind(
            StatusHistoryRepositoryInterface::class,
            StatusHistoryRepository::class,
        );

        // Services are resolved via constructor injection:
        // GuarantorService, GuarantorInstallmentService, GuarantorChatService,
        // GuarantorDashboardService
        // Dashboard actions: AdminApproveGuarantorAction, AdminRejectGuarantorAction,
        // AdminCancelGuarantorAction
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(GuarantorRequest::class, GuarantorPolicy::class);
        Gate::policy(GuarantorInstallment::class, InstallmentPolicy::class);

        Event::listen(PaymentCompleted::class, HandleGuarantorPaymentCompleted::class);
        Event::listen(PaymentCompleted::class, NotifyGuarantorPaymentCompleted::class);
        Event::listen(PaymentFailed::class, HandleGuarantorPaymentFailed::class);

        $this->app->make(ChatTypeRegistry::class)
            ->register(ChatTypeEnum::Guarantor, new GuarantorChatHandler);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckOverdueInstallmentsCommand::class,
            ]);
        }
    }
}
