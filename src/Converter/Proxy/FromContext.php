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

class FromContext implements ConverterInterface
{
    private $key;

    /**
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function __construct(array $parameters = [])
    {
        if (!\array_key_exists('key', $parameters)) {
            throw new InvalidArgumentException('The parameter "key" is required.');
        }

        $this->key = (string) $parameters['key'];

        if ('' === $this->key) {
            throw new UnexpectedValueException('The parameter "key" must not be empty.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        return ArrayHelper::getPath($context, $this->key);
    }
}
