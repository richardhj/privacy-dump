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

namespace Richardhj\PrivacyDump\Dumper\Config\Table\Filter;

use UnexpectedValueException;

class SortOrder
{
    const DIRECTION_ASC  = 'ASC';
    const DIRECTION_DESC = 'DESC';

    private $column;
    private $direction;

    public function __construct(string $column, string $direction = self::DIRECTION_ASC)
    {
        $direction = strtoupper($direction);

        if (self::DIRECTION_ASC !== $direction && self::DIRECTION_DESC !== $direction) {
            throw new UnexpectedValueException(sprintf('Invalid sort direction "%s".', $direction));
        }

        $this->column    = $column;
        $this->direction = $direction;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }
}
