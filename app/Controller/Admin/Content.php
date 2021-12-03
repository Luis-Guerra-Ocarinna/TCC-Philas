<?php

namespace App\Controller\Admin;

use App\Utils\Alert;
use App\Utils\View;
use App\Utils\Upload;
use App\Http\Request;
use App\Model\Entity\Us as EntityUs;

class Content extends Page {

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
      case 'conteudoModificado':
        return Alert::getSuccess('O contéudo foi modificado com sucesso!');
      case 'erro':
        return Alert::getError('Tente novamente!');
      default:
        return '';
    }
  }

  /**
   * Método responsável por retornar a view da rota Contéudo 
   *
   * @return  string  
   *            
   */
  public static function getContent(Request $request): string {
    // VIEW DO CONTÉUDO
    $content = View::render('admin/actions/contents/content', [
      'title_form' => 'Contéudo',
      'content'    => 'Envia imagem e texto para tela home.',
      'status'     => self::getStatus($request)
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage(
      'Contéudo',
      $content,
      scripts: [['preview', 'preview do contéudo']]
    );
  }

  /**
   * Método responsável por enviar o Contéudo 
   *
   * @return  string  
   */
  public static function setContent(Request $request) {
    // POST VARS
    $postVars = $request->getPostVars();

    // DADOS DO CONTÉUDO
    $titulo    = $postVars['titulo'];
    $descricao = $postVars['descricao'];
    $imagem    = $postVars['arquivo'];

    if (isset($imagem)) {
      // CONVERTE A IMAGEM EM DATA URL
      $base64image = base64_encode(file_get_contents($imagem['tmp_name']));
      $mimeType    = $imagem['type'];
      $imagem      = "data:{$mimeType};base64,{$base64image}";

      // INSTANCIA DE ENTIDADE NÓS
      $obUs = new EntityUs;

      // ATUALIZA O CONTÉUDO
      $obUs->conteudo->titulo = $titulo;
      $obUs->conteudo->texto  = $descricao;
      $obUs->conteudo->imagem = $imagem;

      // ATUALIZA-OS
      $obUs->update();
    }

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/admin/conteudo?status=conteudoModificado');
  }
}
