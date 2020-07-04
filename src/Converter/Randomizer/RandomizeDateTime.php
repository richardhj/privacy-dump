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

class RandomizeDateTime extends RandomizeDate
{
    /**
     * {@inheritdoc}
     */
    protected $format = 'Y-m-d H:i:s';

    /**
     * {@inheritdoc}
     */
    protected function randomizeDate()
    {
        // Randomize the year, month and day
        parent::randomizeDate();

        // Randomize the hour, minute and second
        $hour   = random_int(0, 23);
        $minute = random_int(0, 59);
        $second = random_int(0, 59);

        // Replace the values
        $this->date->setTime($hour, $minute, $second);
    }
}
