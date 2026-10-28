<?php

namespace App\Tests\Integration;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

class ApplicationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testKernelStartsSuccessfully(): void
    {
        self::bootKernel();

        $this->assertNotNull(self::getContainer());
    }

    public function testApplicationHasRoutes(): void
    {
        self::bootKernel();

        $router = self::getContainer()->get(RouterInterface::class);
        $routes = $router->getRouteCollection();

        $this->assertGreaterThan(0, $routes->count());
    }
}
