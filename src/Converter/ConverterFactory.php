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

namespace Richardhj\PrivacyDump\Converter;

use ReflectionClass;
use ReflectionException;
use Richardhj\PrivacyDump\Converter\Proxy\Cache;
use Richardhj\PrivacyDump\Converter\Proxy\Conditional;
use Richardhj\PrivacyDump\Converter\Proxy\Unique;
use Richardhj\PrivacyDump\Faker\FakerService;
use RuntimeException;
use UnexpectedValueException;

class ConverterFactory
{
    private $faker;

    /**
     * e.g. ['unique' => 'Richardhj\PrivacyDump\Converter\Proxy\Unique', ...].
     *
     * @var string[]
     */
    private $classNames;

    public function __construct(FakerService $faker)
    {
        $this->faker = $faker;
    }

    /**
     * Create a converter from a definition.
     * It can be either a string that represents the converter name,
     * or an array that represents the converter data.
     */
    public function create(array $definition): ConverterInterface
    {
        $definition = $this->getConverterData($definition);

        // If converter is disabled, return a dummy converter
        if ($definition['disabled']) {
            return new Dummy();
        }

        // Get the converter name and parameters
        $name       = $definition['converter'];
        $parameters = $definition['parameters'];

        // Create the converter
        $converter = $this->createConverter($name, $parameters);

        // Generate only unique values
        if ($definition['unique']) {
            $converter = new Unique(['converter' => $converter]);
        }

        if ('' !== $definition['cache_key']) {
            $converter = new Cache(['converter' => $converter, 'cache_key' => $definition['cache_key']]);
        }

        // Convert data only if it matches the specified condition
        if ('' !== $definition['condition']) {
            $converter = new Conditional([
                'condition'         => $definition['condition'],
                'if_true_converter' => $converter,
            ]);
        }

        return $converter;
    }

    /**
     * Get the converter data.
     *
     * @throws UnexpectedValueException
     */
    private function getConverterData(array $definition): array
    {
        if (!\array_key_exists('converter', $definition)) {
            throw new UnexpectedValueException('The converter name is required.');
        }

        if (\array_key_exists('parameters', $definition) && !\is_array($definition['parameters'])) {
            throw new UnexpectedValueException('The converter parameters must be an array.');
        }

        $definition['converter'] = (string) $definition['converter'];
        if ('' === $definition['converter']) {
            throw new UnexpectedValueException('The converter name is required.');
        }

        $definition += [
            'parameters' => [],
            'condition'  => '',
            'cache_key'  => '',
            'unique'     => false,
            'disabled'   => false,
        ];

        // Parse the parameters
        $definition['parameters'] = $this->parseParameters($definition['parameters']);

        // Cast values
        $definition['condition'] = (string) $definition['condition'];
        $definition['unique']    = (bool) $definition['unique'];
        $definition['cache_key'] = (string) $definition['cache_key'];
        $definition['disabled']  = (bool) $definition['disabled'];

        return $definition;
    }

    /**
     * Parse the converter parameters.
     *
     * @throws UnexpectedValueException
     */
    private function parseParameters(array $parameters): array
    {
        foreach ($parameters as $name => $value) {
            if ('converters' === $name || false !== strpos($name, '_converters')) {
                // Param is an array of converter definitions (e.g. "converters" param of the "chain" converter)
                $parameters[$name] = $this->parseConvertersParameter($name, $value);
                continue;
            }

            if ('converter' === $name || false !== strpos($name, '_converter')) {
                // Param is a converter definition (e.g. "converter" param of the "unique" converter
                $parameters[$name] = $this->parseConverterParameter($name, $value);
            }
        }

        return $parameters;
    }

    /**
     * Parse a parameter that defines an array of converter definitions.
     *
     * @param mixed $parameter
     *
     * @throws UnexpectedValueException
     *
     * @return ConverterInterface[]
     */
    private function parseConvertersParameter(string $name, $parameter): array
    {
        if (!\is_array($parameter)) {
            throw new UnexpectedValueException(sprintf('The parameter "%s" must be an array.', $name));
        }

        foreach ($parameter as $index => $definition) {
            $parameter[$index] = $this->parseConverterParameter($name.'['.$index.']', $definition);
        }

        return $parameter;
    }

    /**
     * Parse a parameter that defines a converter definition.
     *
     * @param mixed $parameter
     *
     * @throws UnexpectedValueException
     */
    private function parseConverterParameter(string $name, $parameter): ConverterInterface
    {
        if (!\is_array($parameter)) {
            throw new UnexpectedValueException(sprintf('The parameter "%s" must be an array.', $name));
        }

        return $this->create($parameter);
    }

    /**
     * Create a converter object from its name and parameters.
     *
     * @throws RuntimeException
     */
    private function createConverter(string $name, array $parameters = []): ConverterInterface
    {
        $className = $name;

        if (false === strpos($name, '\\')) {
            // Find class names of default converters
            $this->initClassNames();

            // Check if the converter is a class declared in this namespace
            if (\array_key_exists($name, $this->classNames)) {
                $className = $this->classNames[$name];
            }
        }

        if (!class_exists($className)) {
            throw new RuntimeException(sprintf('The converter class "%s" was not found.', $className));
        }

        // Faker parameter
        if ((Faker::class === $className || is_subclass_of($className, Faker::class)) && !isset($parameters['faker'])) {
            $parameters['faker'] = $this->faker->getGenerator();
        }

        return new $className($parameters);
    }

    /**
     * Initialize the converter name <-> class name array.
     */
    private function initClassNames()
    {
        if (null === $this->classNames) {
            $this->classNames = $this->findClassNames(__DIR__);
        }
    }

    /**
     * Get converter class names that reside in the specified directory.
     * e.g. ['unique' => 'Richardhj\PrivacyDump\Data\Converter\Proxy\Unique', ...].
     *
     * @throws ReflectionException
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function findClassNames(string $directory, string $baseDirectory = ''): array
    {
        $result = [];
        foreach (scandir($directory, SCANDIR_SORT_NONE) as $fileName) {
            if ('.' === $fileName || '..' === $fileName) {
                continue;
            }

            // Absolute path of the file
            $path = $directory.'/'.$fileName;

            if (is_dir($path)) {
                // Recursively find files in this directory
                $newBaseDirectory = ('' !== $baseDirectory) ? $baseDirectory.'/'.$fileName : $fileName;
                $result           = array_merge($result, $this->findClassNames($path, $newBaseDirectory));
            } else {
                // Remove the extension
                $fileName = pathinfo($fileName, PATHINFO_FILENAME);

                // Get the class name
                $className = 'Richardhj\PrivacyDump\Converter\\';
                $className .= ('' !== $baseDirectory)
                    ? str_replace('/', '\\', $baseDirectory).'\\'.$fileName
                    : $fileName;

                // Include only classes that implement the converter interface
                $reflection = new ReflectionClass($className);

                if ($reflection->isSubclassOf(ConverterInterface::class)) {
                    $result[lcfirst($fileName)] = $className;
                }
            }
        }

        return $result;
    }
}
