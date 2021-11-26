<?php

namespace App\Controller\User;

use App\Http\Request;
use App\Model\Entity\Us as EntityUs;
use App\Utils\View;
use WilliamCosta\DatabaseManager\Pagination;

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
    return View::render('user/header');
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
   * Método responsável por retornar um link de paginação
   *
   * @param   array   $queryParams
   * @param   array   $page
   * @param   string  $url
   * @param   string  $label
   *
   * @return  string
   */
  private static function getPaginationLink(array $queryParams, array $page, string $url, string $label = null): string {
    // ALTERA PÁGINA
    $queryParams['page'] = $page['page'];

    // REMOVE O STATUS
    unset($queryParams['status']);

    // LINK
    $link = $url . '?' . http_build_query($queryParams);

    // VIEW
    return View::render('Pagination/link', [
      'page'   => $label ?? $page['page'],
      'link'   => $link,
      'active' => $page['current'] ? 'active' : ''
    ]);
  }

  /**
   * Método responsável por renderizar o layout de paginação
   *
   * @param   Request     $request
   * @param   Pagination  $obgPagination
   *
   * @return  string
   */
  public static function getPagination(Request $request, Pagination $obgPagination): string {
    // PÁGINAS
    $pages = $obgPagination->getPages();

    // VERIFICA A QUANTIDADE DE PÁGINAS
    if (count($pages) <= 1) return '';

    // LINKS
    $links = '';

    // URL ATUAL (SEM QUERYS)
    $url =  $request->getRouter()->getCurrentUrl();

    // QUERY PARAMS
    $queryParams = $request->getQueryParams();

    // PÁGINA ATUAL
    $currentPage = $queryParams['page'] ?? 1;

    // LIMITE DE CAIXAS DE LINKS
    $limit = 5;

    // MEIO DA PAGINAÇÃO
    $middle = ceil($limit / 2);

    // INÍCIO DA PAGINAÇÃO
    $start = $middle > $currentPage ? 0 : $currentPage - $middle;

    // AJUSTA O FINAL DA PAGINAÇÃO
    $end = $limit + $start;
    
    // AJUSTA O INÍCIO DA PAGINAÇÃO
    if ($end > count($pages)) {
      $diff = $end - count($pages);
      $start -= $diff;
    }

    // LINK INCIAL
    if ($start > 0)
      $links .= self::getPaginationLink($queryParams, reset($pages), $url, '«');

    // RENDERIZA OS LINKS
    foreach ($pages as $page) {
      // VERIFICA O START DA PAGINAÇÃO
      if ($page['page'] <= $start) continue;

      // VERIFICA O END DA PAGINA
      if ($page['page'] > $end) {
        $links .= self::getPaginationLink($queryParams, end($pages), $url, '»');
        break;
      }

      $links .= self::getPaginationLink($queryParams, $page, $url);
    }

    // RENDERIZA BOX DE PAGINAÇÃO
    return  View::render('Pagination/box', [
      'links' => $links
    ]);
  }

  /**
   * Método responsável por retornar o conteúdo (view) da página genárica
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
    ?array  $styles  = [[]],
    ?array  $scripts = [[]]
  ) {
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
    return View::render('user/page', [
      'styles'  => $styles,
      'title'   => $title,
      'header'  => $header ?? self::getHeader(),
      'content' => $content,
      'footer'  => $footer ?? self::getFooter(),
      'scripts' => $scripts,
      'us_name' => (new EntityUs)->nome
    ]);
  }
};
