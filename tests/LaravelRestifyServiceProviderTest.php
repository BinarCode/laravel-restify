<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\LaravelRestifyServiceProvider;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;

class LaravelRestifyServiceProviderTest extends IntegrationTestCase
{
    #[Test]
    public function it_publishes_the_package_views_to_the_restify_vendor_views_directory(): void
    {
        $paths = ServiceProvider::pathsToPublish(LaravelRestifyServiceProvider::class, 'restify-views');
        $publishedPath = array_values($paths)[0] ?? '';

        $this->assertSame(
            str_replace('\\', '/', resource_path('views/vendor/restify')),
            str_replace('\\', '/', $publishedPath),
        );
    }
}
