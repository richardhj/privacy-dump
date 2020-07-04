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

namespace Richardhj\PrivacyDump\Dumper\Mysqldump;

use Ifsnop\Mysqldump\Mysqldump;
use Richardhj\PrivacyDump\Converter\ConverterFactory;
use Richardhj\PrivacyDump\Dumper\Config\DumperConfig;

class DataConverterExtension implements ExtensionInterface
{
    private $converterFactory;
    private $config;
    private $context;
    private $converters;
    private $skipConditions;

    public function __construct(DumperConfig $config, ConverterFactory $converterFactory, array $context = [])
    {
        $this->config           = $config;
        $this->converterFactory = $converterFactory;
        $this->context          = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Mysqldump $dumper)
    {
        if (null === $this->converters) {
            $this->prepareConverters();
        }

        $dumper->setTransformTableRowHook($this->getHook());
    }

    private function getHook(): callable
    {
        return function (string $table, array $row): array {
            // Please keep in mind that this method must be as fast as possible
            // Every micro-optimization counts, this method can be executed billions of times
            // In this part of the code, abstraction layers should be avoided at all costs
            if (!isset($this->converters[$table])) {
                return $row;
            }

            // Initialize the context data
            $context = $this->context;

            $context['row_data']       = $row;
            $context['processed_data'] = [];

            // Evaluate the skip condition (done after context initialization as it may depend on it)
            if (isset($this->skipConditions[$table]) && eval($this->skipConditions[$table])) {
                return $row;
            }

            foreach ($this->converters[$table] as $column => $converter) {
                // Skip conversion if the column does not exist or the value is null
                if (!isset($row[$column])) {
                    continue;
                }

                // Convert the value
                $row[$column] = $converter->convert($row[$column], $context);

                $context['processed_data'][$column] = $row[$column];
            }

            return $row;
        };
    }

    private function prepareConverters(): void
    {
        $this->converters     = [];
        $this->skipConditions = [];

        foreach ($this->config->getTablesConfig() as $tableName => $tableConfig) {
            foreach ($tableConfig->getConverters() as $columnName => $definition) {
                $this->converters[$tableName][$columnName] = $this->converterFactory->create($definition);
            }

            $skipCondition = $tableConfig->getSkipCondition();
            if ('' !== $skipCondition) {
                $this->skipConditions[$tableName] = $skipCondition;
            }
        }
    }
}
