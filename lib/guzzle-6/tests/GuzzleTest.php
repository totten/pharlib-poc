<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class GuzzleTest extends TestCase {

    public function testGuzzleClient(): void {
        $phar = getenv('TEST_PHAR');
        $this->assertNotEmpty($phar, 'TEST_PHAR environment variable must be set');
        $this->assertFileExists($phar);

        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }

        $distDir = dirname($phar);
        pathload()->addSearchDir($distDir);
        pathload()->addNamespace('guzzle@6', ['GuzzleHttp\\']);

        $client = new Client();
        $this->assertInstanceOf(Client::class, $client);
    }
}
