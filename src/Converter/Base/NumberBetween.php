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

namespace Richardhj\PrivacyDump\Converter\Base;

use InvalidArgumentException;
use Richardhj\PrivacyDump\Converter\ConverterInterface;
use UnexpectedValueException;

class NumberBetween implements ConverterInterface
{
    private $min;
    private $max;

    /**
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function __construct(array $parameters = [])
    {
        if (!\array_key_exists('min', $parameters)) {
            throw new InvalidArgumentException('The parameter "min" is required.');
        }

        if (!\array_key_exists('max', $parameters)) {
            throw new InvalidArgumentException('The parameter "max" is required.');
        }

        if ($parameters['min'] > $parameters['max']) {
            throw new UnexpectedValueException('The parameter "min" must be lower than the parameter "max".');
        }

        $this->min = (int) $parameters['min'];
        $this->max = (int) $parameters['max'];
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        return random_int($this->min, $this->max);
    }
}
