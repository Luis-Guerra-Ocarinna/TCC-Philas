<?php

namespace App\Controller\Index;

use App\Model\Entity\Us as EntityUs;
use App\Utils\View;

class Page {

  /** @var array Stylesheets padrões */
  private static array $styles = [['style', 'CSS']];

  /** @var array Scripts padrões */
  private static array $scripts = [['form-validation', 'Validação Dos Forms']];

  /**
   * Método responsável por renderizar o topo da página
   *
   * @return  string
   */
  private static function getHeader() {
    return View::render('header');
  }

  /**
   * Método responsável por renderizar o rodapé da página
   *
   * @return  string
   */
  private static function getFooter() {
    return View::render('footer');
  }

  /**
   * Método responsável por retornar o conteúdo (VIEW) da página genárica
   *
   * @param   string  $title
   * @param   string  $content
   * @param   string  $header
   * @param   string  $footer
   * @param   array   $styles
   * @param   array   $scripts
   *
   * @return  string
   */
  public static function getPage(
    string $title,
    string $content,
    ?string $header  = null,
    ?string $footer  = null,
    array  $styles  = [[]],
    array  $scripts = [[]]
  ): string {
    // UNI OS PADRÕES
    $styles  = array_merge($styles, self::$styles);
    $scripts = array_merge($scripts, self::$scripts);

    // VARÍVAEL TEMPORÁRIA PARA O IMPORTS
    $temp = '';

    // IMPORTA OS STYLESHEETS
    foreach ($styles as $style) {
      if (count($style) == 0) continue;

      $temp .= View::getStyle(...$style);
    }
    $styles = $temp;

    // VARÍVAEL TEMPORÁRIA PARA O IMPORTS
    $temp = '';

    // IMPORTA OS SCRIPTS
    foreach ($scripts as $script) {
      if (count($script) == 0) continue;

      $temp .= View::getScript(...$script);
    }
    $scripts = $temp;

    // RENDERIZA A PÁGINA GENÉRICA
    return View::render('page', [
      'styles'  => $styles,
      'title'   => $title,
      'header'  => $header ?? self::getHeader(),
      'content' => $content,
      'footer'  => $footer ?? self::getFooter(),
      'scripts' => $scripts,
      'us_name' => (new EntityUs)->nome
    ]);
  }
}
