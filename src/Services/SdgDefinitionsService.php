<?php

declare(strict_types=1);

namespace Wizdam\Services;

use Exception;

/**
 * SDG Definitions Service
 * 
 * Wrapper class untuk sdg_definitions.php yang menyediakan interface OOP
 * untuk akses definisi SDG tanpa mengubah file asli.
 * 
 * @version 1.0.0
 * @author Wizdam Team
 * @license MIT
 */
class SdgDefinitionsService
{
    private string $definitionsFile;
    private ?array $definitions = null;

    /**
     * Constructor
     */
    public function __construct(string $definitionsFile = '')
    {
        // Use provided path or default to includes/sdg_definitions.php
        if ($definitionsFile === '') {
            $projectRoot = dirname(__DIR__, 2); // Go up from src/Services to project root
            $definitionsFile = $projectRoot . '/includes/sdg_definitions.php';
        }
        
        $this->definitionsFile = $definitionsFile;
        
        if (!file_exists($this->definitionsFile)) {
            throw new Exception('SDG definitions file not found: ' . $this->definitionsFile);
        }
    }

    /**
     * Load SDG definitions from file
     */
    private function loadDefinitions(): void
    {
        if ($this->definitions !== null) {
            return;
        }

        require $this->definitionsFile;
        
        // Access global variable
        if (isset($GLOBALS['SDG_DEFINITIONS']) && is_array($GLOBALS['SDG_DEFINITIONS'])) {
            $this->definitions = $GLOBALS['SDG_DEFINITIONS'];
        } else {
            // Fallback to default definitions
            $this->definitions = $this->getDefaultDefinitions();
        }
    }

    /**
     * Get default SDG definitions as fallback
     */
    private function getDefaultDefinitions(): array
    {
        return [
            'SDG1' => [
                'number' => 1,
                'title' => 'No Poverty',
                'description' => 'End poverty in all its forms everywhere',
                'color' => '#e5243b',
                'targets' => [],
                'keywords' => ['poverty', 'extreme poverty', 'social protection']
            ],
            'SDG2' => [
                'number' => 2,
                'title' => 'Zero Hunger',
                'description' => 'End hunger, achieve food security and improved nutrition',
                'color' => '#dda63a',
                'targets' => [],
                'keywords' => ['hunger', 'food security', 'nutrition', 'agriculture']
            ],
            'SDG3' => [
                'number' => 3,
                'title' => 'Good Health and Well-being',
                'description' => 'Ensure healthy lives and promote well-being for all',
                'color' => '#4c9f38',
                'targets' => [],
                'keywords' => ['health', 'healthcare', 'disease', 'mortality']
            ],
            'SDG4' => [
                'number' => 4,
                'title' => 'Quality Education',
                'description' => 'Ensure inclusive and equitable quality education',
                'color' => '#c5192d',
                'targets' => [],
                'keywords' => ['education', 'learning', 'school', 'literacy']
            ],
            'SDG5' => [
                'number' => 5,
                'title' => 'Gender Equality',
                'description' => 'Achieve gender equality and empower all women and girls',
                'color' => '#ff3a21',
                'targets' => [],
                'keywords' => ['gender equality', 'women empowerment', 'discrimination']
            ],
            'SDG6' => [
                'number' => 6,
                'title' => 'Clean Water and Sanitation',
                'description' => 'Ensure availability and sustainable management of water',
                'color' => '#26bde2',
                'targets' => [],
                'keywords' => ['clean water', 'sanitation', 'water quality']
            ],
            'SDG7' => [
                'number' => 7,
                'title' => 'Affordable and Clean Energy',
                'description' => 'Ensure access to affordable, reliable, sustainable energy',
                'color' => '#fcc30b',
                'targets' => [],
                'keywords' => ['renewable energy', 'clean energy', 'energy access']
            ],
            'SDG8' => [
                'number' => 8,
                'title' => 'Decent Work and Economic Growth',
                'description' => 'Promote sustained, inclusive and sustainable economic growth',
                'color' => '#a21942',
                'targets' => [],
                'keywords' => ['economic growth', 'employment', 'decent work']
            ],
            'SDG9' => [
                'number' => 9,
                'title' => 'Industry, Innovation and Infrastructure',
                'description' => 'Build resilient infrastructure, promote industrialization',
                'color' => '#fd6925',
                'targets' => [],
                'keywords' => ['infrastructure', 'innovation', 'industrialization']
            ],
            'SDG10' => [
                'number' => 10,
                'title' => 'Reduced Inequalities',
                'description' => 'Reduce inequality within and among countries',
                'color' => '#dd1367',
                'targets' => [],
                'keywords' => ['inequalities', 'migration', 'income inequality']
            ],
            'SDG11' => [
                'number' => 11,
                'title' => 'Sustainable Cities and Communities',
                'description' => 'Make cities and human settlements inclusive, safe, resilient',
                'color' => '#fd9d24',
                'targets' => [],
                'keywords' => ['sustainable cities', 'urban planning', 'housing']
            ],
            'SDG12' => [
                'number' => 12,
                'title' => 'Responsible Consumption and Production',
                'description' => 'Ensure sustainable consumption and production patterns',
                'color' => '#bf8b2e',
                'targets' => [],
                'keywords' => ['responsible consumption', 'waste management', 'recycling']
            ],
            'SDG13' => [
                'number' => 13,
                'title' => 'Climate Action',
                'description' => 'Take urgent action to combat climate change',
                'color' => '#3f7e44',
                'targets' => [],
                'keywords' => ['climate change', 'global warming', 'carbon emission']
            ],
            'SDG14' => [
                'number' => 14,
                'title' => 'Life Below Water',
                'description' => 'Conserve and sustainably use the oceans, seas, marine resources',
                'color' => '#0a97d9',
                'targets' => [],
                'keywords' => ['marine pollution', 'ocean acidification', 'fishing']
            ],
            'SDG15' => [
                'number' => 15,
                'title' => 'Life on Land',
                'description' => 'Protect, restore and promote sustainable use of terrestrial ecosystems',
                'color' => '#56c02b',
                'targets' => [],
                'keywords' => ['biodiversity', 'deforestation', 'ecosystem']
            ],
            'SDG16' => [
                'number' => 16,
                'title' => 'Peace, Justice and Strong Institutions',
                'description' => 'Promote peaceful and inclusive societies for sustainable development',
                'color' => '#00689d',
                'targets' => [],
                'keywords' => ['peace', 'justice', 'strong institutions', 'governance']
            ],
            'SDG17' => [
                'number' => 17,
                'title' => 'Partnerships for the Goals',
                'description' => 'Strengthen the means of implementation and revitalize global partnership',
                'color' => '#19486a',
                'targets' => [],
                'keywords' => ['partnerships', 'global cooperation', 'international support']
            ]
        ];
    }

    /**
     * Get all SDG definitions
     */
    public function getAllDefinitions(): array
    {
        $this->loadDefinitions();
        return $this->definitions;
    }

    /**
     * Get definition by SDG code
     * 
     * @param string $sdgCode SDG code (e.g., 'SDG1', 'SDG2')
     * @return array|null Definition or null if not found
     */
    public function getDefinition(string $sdgCode): ?array
    {
        $this->loadDefinitions();
        return $this->definitions[$sdgCode] ?? null;
    }

    /**
     * Get SDG title by code
     */
    public function getTitle(string $sdgCode): string
    {
        $definition = $this->getDefinition($sdgCode);
        return $definition['title'] ?? 'Unknown SDG';
    }

    /**
     * Get SDG description by code
     */
    public function getDescription(string $sdgCode): string
    {
        $definition = $this->getDefinition($sdgCode);
        return $definition['description'] ?? '';
    }

    /**
     * Get SDG color by code
     */
    public function getColor(string $sdgCode): string
    {
        $definition = $this->getDefinition($sdgCode);
        return $definition['color'] ?? '#cccccc';
    }

    /**
     * Get SDG number by code
     */
    public function getNumber(string $sdgCode): int
    {
        $definition = $this->getDefinition($sdgCode);
        return $definition['number'] ?? 0;
    }

    /**
     * Get SDG targets by code
     */
    public function getTargets(string $sdgCode): array
    {
        $definition = $this->getDefinition($sdgCode);
        return $definition['targets'] ?? [];
    }

    /**
     * Get SDG keywords by code
     */
    public function getKeywords(string $sdgCode): array
    {
        $definition = $this->getDefinition($sdgCode);
        return $definition['keywords'] ?? [];
    }

    /**
     * Search SDGs by keyword
     * 
     * @param string $keyword Keyword to search
     * @return array Matching SDG codes
     */
    public function searchByKeyword(string $keyword): array
    {
        $this->loadDefinitions();
        $matches = [];
        $keyword = strtolower($keyword);

        foreach ($this->definitions as $code => $definition) {
            // Search in title
            if (stripos($definition['title'], $keyword) !== false) {
                $matches[$code] = $definition;
                continue;
            }

            // Search in description
            if (stripos($definition['description'], $keyword) !== false) {
                $matches[$code] = $definition;
                continue;
            }

            // Search in keywords
            if (isset($definition['keywords'])) {
                foreach ($definition['keywords'] as $kw) {
                    if (stripos($kw, $keyword) !== false) {
                        $matches[$code] = $definition;
                        break;
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Get all SDG codes
     */
    public function getAllCodes(): array
    {
        $this->loadDefinitions();
        return array_keys($this->definitions);
    }

    /**
     * Count total SDGs
     */
    public function count(): int
    {
        $this->loadDefinitions();
        return count($this->definitions);
    }

    /**
     * Reload definitions from file
     */
    public function reload(): void
    {
        $this->definitions = null;
        $this->loadDefinitions();
    }
}
