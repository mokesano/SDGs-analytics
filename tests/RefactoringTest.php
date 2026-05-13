<?php

declare(strict_types=1);

namespace Wizdam\Tests;

use PHPUnit\Framework\TestCase;
use Wizdam\Core\Application;
use Wizdam\Core\Database;
use Wizdam\Services\AuthService;
use Wizdam\Services\JournalService;
use Wizdam\Services\OrcidService;
use Wizdam\Services\OrcidProfileService;
use Wizdam\Services\ScopusJournalService;
use Wizdam\Services\SdgClassificationService;
use Wizdam\Services\SdgDefinitionsService;
use Wizdam\Utils\CacheManager;
use Wizdam\Utils\Logger;
use Wizdam\Utils\Security;
use Wizdam\Utils\Validator;

/**
 * Refactoring Verification Test
 * 
 * Test untuk memverifikasi bahwa semua class hasil refactoring
 * memenuhi standar PSR-4 dan PSR-12
 */
class RefactoringTest extends TestCase
{
    /**
     * Test bahwa semua class dapat di-autoload dengan benar
     */
    public function testAutoloading(): void
    {
        // Core classes
        $this->assertTrue(class_exists(Application::class));
        $this->assertTrue(class_exists(Database::class));
        
        // Service classes
        $this->assertTrue(class_exists(AuthService::class));
        $this->assertTrue(class_exists(JournalService::class));
        $this->assertTrue(class_exists(OrcidService::class));
        $this->assertTrue(class_exists(OrcidProfileService::class));
        $this->assertTrue(class_exists(ScopusJournalService::class));
        $this->assertTrue(class_exists(SdgClassificationService::class));
        $this->assertTrue(class_exists(SdgDefinitionsService::class));
        
        // Utility classes
        $this->assertTrue(class_exists(CacheManager::class));
        $this->assertTrue(class_exists(Logger::class));
        $this->assertTrue(class_exists(Security::class));
        $this->assertTrue(class_exists(Validator::class));
    }

    /**
     * Test namespace sesuai dengan struktur folder (PSR-4)
     */
    public function testNamespaceStructure(): void
    {
        $expectedNamespaces = [
            Application::class => 'Wizdam\Core',
            Database::class => 'Wizdam\Core',
            AuthService::class => 'Wizdam\Services',
            JournalService::class => 'Wizdam\Services',
            OrcidService::class => 'Wizdam\Services',
            OrcidProfileService::class => 'Wizdam\Services',
            ScopusJournalService::class => 'Wizdam\Services',
            SdgClassificationService::class => 'Wizdam\Services',
            SdgDefinitionsService::class => 'Wizdam\Services',
            CacheManager::class => 'Wizdam\Utils',
            Logger::class => 'Wizdam\Utils',
            Security::class => 'Wizdam\Utils',
            Validator::class => 'Wizdam\Utils',
        ];

        foreach ($expectedNamespaces as $class => $expectedNamespace) {
            $reflection = new \ReflectionClass($class);
            $this->assertSame(
                $expectedNamespace,
                $reflection->getNamespaceName(),
                "Class {$class} harus berada di namespace {$expectedNamespace}"
            );
        }
    }

    /**
     * Test strict types declaration ada di setiap file
     */
    public function testStrictTypesDeclaration(): void
    {
        $files = [
            PROJECT_ROOT . '/src/Core/Application.php',
            PROJECT_ROOT . '/src/Core/Database.php',
            PROJECT_ROOT . '/src/Services/AuthService.php',
            PROJECT_ROOT . '/src/Services/JournalService.php',
            PROJECT_ROOT . '/src/Services/OrcidService.php',
            PROJECT_ROOT . '/src/Services/OrcidProfileService.php',
            PROJECT_ROOT . '/src/Services/ScopusJournalService.php',
            PROJECT_ROOT . '/src/Services/SdgClassificationService.php',
            PROJECT_ROOT . '/src/Services/SdgDefinitionsService.php',
            PROJECT_ROOT . '/src/Utils/CacheManager.php',
            PROJECT_ROOT . '/src/Utils/Logger.php',
            PROJECT_ROOT . '/src/Utils/Security.php',
            PROJECT_ROOT . '/src/Utils/Validator.php',
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file, "File {$file} harus ada");
            
            $content = file_get_contents($file);
            $this->assertMatchesRegularExpression(
                '/^<\?php\s*\n\s*declare\(strict_types=1\);/m',
                $content,
                "File {$file} harus memiliki declare(strict_types=1)"
            );
        }
    }

    /**
     * Test Validator methods
     */
    public function testValidatorMethods(): void
    {
        // Test ORCID validation
        $this->assertTrue(Validator::validateOrcid('0000-0002-5157-9767'));
        $this->assertFalse(Validator::validateOrcid('invalid-orcid'));
        
        // Test DOI validation
        $this->assertTrue(Validator::validateDoi('10.1038/nature12373'));
        $this->assertFalse(Validator::validateDoi('invalid-doi'));
        
        // Test ISSN validation
        $this->assertTrue(Validator::validateIssn('1234-5678'));
        $this->assertTrue(Validator::validateIssn('0028-0836')); // Nature
        $this->assertFalse(Validator::validateIssn('invalid'));
        
        // Test ISSN formatting
        $this->assertSame('1234-5678', Validator::formatIssn('12345678'));
        $this->assertSame('1234-5678', Validator::formatIssn('1234-5678'));
        
        // Test email validation
        $this->assertTrue(Validator::validateEmail('test@example.com'));
        $this->assertFalse(Validator::validateEmail('invalid-email'));
    }

    /**
     * Test SdgDefinitionsService dapat load definitions
     */
    public function testSdgDefinitionsService(): void
    {
        $service = new SdgDefinitionsService();
        
        // Test get all definitions
        $definitions = $service->getAllDefinitions();
        $this->assertIsArray($definitions);
        $this->assertCount(17, $definitions);
        
        // Test get single definition
        $sdg1 = $service->getDefinition('SDG1');
        $this->assertIsArray($sdg1);
        $this->assertArrayHasKey('code', $sdg1);
        $this->assertArrayHasKey('title', $sdg1);
        $this->assertSame('SDG1', $sdg1['code']);
    }

    /**
     * Test Security methods
     */
    public function testSecurityMethods(): void
    {
        // Test CSRF token generation
        $token = Security::generateCsrfToken();
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
        
        // Test secure token generation
        $secureToken = Security::generateSecureToken();
        $this->assertIsString($secureToken);
        $this->assertEquals(64, strlen($secureToken));
        
        // Test password hashing
        $password = 'testPassword123';
        $hash = Security::hashPassword($password);
        $this->assertIsString($hash);
        $this->assertNotSame($password, $hash);
        $this->assertTrue(Security::verifyPassword($password, $hash));
        $this->assertFalse(Security::verifyPassword('wrongPassword', $hash));
    }

    /**
     * Test CacheManager instantiation
     */
    public function testCacheManagerInstantiation(): void
    {
        $cacheDir = PROJECT_ROOT . '/cache';
        $cacheManager = new CacheManager($cacheDir, true, 3600);
        
        $this->assertInstanceOf(CacheManager::class, $cacheManager);
    }

    /**
     * Test Logger instantiation
     */
    public function testLoggerInstantiation(): void
    {
        $logFile = PROJECT_ROOT . '/logs/test.log';
        $logger = new Logger($logFile, true, 'DEBUG');
        
        $this->assertInstanceOf(Logger::class, $logger);
    }

    /**
     * Test bahwa file API tidak dimodifikasi
     */
    public function testApiFilesNotModified(): void
    {
        $apiFiles = [
            PROJECT_ROOT . '/api/SDG_Classification_API.php',
            PROJECT_ROOT . '/api/ORCID_Profile_API.php',
            PROJECT_ROOT . '/api/SCOPUS_Journal-Checker_API.php',
        ];

        foreach ($apiFiles as $file) {
            $this->assertFileExists($file, "API file {$file} harus tetap ada");
            
            // Verify files still contain original class/function definitions
            $content = file_get_contents($file);
            
            if ($file === PROJECT_ROOT . '/api/SDG_Classification_API.php') {
                $this->assertStringContainsString(
                    'class SDGClassifier',
                    $content,
                    'SDG_Classification_API.php harus tetap mengandung class SDGClassifier'
                );
            }
        }
    }

    /**
     * Test wrapper classes exist and wrap API properly
     */
    public function testWrapperClassesExist(): void
    {
        // SdgClassificationService wraps SDG_Classification_API.php
        $sdgService = new SdgClassificationService();
        $this->assertInstanceOf(SdgClassificationService::class, $sdgService);
        
        // OrcidProfileService wraps ORCID_Profile_API.php
        $orcidProfileService = new OrcidProfileService();
        $this->assertInstanceOf(OrcidProfileService::class, $orcidProfileService);
        
        // ScopusJournalService wraps SCOPUS_Journal-Checker_API.php
        $scopusService = new ScopusJournalService();
        $this->assertInstanceOf(ScopusJournalService::class, $scopusService);
    }
}
