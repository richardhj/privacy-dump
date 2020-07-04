<?php

declare(strict_types=1);

/*
 * This file is part of richardhj/privacy-dump.
 *
 * Copyright (c) 2020-2020 Richard Henkenjohann
 *
 * @package   richardhj/privacy-dump.
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2020-2020 Richard Henkenjohann
 * @license   GPL-3.0-only
 */

namespace Richardhj\PrivacyDump\Dumper\Config;

use Richardhj\PrivacyDump\Config\Config;
use Richardhj\PrivacyDump\Database\Metadata\MetadataInterface;

class ConfigProcessor
{
    private $metadata;
    private $tableNames;

    public function __construct(MetadataInterface $metadata)
    {
        $this->metadata = $metadata;
    }

    public function process(Config $config): DumperConfig
    {
        $this->processTableLists($config);
        $this->processTablesData($config);

        return new DumperConfig($config);
    }

    private function processTableLists(Config $config): void
    {
        foreach (['tables_include', 'tables_exclude'] as $key) {
            $tableNames = $config->get($key) ?? [];

            if (!empty($tableNames)) {
                $resolved = $this->resolveTableNames($tableNames);
                $config->set($key, $resolved);
            }
        }
    }

    private function processTablesData(Config $config): void
    {
        $tablesData = $config->get('tables') ?? [];
        if (empty($tablesData)) {
            return;
        }

        $resolved = $this->resolveTablesData($tablesData);
        $config->set('tables', $resolved);
    }

    private function resolveTableNames(array $tableNames): array
    {
        $resolved = [];

        foreach ($tableNames as $tableName) {
            $matches = $this->findTablesByName((string) $tableName);
            if (empty($matches)) {
                continue;
            }

            $resolved[] = $matches;
        }

        return array_unique(array_merge(...$resolved));
    }

    private function resolveTablesData(array $tablesData): array
    {
        foreach ($tablesData as $tableName => $tableData) {
            $tableName = (string) $tableName;

            // Find all tables matching the pattern
            $matches = $this->findTablesByName($tableName);

            // Table found is the same as the table name -> nothing to do
            if (1 === \count($matches) && $matches[0] === $tableName) {
                continue;
            }

            // If tables were found -> update the tables data
            foreach ($matches as $match) {
                if (!\array_key_exists($match, $tablesData)) {
                    $tablesData[$match] = [];
                }

                $tablesData[$match] += $tableData;
            }

            // Remove the entry from the tables data
            unset($tablesData[$tableName]);
        }

        return $tablesData;
    }

    private function findTablesByName(string $pattern): array
    {
        if (null === $this->tableNames) {
            $this->tableNames = $this->metadata->getTableNames();
        }

        $matches = [];

        foreach ($this->tableNames as $tableName) {
            if (fnmatch($pattern, $tableName)) {
                $matches[] = $tableName;
            }
        }

        return $matches;
    }
}
