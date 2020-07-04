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

use Nyholm\DSN;
use UnexpectedValueException;

class DatabaseConfig
{
    private $driver           = 'pdo_mysql';
    private $driverOptions    = [];
    private $connectionParams = [];

    private $defaults = [
        'pdo_mysql' => ['host' => 'localhost', 'user' => 'root'],
    ];

    public function __construct(array $params)
    {
        $this->prepareConfig($params);
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getDriverOptions(): array
    {
        return $this->driverOptions;
    }

    public function getConnectionParams(): array
    {
        return $this->connectionParams;
    }

    public function getConnectionParam(string $name)
    {
        return $this->connectionParams[$name] ?? null;
    }

    private function prepareConfig(array $params): void
    {
        // A DSN holds all config and is valid standalone
        if (isset($params['dsn'])) {
            $dsn = new DSN((string) $params['dsn']);

            $this->connectionParams['dsn']      = $dsn->getDsn();
            $this->connectionParams['user']     = $dsn->getUsername();
            $this->connectionParams['password'] = $dsn->getPassword();

            return;
        }

        // The database name is mandatory, no matter what driver is used
        // (this will require some refactoring if SQLite compatibility is added)
        if (!isset($params['database'])) {
            throw new UnexpectedValueException('Missing database name.');
        }

        // Set the driver
        if (isset($params['driver'])) {
            $this->driver = (string) $params['driver'];
            unset($params['driver']);
        }

        // Set the driver options (PDO settings)
        if (\array_key_exists('driver_options', $params)) {
            $this->driverOptions = $params['driver_options'];
            unset($params['driver_options']);
        }

        // Set connection parameters values
        if (isset($this->defaults[$this->driver])) {
            $this->connectionParams = $this->defaults[$this->driver];
        }

        foreach ($params as $param => $value) {
            $this->connectionParams[$param] = (string) $value;
        }
    }
}
