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

use Doctrine\DBAL\Query\QueryBuilder;
use Ifsnop\Mysqldump\Mysqldump;
use Richardhj\PrivacyDump\Database\Database;
use Richardhj\PrivacyDump\Database\Metadata\Definition\Constraint\ForeignKey;
use Richardhj\PrivacyDump\Database\TableDependencyResolver;
use Richardhj\PrivacyDump\Dumper\Config\DumperConfig;
use Richardhj\PrivacyDump\Dumper\Config\Table\Filter\Filter;
use Richardhj\PrivacyDump\Dumper\Config\Table\TableConfig;
use UnexpectedValueException;

class TableFilterExtension implements ExtensionInterface
{
    private $connection;
    private $metadata;
    private $config;

    public function __construct(Database $database, DumperConfig $config)
    {
        $this->connection = $database->getConnection();
        $this->metadata   = $database->getMetadata();
        $this->config     = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Mysqldump $dumper)
    {
        $dumper->setTableWheres($this->buildTablesWhere());
    }

    /**
     * Get the query as SQL, starting from the WHERE clause.
     */
    public function getWhereSql(QueryBuilder $queryBuilder): string
    {
        $wherePart = $queryBuilder->getQueryPart('where');

        if (empty($wherePart)) {
            $queryBuilder->where(1);
        }

        $sql = $queryBuilder->getSQL();

        return substr($sql, strpos($sql, ' WHERE ') + 7);
    }

    /**
     * Get the filters to apply on each table.
     */
    private function buildTablesWhere(): array
    {
        // Get the tables to sort/filter
        $tablesToFilter = $this->config->getTablesToFilter();
        $tablesToSort   = $this->config->getTablesToSort();

        // Do nothing if there is no sort order/filter defined in the configuration
        if (empty($tablesToFilter) && empty($tablesToSort)) {
            return [];
        }

        // Get the foreign keys of each table that depends on the filters listed in the configuration
        $dependencies = (new TableDependencyResolver($this->metadata))->getTablesDependencies($tablesToFilter);

        // Tables to query are:
        // - tables with filters or sort orders declared in the config
        // - tables that depend on the tables to filter
        $tablesToQuery = array_unique(array_merge(array_keys($dependencies), $tablesToFilter, $tablesToSort));
        $tableWheres   = [];

        foreach ($tablesToQuery as $tableName) {
            // Create the query that will contain a combination of filter / sort order / limit
            $queryBuilder = $this->createQueryBuilder($tableName);

            // Add where conditions on the parent tables that also have active filters
            if (\array_key_exists($tableName, $dependencies) && 0 !== $queryBuilder->getMaxResults()) {
                $this->addDependentFilter($tableName, $queryBuilder, $dependencies);
            }

            // Convert the query to SQL, starting from the "WHERE" clause
            $tableWheres[$tableName] = $this->getWhereSql($queryBuilder);
        }

        // Sort by table name (easier to debug)
        ksort($tableWheres);

        return $tableWheres;
    }

    /**
     * Add a filter on the dependent tables.
     *
     * @param array $dependencies
     * @param int   $subQueryCount
     */
    private function addDependentFilter(
        string $tableName,
        QueryBuilder $queryBuilder,
        &$dependencies,
        &$subQueryCount = 0
    ) {
        /** @var ForeignKey $dependency */
        foreach ($dependencies[$tableName] as $dependency) {
            $tableName = $dependency->getForeignTableName();

            $qb = $this->createQueryBuilder($tableName);
            $qb->select($this->getColumnsSql($dependency->getForeignColumns()));

            // Recursively add condition on parent tables
            if (\array_key_exists($tableName, $dependencies) && 0 !== $qb->getMaxResults()) {
                $this->addDependentFilter($tableName, $qb, $dependencies, $subQueryCount);
            }

            // Prepare the condition data
            ++$subQueryCount;
            $subQueryName = $this->connection->quoteIdentifier('sub_'.$subQueryCount);
            $columnsSql   = $this->getColumnsSql($dependency->getLocalColumns());

            // Filter on the foreign keys
            // (wrap the sub query in a SELECT * FROM (...),
            // because otherwise a sub query cannot declare the LIMIT clause)
            $expr = $queryBuilder
                ->expr()
                ->comparison($columnsSql, 'IN', '(SELECT * FROM ('.$qb.') '.$subQueryName.')');

            // Allow null values
            foreach ($dependency->getLocalColumns() as $column) {
                $expr = $queryBuilder
                    ->expr()
                    ->orX($expr, $qb->expr()->isNull($this->connection->quoteIdentifier($column)));
            }

            $queryBuilder->andWhere($expr);
        }
    }

    /**
     * Create a query builder that applies the configuration of the specified table.
     */
    private function createQueryBuilder(string $tableName): QueryBuilder
    {
        // Create the query builder
        $queryBuilder = $this->connection->createQueryBuilder();

        // Select all columns of the table
        $queryBuilder->select('*')
            ->from($this->connection->quoteIdentifier($tableName));

        // Get the table configuration
        $tableConfig = $this->config->getTableConfig($tableName);
        if (null !== $tableConfig) {
            $this->applyTableConfigToQueryBuilder($queryBuilder, $tableConfig);
        }

        return $queryBuilder;
    }

    private function applyTableConfigToQueryBuilder(QueryBuilder $queryBuilder, TableConfig $tableConfig): void
    {
        // Apply filters
        foreach ($tableConfig->getFilters() as $filter) {
            $value = $this->getFilterValue($filter);

            $whereExpr = \call_user_func(
                [$queryBuilder->expr(), $filter->getOperator()],
                $this->connection->quoteIdentifier($filter->getColumn()),
                $value
            );

            $queryBuilder->andWhere($whereExpr);
        }

        // Apply sort orders
        foreach ($tableConfig->getSortOrders() as $sortOrder) {
            $queryBuilder->addOrderBy(
                $this->connection->quoteIdentifier($sortOrder->getColumn()),
                $sortOrder->getDirection()
            );
        }

        // Apply limit
        $limit = $tableConfig->getLimit();
        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }
    }

    /**
     * Get the SQL query that represents a list of columns.
     */
    private function getColumnsSql(array $columns): string
    {
        foreach ($columns as $index => $column) {
            $columns[$index] = $this->connection->quoteIdentifier($column);
        }

        $result = implode(',', $columns);

        if (\count($columns) > 1) {
            $result = '('.$result.')';
        }

        return $result;
    }

    private function getFilterValue(Filter $filter)
    {
        $value = $filter->getValue();

        if (\is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->quoteValue($v);
            }

            return $value;
        }

        return $this->quoteValue($value);
    }

    /**
     * Quote a value so that it can be safely injected in a SQL query.
     * (we can't use query params because Mysqldump library doesn't allow it).
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function quoteValue($value)
    {
        if (null !== $value && !is_scalar($value)) {
            throw new UnexpectedValueException('Non-scalar values can not be used in filters.');
        }

        if (\is_bool($value)) {
            return (int) $value;
        }

        if (\is_string($value)) {
            return 0 === strpos($value, 'expr:') ? ltrim(substr($value, 5)) : $this->connection->quote($value);
        }

        return $value;
    }
}
