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

class AnonymizeNumber extends AnonymizeText
{
    /**
     * {@inheritdoc}
     */
    public function convert($value, array $context = [])
    {
        $isFirstCharacter = true;

        foreach (str_split((string) $value) as $index => $char) {
            if (!is_numeric($char)) {
                $isFirstCharacter = true;
                continue;
            }

            if ($isFirstCharacter) {
                $isFirstCharacter = false;
                continue;
            }

            $value[$index] = '*';
        }

        return $value;
    }
}
