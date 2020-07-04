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
use UnexpectedValueException;

class RandomizeText implements ConverterInterface
{
    private $minLength    = 3;
    private $replacements = '0123456789abcdefghijklmnopqrstuvwxyz';
    private $replacementsCount;

    /**
     * @throws UnexpectedValueException
     */
    public function __construct(array $parameters = [])
    {
        if (\array_key_exists('min_length', $parameters)) {
            $this->minLength = (int) $parameters['min_length'];
        }

        if (\array_key_exists('replacements', $parameters)) {
            $this->replacements = (string) $parameters['replacements'];

            if ('' === $this->replacements) {
                throw new UnexpectedValueException('The parameter "replacements" must not be empty.');
            }
        }

        $this->replacementsCount = \strlen($this->replacements);
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        $length = \strlen((string) $value);
        $value  = '';

        if ($length < $this->minLength) {
            $length = $this->minLength;
        }

        for ($index = 0; $index < $length; ++$index) {
            $replacementIndex = random_int(0, $this->replacementsCount - 1);
            $value .= $this->replacements[$replacementIndex];
        }

        return $value;
    }
}
