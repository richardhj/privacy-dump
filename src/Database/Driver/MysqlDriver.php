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

namespace Richardhj\PrivacyDump\Database\Driver;

use Doctrine\DBAL\Connection;

class MysqlDriver implements DriverInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string[]
     */
    private $params = [
        'host',
        'port',
        'dbname',
        'unix_socket',
        'charset',
    ];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getDsn(): string
    {
        $values = $this->connection->getParams();
        $dsn    = [];

        foreach ($this->params as $param) {
            if (!\array_key_exists($param, $values)) {
                continue;
            }

            $value = $values[$param];
            if ('' !== $value && null !== $value) {
                $dsn[] = $param.'='.$value;
            }
        }

        return 'mysql:'.implode(';', $dsn);
    }
}
