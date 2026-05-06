<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

abstract class AbstractWebTestCase extends WebTestCase
{
    use Factories;

    protected ?KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient([], [
            'HTTP_ACCEPT' => "text/html"
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
    }
}