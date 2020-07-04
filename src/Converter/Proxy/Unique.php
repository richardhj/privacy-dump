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
use OverflowException;
use Richardhj\PrivacyDump\Converter\ConverterInterface;

class Unique implements ConverterInterface
{
    private $converter;
    private $maxRetries;
    private $generated = [];

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $parameters)
    {
        if (!isset($parameters['converter'])) {
            throw new InvalidArgumentException('The parameter "converter" is required.');
        }

        $this->converter  = $parameters['converter'];
        $this->maxRetries = (int) ($parameters['maxRetries'] ?? 100);
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        $count = 0;

        do {
            $result = $this->converter->convert($value, $context);

            // Ignore null values
            if (null === $result) {
                return null;
            }

            ++$count;
            if ($count > $this->maxRetries) {
                throw new OverflowException(sprintf('Maximum retries of %d reached without finding a unique value.', $this->maxRetries));
            }

            $key = serialize($result);
        } while (\array_key_exists($key, $this->generated));

        $this->generated[$key] = null;

        return $result;
    }
}
