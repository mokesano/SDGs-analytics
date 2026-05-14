<?php

declare(strict_types=1);

namespace Wizdam\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wizdam\Services\ResearcherIdentityService;

/**
 * Unit Test untuk ResearcherIdentityService
 * Menguji ekstraksi Scopus ID, ResearcherID, dan identifier lainnya dari data ORCID
 */
class ResearcherIdentityTest extends TestCase
{
    private ResearcherIdentityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ResearcherIdentityService();
    }

    /**
     * Test ekstraksi Scopus Author ID dari external-identifiers
     */
    public function testExtractScopusAuthorIdFromExternalIdentifiers(): void
    {
        $mockData = [
            'external-identifiers' => [
                'external-identifier' => [
                    [
                        'external-id-type' => 'SCOPUS_AUTHOR_ID',
                        'external-id-value' => '57209634100',
                        'external-id-url' => ['value' => 'https://www.scopus.com/authid/detail.uri?authorId=57209634100'],
                    ],
                ],
            ],
        ];

        $scopusId = $this->service->getScopusAuthorId($mockData);
        
        $this->assertEquals('57209634100', $scopusId);
    }

    /**
     * Test ekstraksi ResearcherID dari external-identifiers
     */
    public function testExtractResearcherIdFromExternalIdentifiers(): void
    {
        $mockData = [
            'external-identifiers' => [
                'external-identifier' => [
                    [
                        'external-id-type' => 'RESEARCHERID',
                        'external-id-value' => 'A-1234-2008',
                        'external-id-url' => ['value' => 'https://www.researcherid.com/rid/A-1234-2008'],
                    ],
                ],
            ],
        ];

        $researcherId = $this->service->getResearcherId($mockData);
        
        $this->assertEquals('A-1234-2008', $researcherId);
    }

    /**
     * Test ekstraksi Publons ID dari external-identifiers
     */
    public function testExtractPublonsIdFromExternalIdentifiers(): void
    {
        $mockData = [
            'external-identifiers' => [
                'external-identifier' => [
                    [
                        'external-id-type' => 'PUBLONS',
                        'external-id-value' => '12345',
                        'external-id-url' => ['value' => 'https://publons.com/researcher/12345'],
                    ],
                ],
            ],
        ];

        $publonsId = $this->service->getPublonsId($mockData);
        
        $this->assertEquals('12345', $publonsId);
    }

    /**
     * Test ekstraksi Google Scholar ID dari external-identifiers
     */
    public function testExtractGoogleScholarIdFromExternalIdentifiers(): void
    {
        $mockData = [
            'external-identifiers' => [
                'external-identifier' => [
                    [
                        'external-id-type' => 'GOOGLE_SCHOLAR',
                        'external-id-value' => 'abc123XYZ',
                        'external-id-url' => ['value' => 'https://scholar.google.com/citations?user=abc123XYZ'],
                    ],
                ],
            ],
        ];

        $scholarId = $this->service->getGoogleScholarId($mockData);
        
        // GOOGLE_SCHOLAR tidak ada di mapping, harus menggunakan lowercase
        $this->assertEquals('abc123XYZ', $scholarId);
    }

    /**
     * Test ekstraksi Scopus ID dari URL researcher
     */
    public function testExtractScopusIdFromUrl(): void
    {
        $mockData = [
            'researcher-urls' => [
                'researcher-url' => [
                    [
                        'url-name' => 'Scopus Profile',
                        'url' => ['value' => 'https://www.scopus.com/authid/detail.uri?partnerID=HzOxMe3b&origin=inward&AuthorID=57209634100'],
                    ],
                ],
            ],
        ];

        $scopusId = $this->service->getScopusAuthorId($mockData);
        
        $this->assertEquals('57209634100', $scopusId);
    }

    /**
     * Test ekstraksi ResearcherID dari URL
     */
    public function testExtractResearcherIdFromUrl(): void
    {
        $mockData = [
            'researcher-urls' => [
                'researcher-url' => [
                    [
                        'url-name' => 'ResearcherID',
                        'url' => ['value' => 'https://www.researcherid.com/rid/A-1234-2008'],
                    ],
                ],
            ],
        ];

        $researcherId = $this->service->getResearcherId($mockData);
        
        $this->assertEquals('A-1234-2008', $researcherId);
    }

    /**
     * Test validasi format Scopus Author ID
     */
    public function testValidScopusAuthorIdFormat(): void
    {
        $this->assertTrue($this->service->isValidScopusAuthorId('5720963410'));
        $this->assertTrue($this->service->isValidScopusAuthorId('57209634100'));
        $this->assertFalse($this->service->isValidScopusAuthorId('572096341')); // Too short
        $this->assertFalse($this->service->isValidScopusAuthorId('572096341000')); // Too long
        $this->assertFalse($this->service->isValidScopusAuthorId('ABC123')); // Invalid chars
    }

    /**
     * Test validasi format ResearcherID
     */
    public function testValidResearcherIdFormat(): void
    {
        $this->assertTrue($this->service->isValidResearcherId('A-1234-2008'));
        $this->assertTrue($this->service->isValidResearcherId('AB-5678-2010'));
        $this->assertFalse($this->service->isValidResearcherId('A-123-2008')); // Wrong digit count
        $this->assertFalse($this->service->isValidResearcherId('a-1234-2008')); // Lowercase
        $this->assertFalse($this->service->isValidResearcherId('ABC-1234-2008')); // 3 letters
    }

    /**
     * Test ekstraksi semua identifier sekaligus
     */
    public function testExtractAllIdentifiers(): void
    {
        $mockData = [
            'external-identifiers' => [
                'external-identifier' => [
                    [
                        'external-id-type' => 'SCOPUS_AUTHOR_ID',
                        'external-id-value' => '57209634100',
                        'external-id-url' => ['value' => null],
                    ],
                    [
                        'external-id-type' => 'RESEARCHERID',
                        'external-id-value' => 'A-1234-2008',
                        'external-id-url' => ['value' => null],
                    ],
                    [
                        'external-id-type' => 'ORCID',
                        'external-id-value' => '0000-0001-9006-2018',
                        'external-id-url' => ['value' => 'https://orcid.org/0000-0001-9006-2018'],
                    ],
                ],
            ],
        ];

        $allIds = $this->service->extractAllIdentifiers($mockData);
        
        $this->assertArrayHasKey('scopus-author-id', $allIds);
        $this->assertArrayHasKey('researcherid', $allIds);
        $this->assertArrayHasKey('orcid', $allIds);
        $this->assertEquals('57209634100', $allIds['scopus-author-id']['value']);
        $this->assertEquals('A-1234-2008', $allIds['researcherid']['value']);
        $this->assertEquals('0000-0001-9006-2018', $allIds['orcid']['value']);
    }

    /**
     * Test ringkasan identifier
     */
    public function testGetIdentifierSummary(): void
    {
        $mockData = [
            'external-identifiers' => [
                'external-identifier' => [
                    [
                        'external-id-type' => 'SCOPUS_AUTHOR_ID',
                        'external-id-value' => '57209634100',
                        'external-id-url' => ['value' => null],
                    ],
                ],
            ],
        ];

        $summary = $this->service->getIdentifierSummary($mockData);
        
        $this->assertTrue($summary['has_scopus_id']);
        $this->assertFalse($summary['has_researcher_id']);
        $this->assertFalse($summary['has_publons_id']);
        $this->assertFalse($summary['has_google_scholar_id']);
        $this->assertGreaterThanOrEqual(1, $summary['total_identifiers']);
    }

    /**
     * Test dengan data kosong
     */
    public function testExtractFromEmptyData(): void
    {
        $mockData = [];
        
        $allIds = $this->service->extractAllIdentifiers($mockData);
        
        $this->assertEmpty($allIds);
        $this->assertNull($this->service->getScopusAuthorId($mockData));
        $this->assertNull($this->service->getResearcherId($mockData));
    }

    /**
     * Test normalisasi tipe identifier dengan berbagai variasi
     */
    public function testIdentifierTypeNormalization(): void
    {
        $mockData = [
            'external-identifiers' => [
                'external-identifier' => [
                    [
                        'external-id-type' => 'scopus author id', // lowercase with space
                        'external-id-value' => '57209634100',
                        'external-id-url' => ['value' => null],
                    ],
                    [
                        'external-id-type' => 'Web of Science ResearcherID', // Full name
                        'external-id-value' => 'B-5678-2012',
                        'external-id-url' => ['value' => null],
                    ],
                ],
            ],
        ];

        $allIds = $this->service->extractAllIdentifiers($mockData);
        
        $this->assertArrayHasKey('scopus-author-id', $allIds);
        $this->assertArrayHasKey('researcherid', $allIds);
    }
}
