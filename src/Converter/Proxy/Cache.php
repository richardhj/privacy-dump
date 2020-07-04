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

namespace Richardhj\PrivacyDump\Converter\Proxy;

use InvalidArgumentException;
use Richardhj\PrivacyDump\Converter\ConverterInterface;
use UnexpectedValueException;

class Cache implements ConverterInterface
{
    private static $values;
    private $cacheKey;
    private $converter;

    /**
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function __construct(array $parameters)
    {
        if (!isset($parameters['converter'])) {
            throw new InvalidArgumentException('The parameter "converter" is required.');
        }

        if (!\array_key_exists('cache_key', $parameters)) {
            throw new InvalidArgumentException('The parameter "cache_key" is required.');
        }

        $this->converter = $parameters['converter'];
        $this->cacheKey  = (string) $parameters['cache_key'];

        if ('' === $this->cacheKey) {
            throw new UnexpectedValueException('The parameter "cache_key" must not be empty.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        if (!isset(static::$values[$this->cacheKey][$value])) {
            static::$values[$this->cacheKey][$value] = $this->converter->convert($value, $context);
        }

        return static::$values[$this->cacheKey][$value];
    }
}
