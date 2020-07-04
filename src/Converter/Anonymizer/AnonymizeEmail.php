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

use UnexpectedValueException;

class AnonymizeEmail extends AnonymizeText
{
    private $domains = [
        'example.com',
        'example.net',
        'example.org',
    ];

    private $domainsCount;

    /**
     * @throws UnexpectedValueException
     */
    public function __construct(array $parameters = [])
    {
        if (\array_key_exists('domains', $parameters)) {
            if (!\is_array($parameters['domains'])) {
                throw new UnexpectedValueException('The parameter "domains" must be an array.');
            }

            if (empty($parameters['domains'])) {
                throw new UnexpectedValueException('The parameter "domains" must not be empty.');
            }

            $this->domains = $parameters['domains'];
        }

        $this->domainsCount = \count($this->domains);
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        $parts = explode('@', $value);

        if (isset($parts[0])) {
            $parts[0] = parent::convert($parts[0]);
        }

        // Replace the email domain
        if (isset($parts[1])) {
            $index    = random_int(0, $this->domainsCount - 1);
            $parts[1] = $this->domains[$index];
        }

        return implode('@', $parts);
    }
}
