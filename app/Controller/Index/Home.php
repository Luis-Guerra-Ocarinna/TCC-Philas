<?php

namespace App\Controller\Index;

use App\Http\Request;
use App\Model\Entity\Us as EntityUs;
use App\Utils\Alert;
use App\Utils\View;

class Home extends Page {

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
      case 'usuarioAlteradoEmail':
        return Alert::getWarning('Usuário alterado com sucesso! Porém verifique o novo e-mail para confirmar a alteração.');
      default:
        return '';
    }
  }

  /**
   * Método responsável por retornar o conteúdo (VIEW) da Home
   *
   * @return  string  
   */
  public static function getHome(): string {
    // INSTANCIA DA ENTIDADE CONTENT
    $obContent = (new EntityUs)->conteudo;

    // VIEW DA HOME
    $content =  View::render('home', [
      'titulo' => $obContent->titulo,
      'texto'  => $obContent->texto
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('{{us_name}}', $content);
  }

  /**
   * Método responsável por retornar a VIEW de aviso para confirmação de email
   *
   * @param   Request  $request
   * 
   * @return  string
   */
  public static function getConfirmEmail(Request $request): string {
    // CONTEÚDO DO AVISO
    $content = View::render('confirmEmail', ['status' => self::getStatus($request)]);

    // RETORNAR A PÁGINA RENDERIZA
    return parent::getPage('Confirmar Email', $content);
  }
}
