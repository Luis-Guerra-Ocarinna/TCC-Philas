<?php

namespace App\Utils;

/**
 * Classe responsável por controlar Arrays
 */
class Arrays {

  /**
   * Método responsável por combinar arrays corretamente
   *
   * @param   array  $main
   * @param   array  ...$tomerge
   *
   * @return  bool
   */
  public static function mergeRight(array &$main, array ...$tomerge): bool {
    foreach ($tomerge as $array) {
      // COMBINA OS ARRAYS MANTENDO AS CHAVES DEFINADAS E TODOS OS VALORES
      foreach ($array as $key => $value) {
        if (!array_key_exists($key, $main))
          $main[$key] = $value;
        else
          $main[] = $value;
      }
    }

    return true;
  }
}
