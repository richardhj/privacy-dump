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

class SetValue implements ConverterInterface
{
    private $value;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $parameters)
    {
        if (!\array_key_exists('value', $parameters)) {
            throw new InvalidArgumentException('The parameter "value" is required.');
        }

        $this->value = $parameters['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        return $this->value;
    }
}
