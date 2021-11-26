<?php

namespace App\Session;

class Main {
  /**
   * Método responsável por iniciar a sessão
   */
  private static function init(): void {
    // VERIFICA SE A SESSÃO NÃO ESTÁ ATIVA
    if (session_status() != PHP_SESSION_ACTIVE)
      session_start();
  }

  /**
   * Método responsável por setar uma variavel na Sessão
   *
   * @param   string  $key
   * @param   mixed   $value
   *
   * @return  void
   */
  public static function set(string $key, mixed $value): void {
    // INICIA A SESSÃO
    self::init();

    $_SESSION['philhas'][$key] = $value;
  }

  /**
   * Método responsável por retornar se uma variavel existe na Sessão
   *
   * @param   string  $key
   *
   * @return  bool
   */
  public static function isSet(string $key): bool {
    // INICIA A SESSÃO
    self::init();

    return isset($_SESSION['philhas'][$key]);
  }

  /**
   * Método responsável por retornar uma variavel da Sessão
   *
   * @param   string  $key
   *
   * @return  mixed
   */
  public static function get(string $key): mixed {
    // INICIA A SESSÃO
    self::init();

    return $_SESSION['philhas'][$key];
  }

  /**
   * Método responsável por destruir uma variavel da Sessão
   *
   * @param   string  $key
   *
   * @return  void
   */
  public static function delete(string ...$key): void {
    // INICIA A SESSÃO
    self::init();

    foreach ($key as $k)
      unset($_SESSION['philhas'][$k]);
  }

  /**
   * Método responsável por destruir todas as variaveis da Sessão
   *
   * @return  void
   */
  public static function destroy(): void {
    // VERIFICA SE A SESSÃO ESTÁ ATIVA
    if (session_status() == PHP_SESSION_ACTIVE)
      session_destroy();
  }
}
