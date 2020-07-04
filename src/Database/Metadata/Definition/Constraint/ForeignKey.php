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

namespace Richardhj\PrivacyDump\Database\Metadata\Definition\Constraint;

class ForeignKey
{
    private $constraintName;
    private $localTableName;
    private $localColumns;
    private $foreignTableName;
    private $foreignColumns;

    /**
     * @param string[] $localColumns
     * @param string[] $foreignColumns
     */
    public function __construct(
        string $constraintName,
        string $localTableName,
        array $localColumns,
        string $foreignTableName,
        array $foreignColumns
    ) {
        $this->constraintName   = $constraintName;
        $this->localTableName   = $localTableName;
        $this->localColumns     = $localColumns;
        $this->foreignTableName = $foreignTableName;
        $this->foreignColumns   = $foreignColumns;
    }

    public function getConstraintName(): string
    {
        return $this->constraintName;
    }

    public function getLocalTableName(): string
    {
        return $this->localTableName;
    }

    public function getLocalColumns(): array
    {
        return $this->localColumns;
    }

    public function getForeignTableName(): string
    {
        return $this->foreignTableName;
    }

    public function getForeignColumns(): array
    {
        return $this->foreignColumns;
    }
}
