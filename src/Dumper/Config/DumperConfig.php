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

use Ifsnop\Mysqldump\Mysqldump;
use Richardhj\PrivacyDump\Config\Config;
use Richardhj\PrivacyDump\Dumper\Config\Table\TableConfig;
use Richardhj\PrivacyDump\Dumper\Config\Validation\QueryValidator;
use UnexpectedValueException;

class DumperConfig
{
    private $tablesConfig      = [];
    private $varQueries        = [];
    private $tablesIncludeList = [];
    private $tablesExcludeList = [];
    private $tablesToTruncate  = [];
    private $tablesToFilter    = [];
    private $tablesToSort      = [];

    private $dumpSettings = [
        'compress'              => Mysqldump::NONE,
        'init_commands'         => [],
        'reset-auto-increment'  => false,
        'add-drop-database'     => false,
        'add-drop-table'        => true, // false in MySQLDump-PHP
        'add-drop-trigger'      => true,
        'add-locks'             => true,
        'complete-insert'       => false,
        'default-character-set' => Mysqldump::UTF8,
        'disable-keys'          => true,
        'extended-insert'       => true,
        'events'                => false,
        'hex-blob'              => true,
        'insert-ignore'         => false,
        'net_buffer_length'     => Mysqldump::MAXLINESIZE,
        'no-autocommit'         => true,
        'no-create-info'        => false,
        'lock-tables'           => false, // true in MySQLDump-PHP
        'routines'              => false,
        'single-transaction'    => true,
        'skip-triggers'         => false,
        'skip-tz-utc'           => false,
        'skip-comments'         => false,
        'skip-dump-date'        => false,
        'skip-definer'          => false,
    ];

    public function __construct(Config $config)
    {
        $this->prepareConfig($config);
    }

    public function getDumpSettings(): array
    {
        return $this->dumpSettings;
    }

    public function getTablesConfig(): array
    {
        return $this->tablesConfig;
    }

    public function getTableConfig(string $tableName): ?TableConfig
    {
        return $this->tablesConfig[$tableName] ?? null;
    }

    /**
     * Get the SQL queries to run.
     *
     * The result of each query will then be injected into user-defined variables.
     * Array keys are the variable names, array values are the database queries.
     *
     * @return string[]
     */
    public function getVarQueries(): array
    {
        return $this->varQueries;
    }

    public function getTablesIncludeList(): array
    {
        return $this->tablesIncludeList;
    }

    public function getTablesExcludeList(): array
    {
        return $this->tablesExcludeList;
    }

    public function getTablesToTruncate(): array
    {
        return $this->tablesToTruncate;
    }

    public function getTablesToFilter(): array
    {
        return $this->tablesToFilter;
    }

    public function getTablesToSort(): array
    {
        return $this->tablesToSort;
    }

    private function prepareConfig(Config $config): void
    {
        $this->prepareDumpSettings($config->get('dump') ?? []);
        $this->prepareTablesConfig($config);
        $this->prepareVarQueries($config);
        $this->prepareTablesIncludeList($config);
        $this->prepareTablesExcludeList($config);
    }

    private function prepareDumpSettings(array $options): void
    {
        foreach ($options as $param => $value) {
            if (!\array_key_exists($param, $this->dumpSettings)) {
                throw new UnexpectedValueException(sprintf('Invalid dump setting "%s".', $param));
            }

            $this->dumpSettings[$param] = $value;
        }
    }

    private function prepareTablesConfig(Config $config): void
    {
        foreach ($config->get('tables') ?? [] as $tableName => $tableData) {
            $tableName = (string) $tableName;

            $tableConfig                    = new TableConfig($tableName, $tableData);
            $this->tablesConfig[$tableName] = $tableConfig;

            if (0 === $tableConfig->getLimit()) {
                $this->tablesToTruncate[] = $tableConfig->getName();
            }

            if ($tableConfig->hasSortOrder()) {
                $this->tablesToSort[] = $tableConfig->getName();
            }

            if ($tableConfig->hasFilter() || $tableConfig->hasLimit()) {
                $this->tablesToFilter[] = $tableConfig->getName();
            }
        }
    }

    private function prepareVarQueries(Config $config): void
    {
        $queryValidator   = new QueryValidator();
        $this->varQueries = $config->get('variables') ?? [];

        foreach ($this->varQueries as $index => $query) {
            $queryValidator->validate($query);
            $this->varQueries[$index] = (string) $query;
        }
    }

    private function prepareTablesIncludeList(Config $config): void
    {
        $this->tablesIncludeList = $config->get('tables_include') ?? [];

        foreach ($this->tablesIncludeList as $index => $tableName) {
            $this->tablesIncludeList[$index] = (string) $tableName;
        }
    }

    private function prepareTablesExcludeList(Config $config): void
    {
        $this->tablesExcludeList = $config->get('tables_exclude') ?? [];

        foreach ($this->tablesExcludeList as $index => $tableName) {
            $this->tablesExcludeList[$index] = (string) $tableName;
        }
    }
}
