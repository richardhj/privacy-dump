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

namespace Richardhj\PrivacyDump\Database;

use Richardhj\PrivacyDump\Database\Metadata\Definition\Constraint\ForeignKey;
use Richardhj\PrivacyDump\Database\Metadata\MetadataInterface;

class TableDependencyResolver
{
    /**
     * @var MetadataInterface
     */
    private $metadata;

    /**
     * @var ForeignKey[][]
     */
    private $foreignKeys;

    public function __construct(MetadataInterface $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Get the foreign keys that are related to the specified table.
     *
     * e.g.
     * - with $tableName as "table1"
     * - with foreign keys as: table2 with FK to table 1, table 3 with FK to table 2
     *
     * Result will be:
     * ```
     * [
     *    'table2' => [FK of table 2 to table 1]
     *    'table3' => [FK of table 3 to table 2]
     * ]
     * ```
     */
    public function getTableDependencies(string $tableName): array
    {
        $this->buildDependencyTree();

        return $this->resolveDependencies($tableName);
    }

    /**
     * Get the foreign keys that are related to the specified tables.
     */
    public function getTablesDependencies(array $tableNames): array
    {
        $this->buildDependencyTree();

        $dependencies = [];
        foreach ($tableNames as $tableName) {
            $dependencies = $this->resolveDependencies($tableName, $dependencies);
        }

        return $dependencies;
    }

    /**
     * Recursively fetch all dependencies related to a table.
     */
    private function resolveDependencies(string $tableName, array $resolved = []): array
    {
        // No foreign key to this table
        if (!isset($this->foreignKeys[$tableName])) {
            return $resolved;
        }

        foreach ($this->foreignKeys[$tableName] as $foreignKey) {
            $dependencyTable = $foreignKey->getLocalTableName();

            // Detect cyclic dependencies
            if ($dependencyTable === $tableName) {
                continue;
            }

            $resolved[$dependencyTable][$tableName] = $foreignKey;
            $resolved                               = $this->resolveDependencies($dependencyTable, $resolved);
        }

        return $resolved;
    }

    /**
     * Build the tables dependencies (parent -> children).
     */
    private function buildDependencyTree(): void
    {
        if (null !== $this->foreignKeys) {
            return;
        }

        foreach ($this->metadata->getTableNames() as $tableName) {
            foreach ($this->metadata->getForeignKeys($tableName) as $foreignKey) {
                $foreignTableName                       = $foreignKey->getForeignTableName();
                $this->foreignKeys[$foreignTableName][] = $foreignKey;
            }
        }
    }
}
