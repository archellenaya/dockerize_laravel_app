<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\BookmarkRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\SourceRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\EloquentArticleRepository;
use App\Repositories\EloquentBookmarkRepository;
use App\Repositories\EloquentCategoryRepository;
use App\Repositories\EloquentSourceRepository;
use App\Repositories\EloquentUserRepository;
use App\Services\ArticleImportService;
use App\Services\ArticleService;
use App\Services\BookmarkService;
use App\Services\Contracts\ArticleImportServiceInterface;
use App\Services\Contracts\ArticleServiceInterface;
use App\Services\Contracts\BookmarkServiceInterface;
use App\Services\Contracts\NewsApiClientInterface;
use App\Services\Contracts\UserRegistrationServiceInterface;
use App\Services\NewsApiClient;
use App\Services\UserRegistrationService;
use Illuminate\Support\ServiceProvider;

/**
 * Binds abstractions to their concrete implementations so that
 * controllers, commands, and services depend only on interfaces
 * (dependency inversion), never on Eloquent or NewsApiClient directly.
 * Swapping a data source or API client later means adding a new
 * implementation and changing a binding here - nothing else.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ArticleRepositoryInterface::class => EloquentArticleRepository::class,
        CategoryRepositoryInterface::class => EloquentCategoryRepository::class,
        SourceRepositoryInterface::class => EloquentSourceRepository::class,
        BookmarkRepositoryInterface::class => EloquentBookmarkRepository::class,
        UserRepositoryInterface::class => EloquentUserRepository::class,
        ArticleServiceInterface::class => ArticleService::class,
        ArticleImportServiceInterface::class => ArticleImportService::class,
        BookmarkServiceInterface::class => BookmarkService::class,
        UserRegistrationServiceInterface::class => UserRegistrationService::class,
        NewsApiClientInterface::class => NewsApiClient::class,
    ];
}
