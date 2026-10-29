<?php

use PHPUnit\Framework\TestCase;

class PsrLogTest extends TestCase {

    public function testPsrLogLoading(): void {
        $phar = getenv('TEST_PHAR');
        $this->assertNotEmpty($phar, 'TEST_PHAR environment variable must be set');
        $this->assertFileExists($phar);

        $distDir = dirname($phar);
        pathload()->addSearchDir($distDir);
        pathload()->addNamespace('psr-log@3', ['Psr\\Log\\']);

        $this->assertTrue(class_exists(\Psr\Log\NullLogger::class));
        $this->assertTrue(interface_exists(\Psr\Log\LoggerInterface::class));

        $logger = new \Psr\Log\NullLogger();
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
        $logger->info('Testing PSR log NullLogger');
    }
}
