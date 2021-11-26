<?php

namespace App\Utils;

/**
 * Classe responsável por controlar máscaras para valores
 */
class Masker {

  /** @var string Mácara para CPFs */
  public static string $MK_CPF = '###.###.###-##';

  /** @var string Mácara para CNPJs */
  public static string $MK_CNPJ = '##.###.###/####-##';

  /** @var string Mácara para CEPs */
  public static string $MK_CEP = '#####-###';

  /**
   * Método responsável por mascarar um number
   *
   * @param   string  $number
   * @param   string  $mask
   * @param   string  $marker
   *
   * @return  string
   */
  public static function mask(string $number, string $mask, ?string $fill = null, string $marker = '#'): string {

    // QUATIDADE ESPERADA DE NÚMEROS
    $expectedLen = strlen(str_replace(str_split(str_replace($marker, '', $mask)), '', $mask));

    // SE NÃO FOR PREENCHER E A QTD FOR MENOR QUE A ESPARADA
    if ($fill == null and strlen($number) < $expectedLen) throw new \Exception("Falta de números para máscara");

    // PREENCHE O COM VALOR PASSADO
    while (strlen($number) < $expectedLen) $number .= $fill;

    // COVERTE PARA ARRAYS
    $number = str_split($number);
    $mask = str_split($mask);

    // MAPEIA CADA VALOR DA MÁSCARA
    // SUBISTITUI PELOS NÚMEROS SE BATER COM O MACARADOR
    // CONVERTE O ARRAY PARA STRING
    return implode("", array_map(function ($value) use (&$number, $marker) {
      return ($value == $marker) ? array_shift($number) : $value;
    }, $mask));
  }

  /**
   * Método responsável por retirar a mascaração
   *
   * @param   string  $masked
   *
   * @return  string
   */
  public static function remove(string $masked): string {
    // RETIRA TUDO QUE NÃO FOR NÚMERO E RETORNA
    return preg_replace('/\D/', '', $masked);
  }
}
