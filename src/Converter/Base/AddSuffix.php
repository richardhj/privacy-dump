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

class AddSuffix implements ConverterInterface
{
    private $suffix;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $parameters = [])
    {
        if (!\array_key_exists('suffix', $parameters)) {
            throw new InvalidArgumentException('The parameter "suffix" is required.');
        }

        $this->suffix = (string) $parameters['suffix'];
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        return $value.$this->suffix;
    }
}
