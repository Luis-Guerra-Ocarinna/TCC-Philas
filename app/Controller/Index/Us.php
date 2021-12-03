<?php

namespace App\Controller\Index;

use App\Http\Request;
use App\Model\Entity\Us as EntityUs;
use App\Utils\Alert;
use App\Utils\Email;
use App\Utils\View;

class Us extends Page {

  /**
   * Método responsável por retornar a mensagem de status
   *
   * @param   Request  $request  
   *
   * @return  string             
   */
  private static function getStatus(Request $request): string {
    // QUERY PARAMS
    $queryParams = $request->getQueryParams();

    // STATUS
    if (!isset($queryParams['status'])) return '';

    // MENSAGEM DE STATUS
    switch ($queryParams['status']) {
      case 'enviadoSucesso':
        return Alert::getSuccess('A mensagem foi enviada com sucesso!');
      default:
        return '';
    }
  }

  /**
   * Método responsável por renderizar os contedúdos da organazação
   *
   * @param   string  $title
   * @param   string  $content
   *
   * @return  string
   */
  private static function renderPage(string $title, string $content): string {
    // RENDERIZADA O DEFAULT
    return parent::getPage($title, $content);
  }

  /**
   * Método responsável por renderizar o painel de sobre
   *
   * @param   string  $title
   * @param   string  $content
   * @param   string  $page
   *
   * @return  string
   */
  private static function getAboutPanel(string $title, string $content, string $page): string {
    /** Método responsável por retornar a paginação */
    function getAboutPagination(string $currentPage): string {
      // PÁGINAS
      $links = '';

      // PÁGINAS DO PAINEL
      $pages = ['' => 'Nós'];

      // ADICIONA OS AUTORES ÀS PÁGINAS DO PAINEL
      foreach ((new EntityUs)->autores as $hash => $author) {
        $pages += [$hash => explode(' ', $author->nome, 2)[0]];
      }

      // RENDERIZA OS LINKS PARA AS PÁGINAS
      foreach ($pages as $hash => $page) {
        $links .= View::render('Pagination/link', [
          'link'   => URL . '/sobre' . '/' . $hash,
          'active' => $currentPage == $hash ? 'active' : '',
          'page'   => $page
        ]);
      }

      // RETORNA A PAGINAÇÃO
      return View::render('Pagination/box', [
        'links' => $links
      ]);
    }

    // VIEW DO PAINEL SOBRE
    $content = View::render('us/about', [
      'title'      => $title,
      'content'    => $content,
      'pagination' => getAboutPagination($page)
    ]);

    // RETORNA A VIEW DO PAINEL
    return self::renderPage('Sobre', $content);
  }

  /**
   * Método responsável por retornar o conteúdo (VIEW) do Sobre Nós
   *
   * @return  string
   */
  public static function getAbout(): string {
    // VIEW DA TELA SOBRE NÓS
    $content = View::render('us/about/index', [
      'text' => (new EntityUs)->descricao
    ]);

    // RETORNA A VIEW DA PÁGINA
    return self::getAboutPanel('{{us_name}}', $content, '');
  }

  /**
   * Método responsável por retornar o conteúdo (VIEW) dos autores
   *
   * @param   string  $author
   *
   * @return  string
   */
  public static function getAuthors(string $author): string {
    function getLinks(object $links): string {
      $itens = '';

      if (empty($links)) return '';

      foreach ($links as $hash => $link) {
        $itens .= match ($hash) {
          'gmail' => <<<HTML
            <a href="mailto:$link" aria-label="Enviar email para $link" style="text-decoration: none">
              <img style="border-radius:0.3rem" alt="Badge GMail" src="https://img.shields.io/badge/Gmail-D14836?style=for-the-badge&logo=gmail&logoColor=white"/>
            </a>
            HTML,
          'github' => <<<HTML
            <a href="$link" aria-label="Github" style="text-decoration: none">
              <img style="border-radius:0.3rem" alt="Badge Github" src="https://img.shields.io/badge/GitHub-100000?style=for-the-badge&logo=github&logoColor=white"/>
            </a>
            HTML,
        };
      }

      return $itens;
    }

    // INSTANCIA DA ENTIDADE NÓS E OBTENÇÃO DOS AUTORES
    $obAuthors = (new EntityUs)->autores;

    // VALIDA O CRIADOR
    if (!array_key_exists($author, $obAuthors))
      throw new \Exception('Autor inválido. Existentes: [' . implode(', ', array_keys($obAuthors)) . ']', 404);

    // VIEW DA TELA SOBRE AUTOR
    $content = View::render('us/about/authors', [
      'text'  => $obAuthors[$author]->descricao,
      'image' => $obAuthors[$author]->imagem ?? '',
      'links' => getLinks($obAuthors[$author]->links),
    ]);

    // RETORNA A VIEW DA PÁGINA
    return self::getAboutPanel($obAuthors[$author]->nome, $content, $author);
  }

  /**
   * Método responsável por retornar o conteúdo (VIEW) da Privacidade
   *
   * @return  string
   */
  public static function getPrivacy(): string {
    // VIEW DA TELA PRIVACIDADE
    $content = View::render('us/privacy');

    // RETORNA A VIEW DA PÁGINA
    return self::renderPage('Privacidade', $content);
  }

  /**
   * Método responsável por retornar o conteúdo (VIEW) do Fale Conosco
   *
   * @return  string
   */
  public static function getContact($request): string {
    // VIEW DA TELA PRIVACIDADE
    $content = View::render('us/suport', [
      'title_form' => 'Fale Conosco',
      'content'    => 'Utilize o formulário para nos enviar uma mensagem',
      'status'     => self::getStatus($request)
    ]);

    // RETORNA A VIEW DA PÁGINA
    return self::renderPage('Fale Conosco', $content);
  }

  /**
   * Método responsável por enviar a mensagem do Fale Conosco
   *
   * @return  string
   */
  public static function setContact($request) {
    // POST VARS
    $postVars  = $request->getPostVars();
    $nome      = (isset($postVars['nome']) and isset($postVars['sobrenome'])) ? "$postVars[nome] $postVars[sobrenome]" : '';
    $email     = $postVars['email'];
    $mensagem  = $postVars['mensagem'];

    // ENVIA EMAIL
    Email::sendEmail($nome, $email, $mensagem);

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/contact?status=enviadoSucesso');
  }
};
