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

use DateTime;
use Richardhj\PrivacyDump\Converter\ConverterInterface;
use UnexpectedValueException;

class RandomizeDate implements ConverterInterface
{
    protected $format  = 'Y-m-d';
    protected $minYear = 1900;
    protected $maxYear;
    protected $date;

    /**
     * @throws UnexpectedValueException
     */
    public function __construct(array $parameters = [])
    {
        $this->date = new DateTime();

        if (\array_key_exists('format', $parameters)) {
            $this->format = (string) $parameters['format'];

            if ('' === $this->format) {
                throw new UnexpectedValueException('The parameter "replacement" must not be empty.');
            }
        }

        // Min year is the current year if the parameter is set to null, 1900 if the parameter is not defined
        if (\array_key_exists('min_year', $parameters)) {
            $this->minYear = (int) ($parameters['min_year'] ?? $this->date->format('Y'));
        }

        // Max year is the current year if the parameter is not defined or set to null
        $this->maxYear = (int) ($parameters['max_year'] ?? $this->date->format('Y'));

        if ($this->minYear > $this->maxYear) {
            throw new UnexpectedValueException('The parameter "min_year" must be lower than the parameter "max_year".');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        $this->randomizeDate();

        return $this->date->format($this->format);
    }

    /**
     * Randomize the date.
     */
    protected function randomizeDate()
    {
        // Randomize the year, month and day
        $year  = random_int($this->minYear, $this->maxYear);
        $month = random_int(1, 12);
        $day   = random_int(1, 31);

        // Replace the values
        $this->date->setDate($year, $month, $day);
    }
}
