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

class Filter
{
    const OPERATOR_EQ          = 'eq';
    const OPERATOR_NEQ         = 'neq';
    const OPERATOR_LT          = 'lt';
    const OPERATOR_LTE         = 'lte';
    const OPERATOR_GT          = 'gt';
    const OPERATOR_GTE         = 'gte';
    const OPERATOR_IS_NULL     = 'isNull';
    const OPERATOR_IS_NOT_NULL = 'isNotNull';
    const OPERATOR_LIKE        = 'like';
    const OPERATOR_NOT_LIKE    = 'notLike';
    const OPERATOR_IN          = 'in';
    const OPERATOR_NOT_IN      = 'notIn';

    private static $operators = [
        self::OPERATOR_EQ,
        self::OPERATOR_NEQ,
        self::OPERATOR_LT,
        self::OPERATOR_LTE,
        self::OPERATOR_GT,
        self::OPERATOR_GTE,
        self::OPERATOR_IS_NULL,
        self::OPERATOR_IS_NOT_NULL,
        self::OPERATOR_LIKE,
        self::OPERATOR_NOT_LIKE,
        self::OPERATOR_IN,
        self::OPERATOR_NOT_IN,
    ];

    private $column;

    private $operator;

    private $value;

    public function __construct(string $column, string $operator, $value = null)
    {
        if (!\in_array($operator, self::$operators, true)) {
            throw new UnexpectedValueException(sprintf('Invalid filter operator "%s".', $operator));
        }

        if (\is_array($value) && !\in_array($operator, [self::OPERATOR_IN, self::OPERATOR_NOT_IN], true)) {
            throw new UnexpectedValueException(sprintf('The "%s" operator is not compatible with array values.', $operator));
        }

        $this->column   = $column;
        $this->operator = $operator;
        $this->value    = $value;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): ?array
    {
        return $this->value;
    }
}
