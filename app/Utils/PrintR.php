<?php

namespace App\Utils;

if (!function_exists(__NAMESPACE__ . 'better')) {
  function better(mixed $value): string {
    $return = '';

    switch (gettype($value)) {
      case 'array':
        if (count($value) == 0) $return .= better($value[0]);

        $comma = 0;
        foreach ($value as $k => $v) {
          $return .= ($comma ? ', ' : '') . "[$k => " . better($v) . "]";
          $comma++;
        }
        break;
      case 'boolean':
        $return .= $value ? 'true' : 'false';
        break;
      case 'double':
      case 'integer':
        $return .= $value;
        break;
      case 'NULL':
        $return .= 'null';
        break;
      case 'object':
        $return .= $value::class;
        break;
      case 'resource':
        $return .= '?resource?';
        break;
      case 'string':
        $value = (strlen($value) > 100) ? substr($value, 0, 100) . '...' : $value;
        $return .= "'$value'";
        break;
      default:
        $return .= '?unknown?';
    }

    return $return;
  }
}
