<?php

namespace Tests\Unit\Usdt;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

/**
 * Just what App\Services\Usdt needs without the database: the usdt config
 * and Laravel's HTTP client (so Http::fake() works). Booting the whole app
 * would need the site's settings table.
 */
abstract class UsdtTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = new Container();
        $app->instance('config', new Repository(['usdt' => require __DIR__ . '/../../../config/usdt.php', 'app' => ['timezone' => 'UTC']]));
        $app->instance(Factory::class, new Factory());
        Container::setInstance($app);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        parent::tearDown();
    }
}
