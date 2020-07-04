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

namespace Richardhj\PrivacyDump\Converter\Proxy;

use InvalidArgumentException;
use Richardhj\PrivacyDump\Converter\ConditionBuilder;
use Richardhj\PrivacyDump\Converter\ConverterInterface;
use UnexpectedValueException;

class Conditional implements ConverterInterface
{
    private $condition;
    private $ifTrueConverter;
    private $ifFalseConverter;

    /**
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function __construct(array $parameters)
    {
        if (!\array_key_exists('condition', $parameters)) {
            throw new InvalidArgumentException('The parameter "condition" is required.');
        }

        $condition = (string) $parameters['condition'];
        if ('' === $condition) {
            throw new UnexpectedValueException('The parameter "condition" must not be empty.');
        }

        if (!isset($parameters['if_true_converter']) && !isset($parameters['if_false_converter'])) {
            throw new InvalidArgumentException('The conditional converter requires a "if_true_converter" and/or "if_false_converter" parameter.');
        }

        $this->condition  = (new ConditionBuilder())->build($condition);

        if (isset($parameters['if_true_converter'])) {
            $this->ifTrueConverter = $parameters['if_true_converter'];
        }

        if (isset($parameters['if_false_converter'])) {
            $this->ifFalseConverter = $parameters['if_false_converter'];
        }
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.EvalExpression)
     */
    public function convert($value, array $context = [])
    {
        $result = (bool) eval($this->condition);

        if ($result) {
            if (null !== $this->ifTrueConverter) {
                $value = $this->ifTrueConverter->convert($value, $context);
            }
        } elseif (null !== $this->ifFalseConverter) {
            $value = $this->ifFalseConverter->convert($value, $context);
        }

        return $value;
    }
}
