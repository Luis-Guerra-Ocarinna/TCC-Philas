<?php

namespace App\Utils;

use DateTime;

/**
 * Classe responsável por controlar Datas
 */
class Date {

  /**
   * Método responsável por validar datas
   *
   * @param   string  $date
   * @param   string  $format
   *
   * @return  bool
   */
  public static function isvalid(string $date, string $format = 'd/m/Y H:i:s'): bool {
    // CRIA UMA VALIDADOR DE DATAS
    $validator = DateTime::createFromFormat($format, $date);

    // RETORNA SE FOI POSSIVEL CRIAR UMA DATA
    // E ERA A MESMA INFORMADA
    return $validator and $validator->format($format) == $date;
  }

  /**
   * Método responsável por formatar datas
   *
   * @param   string  $formatting
   * @param   string  $date
   * @param   string  $format
   *
   * @return  string
   */
  public static function format(string $formatting, string $date, string $format): string {
    // VALIDA A DATA ENVIADA
    if (!self::isvalid($date, $formatting))
      throw new \InvalidArgumentException('Data não corresponde com a formatação');

    // FORMATA A DATA ENVIADA
    return DateTime::createFromFormat($formatting, $date)->format($format);
  }
}
