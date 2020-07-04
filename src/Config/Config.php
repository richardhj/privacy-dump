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

namespace Richardhj\PrivacyDump\Config;

final class Config
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Key can be one of:
     * - database: Database connection params and config
     * - tables: Export and converter configuration
     * - tables_include: Table names to include exclusively
     * - tables_exclude: Table names to exclude
     * - dump: Dump settings, can be any of DumperConfig::$dumpSettings.
     */
    public function get(string $key)
    {
        return $this->config[$key] ?? null;
    }

    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }

    public function toArray(): array
    {
        return $this->config;
    }

    public function merge(array $data): self
    {
        $config = $this->mergeArray($this->config, $data);

        return new self($config);
    }

    private function mergeArray(array $data, array $override): array
    {
        foreach ($override as $key => $value) {
            if (\array_key_exists($key, $data) && \is_array($value) && \is_array($data[$key])) {
                $data[$key] = $this->mergeArray($data[$key], $value);
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
