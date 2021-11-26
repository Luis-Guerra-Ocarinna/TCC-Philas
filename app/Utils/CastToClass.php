<?php

namespace App\Utils;

if (!function_exists(__NAMESPACE__ . 'casttoclass')) {
  function casttoclass(object|array $var, string $className) {
    if (!class_exists($className))
      throw new \Exception('Class ' . $className . ' does not exist');

    if ($var instanceof $className) return $var;

    $vs = serialize($var); # varSerilized

    $p = strstr((gettype($var) == 'object') ? strstr($vs, '"') : $vs, ':'); # propertys

    return unserialize(sprintf('O:%d:"%s"%s', strlen($className), $className, $p));
  }
}
