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
    $postVars    = $request->getPostVars();
    $description = $postVars['descricao'];
    $title       = $postVars['titulo'];
    $file        = $postVars['arquivo'];

    if (isset($file)) {
      // INSTANCIA DE UPLOAD
      $obUpload = new Upload($file);

      // ALTERA O NOME DO ARQUIVO
      $obUpload->setName('default');

      // MOVE OS ARQUIVOS DE UPLOAD
      $sucesso = $obUpload->upload(__DIR__ . '/' . '../../../resources/img/Files', true);
      if ($sucesso) {
        // INSTANCIA DE ENTIDADE NÓS
        $obUs = new EntityUs;

        // ATUALIZA O CONTÉUDO
        $obUs->conteudo->titulo = $title;
        $obUs->conteudo->texto  = $description;
        $obUs->conteudo->imagem = $obUpload->getBaseName();

        // ATUALIZA-OS
        $obUs->update();

        // REDIRECIONA O USUÁRIO
        $request->getRouter()->redirect('/admin/conteudo?status=conteudoModificado');
      }

      // REDIRECIONA O USUÁRIO
      $request->getRouter()->redirect('/admin/conteudo?status=erro');
    }
  }
}
