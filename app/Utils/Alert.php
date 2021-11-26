<?php

namespace App\Utils;

use App\Utils\View;

/**
 * Classe responsável por controlar alertas para o usuário
 */
class Alert {

  /**
   * Método responsável por retornar uma mensagem de erro
   * 
   * @param   string         $message
   * @param   bool|string[]  $anchor
   *   
   * @return  string
   */
  public static function getError(string $message, bool|array $anchor = false): string {
    if (!($anchor === false))
      $a = self::getAnchor(...$anchor);

    return View::render('Alert/status', [
      'tipo'     => 'danger',
      'mensagem' => $message,
      'anchor'   => $a ?? ''
    ]);
  }

  /**
   * Método responsável por retornar uma mensagem de sucesso
   * 
   * @param   string         $message
   * @param   bool|string[]  $anchor
   *  
   * @return  string
   */
  public static function getSuccess(string $message, bool|array $anchor = false): string {
    if (!($anchor === false))
      $a = self::getAnchor(...$anchor);

    return View::render('Alert/status', [
      'tipo'     => 'success',
      'mensagem' => $message,
      'anchor'   => $a ?? ''
    ]);
  }

  /**
   * Método responsável por retornar uma mensagem de aviso
   * 
   * @param   string         $message
   * @param   bool|string[]  $anchor
   *  
   * @return  string
   */
  public static function getWarning(string $message, bool|array $anchor = false): string {
    if (!($anchor === false))
      $a = self::getAnchor(...$anchor);

    return View::render('Alert/status', [
      'tipo'     => 'warning',
      'mensagem' => $message,
      'anchor'   => $a ?? ''
    ]);
  }

  /**
   * Método responsável por retornar um link para o Alerta
   *
   * @param   string  $text
   * @param   string  $url
   *
   * @return  string
   */
  private static function getAnchor(string $text, string $url): string {
    return View::render('Alert/anchor', [
      'text' => $text,
      'url'  => $url
    ]);
  }
}
