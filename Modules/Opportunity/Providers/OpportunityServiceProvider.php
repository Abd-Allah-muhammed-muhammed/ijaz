<?php

namespace Modules\Opportunity\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Opportunity\Console\Commands\ExpireOpportunitiesCommand;
use Modules\Opportunity\Contracts\Repositories\OpportunityCommentRepositoryInterface;
use Modules\Opportunity\Contracts\Repositories\OpportunityOfferRepositoryInterface;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\Handlers\OpportunityChatHandler;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Policies\OpportunityCommentPolicy;
use Modules\Opportunity\Policies\OpportunityOfferPolicy;
use Modules\Opportunity\Policies\OpportunityPolicy;
use Modules\Opportunity\Repositories\OpportunityCommentRepository;
use Modules\Opportunity\Repositories\OpportunityOfferRepository;
use Modules\Opportunity\Repositories\OpportunityRepository;
use Nwidart\Modules\Support\ModuleServiceProvider;

class OpportunityServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Opportunity';

    protected string $nameLower = 'opportunity';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(OpportunityOffer::class, OpportunityOfferPolicy::class);
        Gate::policy(OpportunityComment::class, OpportunityCommentPolicy::class);

        $this->app->make(ChatTypeRegistry::class)
            ->register(ChatTypeEnum::Opportunity, new OpportunityChatHandler);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireOpportunitiesCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        parent::register();

        $this->app->bind(OpportunityRepositoryInterface::class, OpportunityRepository::class);
        $this->app->bind(OpportunityOfferRepositoryInterface::class, OpportunityOfferRepository::class);
        $this->app->bind(OpportunityCommentRepositoryInterface::class, OpportunityCommentRepository::class);
    }
}
