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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Richardhj\PrivacyDump\Database\Driver\DriverInterface;
use Richardhj\PrivacyDump\Database\Driver\MysqlDriver;
use Richardhj\PrivacyDump\Database\Metadata\MetadataInterface;
use Richardhj\PrivacyDump\Database\Metadata\MysqlMetadata;
use Richardhj\PrivacyDump\Dumper\Config\DatabaseConfig;
use UnexpectedValueException;

/**
 * Wrapper that stores the following objects:.
 *
 * - connection: the Doctrine connection
 * - driver: allows to retrieve the DSN that was used to connect to the database
 * - metadata: allows to fetch the database metadata (table names, foreign key constraints)
 *
 * We use a custom abstraction layer for database metadata, because the Doctrine schema manager
 * crashes when used with databases that use custom Doctrine types (e.g. OroCommerce).
 */
final class Database
{
    private $connection;
    private $driver;
    private $metadata;

    public function __construct(DatabaseConfig $config)
    {
        $this->connection = $this->createConnection($config);
        $driver           = $config->getDriver();

        switch ($driver) {
            case 'pdo_mysql':
                $this->driver   = new MysqlDriver($this->connection);
                $this->metadata = new MysqlMetadata($this->connection);
                break;

            default:
                throw new UnexpectedValueException(sprintf('The database driver "%s" is not supported.', $driver));
        }
    }

    public function __destruct()
    {
        $this->connection->close();
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    public function getMetadata(): MetadataInterface
    {
        return $this->metadata;
    }

    private function createConnection(DatabaseConfig $config): Connection
    {
        // Get the connection parameters from the config
        $params = $config->getConnectionParams();

        // A DSN holds all config parameters that we need to build the connection
        if (isset($params['dsn'])) {
            return DriverManager::getConnection(['url' => $params['dsn']]);
        }

        // Rename parameters that do not match Doctrine naming conventions (name -> dbname)
        $params['dbname'] = $params['database'];
        unset($params['database']);

        // Remove empty elements
        $params = array_filter($params);

        // Set the driver
        $params['driver']        = $config->getDriver();
        $params['driverOptions'] = $config->getDriverOptions();

        return DriverManager::getConnection($params);
    }
}
