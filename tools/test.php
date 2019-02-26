<?php

use PHPJava\Core\JavaClassInvoker;
use PHPJava\Imitation\java\lang\ArrayIndexOutOfBoundsException;

require __DIR__ . '/../vendor/autoload.php';

$javaClass = new \PHPJava\Core\JavaClass(
    new \PHPJava\Core\JavaClassReader(__DIR__ . '/Test.class')
);

$javaClass->getInvoker()->getStaticFields()
    ->set('c', 100)
    ->set('b', 'New String');

// var_dump($javaClass->getInvoker()->getDynamicMethods()->testMe(1111, 'いかりのねこ', 3333, 4444, 5555, 7777));
$javaClass->getInvoker()->getStaticMethods()->call(
    'main',
    [99999, 55555, 333333]
);
//
//var_dump($javaClass->getInvoker()->getStaticFields()->get('c'));
//var_dump($javaClass->getInvoker()->getStaticFields()->get('b'));

$javaClass->debug();
