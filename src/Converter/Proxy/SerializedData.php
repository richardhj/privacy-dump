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
use Richardhj\PrivacyDump\Converter\Helper\ArrayHelper;
use UnexpectedValueException;

class SerializedData implements ConverterInterface
{
    private $converters;

    /**
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function __construct(array $parameters)
    {
        if (!\array_key_exists('converters', $parameters)) {
            throw new InvalidArgumentException('The parameter "converters" is required.');
        }

        if (!\is_array($parameters['converters'])) {
            throw new UnexpectedValueException('The parameter "converters" must be an array.');
        }

        if (empty($parameters['converters'])) {
            throw new UnexpectedValueException('The parameter "converters" must not be empty.');
        }

        $this->converters = $parameters['converters'];
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        $decoded = @unserialize($value);

        if (!\is_array($decoded)) {
            return $value;
        }

        /** @var ConverterInterface $converter */
        foreach ($this->converters as $path => $converter) {
            // Get the value
            $nestedValue = ArrayHelper::getPath($decoded, $path);
            if (null === $nestedValue) {
                continue;
            }

            // Format the value
            $nestedValue = $converter->convert($nestedValue, $context);

            // Replace the original value in the JSON by the converted value
            ArrayHelper::setPath($decoded, $path, $nestedValue);
        }

        return serialize($decoded);
    }
}
