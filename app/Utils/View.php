<?php

namespace App\Utils;

class View {

  /** @var string Caminho das views */
  private static $path = __DIR__ . '/../../resources/view';

  /** @var array Váriaveis padrões da View */
  private static $vars = [];

  /**
   * Método responsável por definir os dados inicias da classe
   *
   * @param   array  $vars
   */
  public static function init($vars = []): void {
    self::$vars = $vars;
  }

  /**
   * Método responsável por renderizar as variáveis
   *
   * @param   array  $vars
   */
  private static function renderVars(?array &$vars): void {
    // MERGE DE VARIÁVEIS DA VIEW
    $vars = array_merge(self::$vars, $vars);

    // CONVERTE AS CHAVES PARAS A VARIÁVEIS
    $keys = array_keys($vars);
    $keys = array_map(function ($key) {
      return '{{' . $key . '}}';
    }, $keys);

    // VALORES DO ARRAT
    $values = array_values($vars);

    // RENDERIZA O ARRAY
    $vars = ['keys' => $keys, 'values' => $values];
  }

  /**
   * Método responsável por retornar o importe de stylesheets
   *
   * @param   string  $source
   * @param   string  $comment
   * @param   bool    $externalUrl
   *
   * @return  string
   */
  public static function getStyle(string $source, string $comment, bool $externalUrl = false): string {
    // OBTÉM A TAG PARA IMPORTE DE STYLESHEETS 
    $link = self::$path . '/css/.html';
    $link = file_get_contents($link); # sem verificação para gerar aviso

    // CAMINHO PARA O SOURCE
    if ($externalUrl) {
      $path = $css = $source;
    } else {
      $path = self::$path . "/css/$source.css";
      $css = URL . "/resources/view/css/$source.css";
    }
    // GERA WARNING SE NN SE NÃO EXISTIR O STYLESHEETS
    if (!@file_get_contents($path))
      TriggerError::warning("No such file or directory (<b> $path </b>)", 2);

    // RENDERIZA AS VARIÁVEIS
    $vars = [
      'comment' => $comment,
      'style'   => $css
    ];
    self::renderVars($vars);

    // RETORNA A TAG RENDERIZADA
    return str_replace($vars['keys'], $vars['values'], $link);
  }

  /**
   * Método responsável por retornar o conteúdo de uma view
   *
   * @param   string $view
   * 
   * @return  string
   **/
  private static function getContentView(string $view): string {
    $html = self::$path . '/' . $view . '.html';
    return file_exists($html) ?
      file_get_contents($html) :
      '' . TriggerError::warning("No such file or directory (<b>$html</b>)", 2);
  }

  /**
   * Método responsável por retornar o importe de scripts
   *
   * @param   string  $source
   * @param   string  $comment
   * @param   bool    $externalUrl
   * 
   * @return  string
   */
  public static function getScript(string $source, string $comment, bool $externalUrl = false): string {
    // OBTÉM A TAG PARA IMPORTE DE STYLESHEETS 
    $script = self::$path . '/js/.html';
    $script = file_get_contents($script); # sem verificação para gerar aviso

    // CAMINHO PARA O SOURCE
    if ($externalUrl) {
      $path = $js = $source;
    } else {
      $path = self::$path . "/js/$source.js";
      $js = URL . "/resources/view/js/$source.js";
    }
    // GERA WARNING SE NN SE NÃO EXISTIR O SCRIPT
    if (!@file_get_contents($path))
      TriggerError::warning("No such file or directory (<b> $path </b>)", 2);

    // RENDERIZA AS VARIÁVEIS
    $vars = [
      'comment' => $comment,
      'script'  => $js
    ];
    self::renderVars($vars);

    // RETORNA A TAG RENDERIZADA
    return str_replace($vars['keys'], $vars['values'], $script);
  }

  /**
   * Método responsável por retornar o conteúdo renderizado de uma view
   *
   * @param   string            $view
   * @param   (string|float)[]  $vars 
   * 
   * @return  string
   **/
  public static function render(string $view, array $vars = []): string {
    // CONTEÚDO DA VIEW
    $contenteView = self::getContentView($view);

    // RENDERIZA AS VARIÁVEIS
    self::renderVars($vars);

    // RETORNA O CONTEÚDO RENDERIZADO
    return str_replace($vars['keys'], $vars['values'], $contenteView);
  }
}
