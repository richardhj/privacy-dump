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

namespace Richardhj\PrivacyDump\Dumper;

use Ifsnop\Mysqldump\Mysqldump;
use Richardhj\PrivacyDump\Config\Config;
use Richardhj\PrivacyDump\Converter\ConverterFactory;
use Richardhj\PrivacyDump\Database\Database;
use Richardhj\PrivacyDump\Dumper\Config\ConfigProcessor;
use Richardhj\PrivacyDump\Dumper\Config\DatabaseConfig;
use Richardhj\PrivacyDump\Dumper\Config\DumperConfig;
use Richardhj\PrivacyDump\Dumper\Mysqldump\DataConverterExtension;
use Richardhj\PrivacyDump\Dumper\Mysqldump\TableFilterExtension;

class SqlDumper implements DumperInterface
{
    private $converterFactory;

    public function __construct(ConverterFactory $converterFactory)
    {
        $this->converterFactory = $converterFactory;
    }

    /**
     * @{@inheritdoc}
     */
    public function dump(Config $config, string $target, array $options=[]): DumperInterface
    {
        $databaseConfig = new DatabaseConfig($config->get('database') ?? []);
        $database       = new Database($databaseConfig);

        $dumpConfig = (new ConfigProcessor($database->getMetadata()))->process($config->merge($options));

        // Set the SQL variables
        $connection   = $database->getConnection();
        $dumpSettings = $this->getDumpSettings($dumpConfig);
        $context      = ['vars' => []];

        foreach ($dumpConfig->getVarQueries() as $varName => $query) {
            $value = $connection->fetchColumn($query);

            $context['vars'][$varName] = $value;

            // This is only compatible with MySQL and will require refactoring to add compatibility with other drivers
            $dumpSettings['init_commands'][] = 'SET @'.$varName.' = '.$connection->quote($value);
        }

        $dumper = new Mysqldump(
            $database->getDriver()->getDsn(),
            $databaseConfig->getConnectionParam('user'),
            $databaseConfig->getConnectionParam('password'),
            $dumpSettings,
            $databaseConfig->getDriverOptions()
        );

        // Register conversion and filter extensions
        $dataConverterExtension = new DataConverterExtension($dumpConfig, $this->converterFactory, $context);
        $dataConverterExtension->register($dumper);

        $tableFilterExtension = new TableFilterExtension($database, $dumpConfig);
        $tableFilterExtension->register($dumper);

        // Unset the database object to close the database connection
        unset($database);

        $dumper->start($target);

        return $this;
    }

    /**
     * Get the dump settings.
     */
    private function getDumpSettings(DumperConfig $config): array
    {
        $settings = $config->getDumpSettings();

        $settings['include-tables'] = $config->getTablesIncludeList();
        $settings['exclude-tables'] = $config->getTablesExcludeList();
        $settings['no-data']        = $config->getTablesToTruncate();

        return $settings;
    }
}
