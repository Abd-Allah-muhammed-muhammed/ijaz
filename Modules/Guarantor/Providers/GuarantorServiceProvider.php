<?php

namespace Modules\Guarantor\Providers;

use App\Models\Conversation;
use Illuminate\Support\Facades\Gate;
use Modules\Guarantor\Console\Commands\CheckOverdueInstallmentsCommand;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\StatusHistoryRepositoryInterface;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Policies\ConversationPolicy;
use Modules\Guarantor\Policies\GuarantorPolicy;
use Modules\Guarantor\Policies\InstallmentPolicy;
use Modules\Guarantor\Repositories\GuarantorRepository;
use Modules\Guarantor\Repositories\InstallmentRepository;
use Modules\Guarantor\Repositories\StatusHistoryRepository;
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
        // GuarantorService, GuarantorInstallmentService, GuarantorChatService
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(GuarantorRequest::class, GuarantorPolicy::class);
        Gate::policy(GuarantorInstallment::class, InstallmentPolicy::class);

        // Opportunity also registers Conversation::class; defer so participant-only
        // policy wins for both Opportunity and Guarantor conversations.
        $this->app->booted(static function (): void {
            Gate::policy(Conversation::class, ConversationPolicy::class);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckOverdueInstallmentsCommand::class,
            ]);
        }
    }
}
