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

namespace Richardhj\PrivacyDump\Dumper\Config\Validation;

use TheSeer\Tokenizer\Tokenizer;

class QueryValidator
{
    private $tokenizer;
    private $statementBlacklist = [
        'grant', 'create', 'alter', 'drop', 'insert',
        'update', 'delete', 'truncate', 'replace',
        'prepare', 'execute',
    ];

    public function __construct()
    {
        $this->tokenizer = new Tokenizer();
    }

    public function validate(string $query): void
    {
        // Use a PHP tokenizer to split the query into tokens
        $tokens = $this->tokenizer->parse('<?php '.strtolower($query).'?>');

        foreach ($tokens as $token) {
            // If the token is a word, check if it contains a forbidden statement
            if ('T_STRING' === $token->getName() && \in_array($token->getValue(), $this->statementBlacklist, true)) {
                throw new ValidationException(sprintf('This query contains forbidden keywords: "%s".', $query));
            }
        }
    }
}
