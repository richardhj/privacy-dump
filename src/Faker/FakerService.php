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

namespace Richardhj\PrivacyDump\Faker;

use Faker\Factory;
use Faker\Generator;

class FakerService
{
    private $generator;
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = $options + [
            'locale' => Factory::DEFAULT_LOCALE,
        ];
    }

    /**
     * Get the Faker generator.
     */
    public function getGenerator(): Generator
    {
        if (null === $this->generator) {
            $this->generator = Factory::create($this->options['locale']);
        }

        return $this->generator;
    }
}
