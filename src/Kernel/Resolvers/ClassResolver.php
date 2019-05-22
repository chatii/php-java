<?php
namespace PHPJava\Kernel\Resolvers;

use PHPJava\Core\JavaArchive;
use PHPJava\Core\JavaFileClass;
use PHPJava\Core\JVM\Parameters\Runtime;
use PHPJava\Core\Stream\Reader\FileReader;
use PHPJava\Core\Stream\Reader\ReaderInterface;
use PHPJava\Packages\java\lang\ClassNotFoundException;

class ClassResolver
{
    const MAPS = Runtime::PHP_PACKAGES_MAPS;

    // resource types
    const RESOURCE_TYPE_FILE = 'RESOURCE_TYPE_FILE';
    const RESOURCE_TYPE_JAR = 'RESOURCE_TYPE_JAR';
    const RESOURCE_TYPE_CLASS = 'RESOURCE_TYPE_CLASS';
    const RESOURCE_TYPE_INNER_CLASS = 'RESOURCE_TYPE_INNER_CLASS';

    // resolved types
    const RESOLVED_TYPE_CLASS = 'RESOLVED_TYPE_CLASS';
    const RESOLVED_TYPE_PACKAGES = 'RESOLVED_TYPE_PACKAGES';

    /**
     * @var array
     */
    private $resolves = [];

    /**
     * @var (string|JavaFileClass)[][]
     */
    private $resolvedPaths = [];

    /**
     * @var array
     */
    private $options = [];

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function resolve(string $javaPath, JavaFileClass $class = null, bool $instantiation = true): array
    {
        $javaPath = str_replace('/', '.', $javaPath);
        $namespaces = explode('.', $javaPath);
        $buildClassPath = [];
        foreach ($namespaces as $namespace) {
            $buildClassPath[] = static::MAPS[$namespace] ?? $namespace;
        }

        // resolve something approaching
        $relativePath = implode('/', $namespaces);

        foreach ($this->resolves as [$resourceType, $value]) {
            switch ($resourceType) {
                case static::RESOURCE_TYPE_INNER_CLASS:
                    // TODO: Implement here
                    break;
                case static::RESOURCE_TYPE_JAR:
                    /**
                     * @var JavaArchive $value
                     */
                    try {
                        return $this->resolvedPaths[] = [
                            static::RESOLVED_TYPE_CLASS,
                            $value->getClassByName($relativePath),
                        ];
                    } catch (ClassNotFoundException $e) {
                    }
                    break;
                case static::RESOURCE_TYPE_FILE:
                    $path = realpath($value . '/' . $relativePath . '.class');
                    if ($path === false) {
                        break;
                    }
                    if (!$instantiation) {
                        foreach ($this->resolvedPaths as [$resolvedPath, $class]) {
                            if ($relativePath === $class->getClassName()) {
                                return [$resolvedPath, $class];
                            }
                        }
                    }
                    /**
                     * @var JavaFileClass $initiatedClass
                     */
                    if (is_file($path)) {
                        $initiatedClass = new JavaFileClass(
                            new FileReader($path),
                            $this->options
                        );
                        if (strpos($relativePath, '$') !== false && $class !== null) {
                            $initiatedClass->setParentClass($class);
                        }
                        return $this->resolvedPaths[] = [
                            static::RESOLVED_TYPE_CLASS,
                            $initiatedClass,
                        ];
                    }
                    break;
                case static::RESOURCE_TYPE_CLASS:
                    if (!$instantiation) {
                        foreach ($this->resolvedPaths as [$resolvedPath, $class]) {
                            if ($value->getClassName() === $class->getClassName()) {
                                return [$resolvedPath, $class];
                            }
                        }
                    }

                    /**
                     * @var ReaderInterface $value
                     */
                    try {
                        return $this->resolvedPaths[] = [
                            static::RESOLVED_TYPE_CLASS,
                            new JavaFileClass(
                                $value,
                                $this->options
                            ),
                        ];
                    } catch (ClassNotFoundException $e) {
                    }
                    break;
            }
        }

        $className = Runtime::PHP_PACKAGES_DIRECTORY . '\\' . implode('\\', $buildClassPath);

        if (!class_exists($className)) {
            throw new ClassNotFoundException(
                str_replace(
                    [Runtime::PHP_PACKAGES_DIRECTORY . '\\', '\\'],
                    ['', '.'],
                    $className
                ) . ' class does not exist.'
            );
        }

        return [
            static::RESOLVED_TYPE_PACKAGES,
            $className,
        ];
    }

    public function add($valuesOrResourceType = self::RESOURCE_TYPE_FILE, $value = null): void
    {
        if (is_array($valuesOrResourceType)) {
            foreach ($valuesOrResourceType as [$resourceType, $value]) {
                $this->add($resourceType, $value);
            }
            return;
        }
        if (in_array([$valuesOrResourceType, $value], $this->resolves, true)) {
            return;
        }
        $this->resolves[] = [$valuesOrResourceType, $value];
    }

    public static function resolveNameByPath($path): string
    {
        $names = explode(
            '.',
            str_replace(
                [Runtime::PHP_PACKAGES_DIRECTORY . '\\', '\\'],
                ['', '.'],
                '\\' . ltrim(get_class($path))
            )
        );

        $string = [];

        foreach ($names as $name) {
            $string[] = array_flip(static::MAPS)[$name] ?? $name;
        }

        return implode('.', $string);
    }
}
