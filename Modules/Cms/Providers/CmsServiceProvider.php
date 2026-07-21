<?php

namespace Modules\Cms\Providers;

use Modules\Cms\Contracts\Repositories\BannerRepositoryInterface;
use Modules\Cms\Contracts\Repositories\MessageRepositoryInterface;
use Modules\Cms\Contracts\Repositories\PageRepositoryInterface;
use Modules\Cms\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Cms\Repositories\BannerRepository;
use Modules\Cms\Repositories\MessageRepository;
use Modules\Cms\Repositories\PageRepository;
use Modules\Cms\Repositories\QuestionRepository;
use Nwidart\Modules\Support\ModuleServiceProvider;

class CmsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Cms';

    protected string $nameLower = 'cms';

    public function register(): void
    {
        parent::register();

        $this->app->bind(BannerRepositoryInterface::class, BannerRepository::class);
        $this->app->bind(PageRepositoryInterface::class, PageRepository::class);
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, MessageRepository::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
