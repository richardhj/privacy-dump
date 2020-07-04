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

namespace Richardhj\PrivacyDump\Converter\Anonymizer;

use DateTime;
use Richardhj\PrivacyDump\Converter\ConverterInterface;
use UnexpectedValueException;

class AnonymizeDate implements ConverterInterface
{
    /**
     * @var string
     */
    protected $format = 'Y-m-d';

    /**
     * @throws UnexpectedValueException
     */
    public function __construct(array $parameters = [])
    {
        if (\array_key_exists('format', $parameters)) {
            $this->format = (string) $parameters['format'];

            if ('' === $this->format) {
                throw new UnexpectedValueException('The parameter "format" must not be empty.');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        $date = DateTime::createFromFormat($this->format, $value);
        if (false === $date) {
            throw new UnexpectedValueException(sprintf('Failed to convert the value "%s" to a date.', $value));
        }

        $this->anonymizeDate($date);

        return $date->format($this->format);
    }

    /**
     * Anonymize a date, by randomizing the day and month.
     */
    protected function anonymizeDate(DateTime $date)
    {
        // Get the year, month and day
        $year  = (int) $date->format('Y');
        $month = (int) $date->format('n');
        $day   = (int) $date->format('j');

        // Randomize the month and day
        do {
            $randomMonth = random_int(1, 12);
            $randomDay   = random_int(1, 31);
        } while ($randomMonth === $month && $randomDay === $day);

        // Replace the values
        $date->setDate($year, $randomMonth, $randomDay);
    }
}
