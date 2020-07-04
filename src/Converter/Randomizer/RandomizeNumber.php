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

namespace Richardhj\PrivacyDump\Converter\Randomizer;

use Richardhj\PrivacyDump\Converter\ConverterInterface;

class RandomizeNumber implements ConverterInterface
{
    private $replaceCallback;

    public function __construct()
    {
        $this->replaceCallback = static function (): int {
            return random_int(0, 9);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        return preg_replace_callback('/\d/', $this->replaceCallback, $value);
    }
}
