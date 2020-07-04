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

use Richardhj\PrivacyDump\Converter\ConverterInterface;

class ToUpper implements ConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        return \extension_loaded('mbstring') ? mb_strtoupper((string) $value, 'UTF-8') : strtoupper((string) $value);
    }
}
