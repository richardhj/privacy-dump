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

namespace Richardhj\PrivacyDump\Dumper\Config\Table;

use Richardhj\PrivacyDump\Converter\ConditionBuilder;
use Richardhj\PrivacyDump\Dumper\Config\Table\Filter\Filter;
use Richardhj\PrivacyDump\Dumper\Config\Table\Filter\SortOrder;
use UnexpectedValueException;

class TableConfig
{
    private $name;
    private $filters    = [];
    private $sortOrders = [];
    private $limit;
    private $converters    = [];
    private $skipCondition = '';

    public function __construct(string $tableName, array $tableConfig)
    {
        $this->name = $tableName;
        $this->prepareConfig($tableConfig);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getSortOrders(): array
    {
        return $this->sortOrders;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getConverters(): array
    {
        return $this->converters;
    }

    public function hasFilter(): bool
    {
        return !empty($this->filters);
    }

    public function hasSortOrder(): bool
    {
        return !empty($this->sortOrders);
    }

    public function hasLimit(): bool
    {
        return null !== $this->limit;
    }

    public function getSkipCondition(): string
    {
        return $this->skipCondition;
    }

    private function prepareConfig(array $tableData): void
    {
        $this->prepareFilters($tableData);
        $this->prepareSortOrder($tableData);
        $this->prepareLimit($tableData);
        $this->prepareConverters($tableData);
    }

    private function prepareFilters(array $tableData): void
    {
        if (isset($tableData['filters'])) {
            foreach ($tableData['filters'] as $filter) {
                $this->filters[] = new Filter((string) $filter[0], (string) $filter[1], $filter[2] ?? null);
            }
        }
    }

    private function prepareSortOrder(array $tableData): void
    {
        $orderBy = (string) ($tableData['orderBy'] ?? '');
        if ('' === $orderBy) {
            return;
        }

        $orders = explode(',', $orderBy);
        $orders = array_map('trim', $orders);

        foreach ($orders as $order) {
            $parts = explode(' ', $order);

            if (\count($parts) > 2) {
                throw new UnexpectedValueException(sprintf('The sort order "%s" is not valid.', $order));
            }

            $column    = $parts[0];
            $direction = $parts[1] ?? SortOrder::DIRECTION_ASC;

            $this->sortOrders[] = new SortOrder($column, $direction);
        }
    }

    private function prepareLimit(array $tableData): void
    {
        if (isset($tableData['limit']) && $tableData['limit'] > 0) {
            $this->limit = (int) $tableData['limit'];
        }

        if (isset($tableData['truncate']) && $tableData['truncate']) {
            $this->limit = 0;
        }
    }

    private function prepareConverters(array $tableData): void
    {
        if (isset($tableData['converters'])) {
            foreach ($tableData['converters'] as $column => $converterData) {
                // Converter data will be validated by the factory during the object creation
                $this->converters[$column] = $converterData;
            }
        }

        $skipCondition = (string) ($tableData['skip_conversion_if'] ?? '');
        if ('' !== $skipCondition) {
            $conditionBuilder    = new ConditionBuilder();
            $this->skipCondition = $conditionBuilder->build($skipCondition);
        }
    }
}
