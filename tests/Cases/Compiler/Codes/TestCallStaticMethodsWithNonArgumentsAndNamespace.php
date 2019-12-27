<?php
namespace PHPJava\CompilerMethodCallTest;

class TestCallStaticMethodsWithNonArgumentsAndNamespace
{
    public static function doSomethingMethod()
    {
        echo 'Hello World!';
    }

    /**
     * @param \PHPJava\Packages\java\lang\_String[] $args
     */
    public static function main($args)
    {
        self::doSomethingMethod();
    }
}
