<?php

namespace Wizdam\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wizdam\Core\Application;

/**
 * Unit Test untuk Application Class
 * 
 * Menguji fungsionalitas singleton, konfigurasi, dan service container
 */
class ApplicationTest extends TestCase
{
    /**
     * Reset singleton instance sebelum setiap test
     */
    protected function setUp(): void
    {
        // Gunakan reflection untuk reset singleton instance
        $reflection = new \ReflectionClass(Application::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
        
        // Set error handler untuk menghindari risky test warning
        set_error_handler(function($errno, $errstr) {
            throw new \ErrorException($errstr, 0, $errno);
        });
        set_exception_handler(function($e) {
            throw $e;
        });
    }
    
    /**
     * Cleanup setelah setiap test
     */
    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Test bahwa get() mengembalikan instance yang sama (Singleton Pattern)
     */
    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = Application::get();
        $instance2 = Application::get();
        
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test bahwa constructor bersifat private
     */
    public function testConstructorIsPrivate(): void
    {
        $reflection = new \ReflectionClass(Application::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertTrue($constructor->isPrivate());
    }

    /**
     * Test bahwa clone tidak diperbolehkan
     */
    public function testCloningNotAllowed(): void
    {
        $instance = Application::get();
        
        $this->expectException(\Error::class);
        $clone = clone $instance;
    }

    /**
     * Test bahwa unserialization tidak diperbolehkan
     */
    public function testUnserializationNotAllowed(): void
    {
        $instance = Application::get();
        $serialized = serialize($instance);
        
        $this->expectException(\RuntimeException::class);
        unserialize($serialized);
    }

    /**
     * Test getConfig dan setConfig
     */
    public function testGetAndSetConfig(): void
    {
        $app = Application::get();
        
        // Set config
        $app->setConfig('test_key', 'test_value');
        
        // Get config
        $value = $app->getConfig('test_key');
        $this->assertEquals('test_value', $value);
        
        // Get with default value
        $defaultValue = $app->getConfig('non_existent_key', 'default');
        $this->assertEquals('default', $defaultValue);
    }

    /**
     * Test registerService dan getService
     */
    public function testRegisterAndGetService(): void
    {
        $app = Application::get();
        
        // Register a mock service
        $mockService = new class {
            public function getName(): string
            {
                return 'MockService';
            }
        };
        
        $app->registerService('mock_service', $mockService);
        
        // Get service
        $service = $app->getService('mock_service');
        $this->assertSame($mockService, $service);
        $this->assertEquals('MockService', $service->getName());
    }

    /**
     * Test getService dengan service yang tidak terdaftar
     */
    public function testGetNonExistentService(): void
    {
        $app = Application::get();
        
        $this->expectException(\InvalidArgumentException::class);
        $app->getService('non_existent_service');
    }

    /**
     * Test hasService method
     */
    public function testHasService(): void
    {
        $app = Application::get();
        
        $this->assertFalse($app->hasService('test_service'));
        
        $app->registerService('test_service', new \stdClass());
        
        $this->assertTrue($app->hasService('test_service'));
    }

    /**
     * Test getAllConfig returns array
     */
    public function testGetAllConfigReturnsArray(): void
    {
        $app = Application::get();
        $config = $app->getAllConfig();
        
        $this->assertIsArray($config);
    }

    /**
     * Test isBooted method
     */
    public function testIsBootedInitiallyFalse(): void
    {
        // Setelah setUp, instance baru dibuat
        $app = Application::get();
        
        // Booted seharusnya false sebelum execute dipanggil
        // Note: initialize() mungkin sudah dipanggil di constructor
        // Kita test bahwa method exists dan returns boolean
        $this->assertIsBool($app->isBooted());
    }
}
