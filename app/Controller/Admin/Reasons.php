<?php

namespace App\Controller\Admin;

use App\Http\Request;
use App\Utils\View;
use App\Utils\Alert;
use App\Model\Entity\Reason as EntityReason;
use \WilliamCosta\DatabaseManager\Pagination;

class Reasons extends Page {
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
      case 'motivoExistente':
        return Alert::getError('O motivo já existe no banco de dados!');
      case 'motivoCadastrado':
        return Alert::getSuccess('O motivo foi cadastrado com sucesso!');
      case 'motivoAlterado':
        return Alert::getSuccess('O motivo foi alterado com sucesso!');
      case 'motivoDeletado':
        return Alert::getSuccess('O motivo foi deletado com sucesso!');
      default:
        return '';
    }
  }

  /**
   * Método responsável por redirecionar o usuário com um status
   *
   * @param   Request  $request
   * @param   string   $status
   *
   * @return  void
   */
  private static function returnStatus(Request $request, string $status): void {
    $request->getRouter()->redirect('/admin/motivos/new?status=' . $status);
  }

  /**
   * Método responsável por renderizar os itens da lista reasons
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getReasonsItems(Request $request, ?Pagination &$obPagination): string {
    // ATENDIMETOS
    $itens = '';

    // QUANTIDADE TOTAL DE ATENDIMETOS
    $quantidadeTotal = EntityReason::getReasons(null, null, null, 'COUNT(*) as qtd')->fetchObject()->qtd;

    // PÁGINA ATUAL
    $queryParams = $request->getQueryParams();
    $paginaAtual = $queryParams['page'] ?? 1;

    // LIMITE POR PÁGINA
    $limit = $queryParams['per_page'] ?? 8;
    $limit = is_numeric($limit) ? $limit : 8;

    // VALIDANDO SE DEVE MOSTRAR TODOS
    $limit = $limit > 0 ? $limit : $quantidadeTotal;

    //INSTANCIA DE PAGINAÇÃO
    $obPagination = new Pagination($quantidadeTotal, $paginaAtual, $limit);

    //RESULTADOS DO USUÁRIO
    $results = EntityReason::getReasons(null, 'id DESC', $obPagination->getLimit());

    // RENDERIZA O ITEM TODO
    while ($obReason = $results->fetchObject(EntityReason::class)) {
      $itens .= View::render('admin/actions/reasons/item', [
        'id'             => $obReason->id,
        'descricao'      => $obReason->descricao,
        'tempo_previsto' => $obReason->tempo_previsto . ' minutos'
      ]);
    }

    //RETORNA OS ITENS
    return $itens;
  }

  /**
   * Método responsável por renderizar a tela de listagem de motivos
   *
   * @param   Request  $request  
   *
   * @return  string             
   */
  public static function getReasons(Request $request): string {
    // VIEW DA LISTAGEM DE USUÁRIOS
    $content = View::render('admin/actions/reasons/list', [
      'itens'      => self::getReasonsItems($request, $obPagination),
      'pagination' => parent::getPagination($request, $obPagination),
      'status'     => self::getStatus($request),
      'title_form' => 'Motivos',
      'content'    => 'Listagem de motivos cadastrados no banco de dados.'
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Motivos', $content);
  }

  /**
   * Método responsável por renderizar o formulario de novo motivo
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getNewReason(Request $request): string {
    // VIEW DA MINHA CONTA
    $content = View::render('admin/actions/reasons/form', [
      'title_form'   => 'Cadastrar motivo',
      'status'       => self::getStatus($request),
      'descricao'    => '',
      'tmp_previsto' => ''
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Motivos', $content);
  }

  /**
   * Método responsável por cadastrar um novo motivo no banco
   *
   * @param   Request  $request
   *
   * @return  never
   */
  public static function setNewReason(Request $request): void {
    // POST VARS
    $postVars = $request->getPostVars();

    // DADOS DO FORMULÁRIO
    $descricao    = $postVars['descricao'];
    $tmp_previsto = $postVars['tmp_previsto'];

    // VALIDA SE O MOTIVO JÁ EXISTE
    $obReasonD = EntityReason::getReasons("descricao = '$descricao'")->fetchObject(EntityReason::class);
    if ($obReasonD instanceof EntityReason)
      self::returnStatus($request, 'motivoExistente');

    // NOVA INSTÂNCIA DA ENTIDADE USUÁRIO
    $obReason = new EntityReason;

    // SALVA OS DADOS
    $obReason->descricao      = $descricao;
    $obReason->tempo_previsto = $tmp_previsto;
    $obReason->insert();

    // REDIRECIONA O ADMIN
    self::returnStatus($request, 'motivoCadastrado');
  }

  /**
   * Método responsável por renderizar o formulario de editar motivo selecionado
   *
   * @param   Request  $request
   * @param   integer  $id
   *
   * @return  string
   */
  public static function getEditReason(Request $request, $id): string {
    // VALIDA O ID DO MOTIVO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);

    // OBTEM O MOTIVO DO BANCO DE DADOS
    $obReason = EntityReason::getReasonById($id);
    if (!$obReason instanceof EntityReason)
      throw new \Exception("O motivo($id) não foi encontrado", 404);

    // VIEW DE EDITAR REASON SELECIONADO
    $content = View::render('admin/actions/reasons/form', [
      'title_form'   => 'Editar motivo',
      'status'       => self::getStatus($request),
      'descricao'    => $obReason->descricao,
      'tmp_previsto' => $obReason->tempo_previsto
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Motivos', $content);
  }

  /**
   * Método responsável por editar o motivo selecionado
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  never
   */
  public static function setEditReason(Request $request, $id): void {
    // VALIDA O ID DO MOTIVO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);

    // OBTEM O MOTIVO DO BANCO DE DADOS
    $obReason = EntityReason::getReasonById($id);
    if (!$obReason instanceof EntityReason)
      throw new \Exception("O motivo($id) não foi encontrado", 404);

    // POST VARS
    $postVars = $request->getPostVars();

    // DADOS DO FORMULÁRIO
    $descricao    = $postVars['descricao'];
    $tmp_previsto = $postVars['tmp_previsto'];

    // VALIDA SE O MOTIVO JÁ EXISTE
    $obReasonD = EntityReason::getReasons("descricao = '$descricao' AND id != $id")->fetchObject(EntityReason::class);
    if ($obReasonD instanceof EntityReason)
      $request->getRouter()->redirect('/admin/motivos/' . $id . '/edit?status=motivoExistente');

    // ATUALIZA OS DADOS
    $obReason->descricao      = $descricao;
    $obReason->tempo_previsto = $tmp_previsto;
    $obReason->update();

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/admin/motivos/' . $id . '/edit?status=motivoAlterado');
  }

  /**
   * Método responsável por renderizar o formulario de deletar motivo selecionado
   *
   * @param   Request  $request
   * @param   integer  $id
   *
   * @return  string
   */
  public static function getDeleteReason($id): string {
    // VALIDA O ID DO MOTIVO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);

    // OBTEM O MOTIVO DO BANCO DE DADOS
    $obReason = EntityReason::getReasonById($id);
    if (!$obReason instanceof EntityReason)
      throw new \Exception("O motivo($id) não foi encontrado", 404);

    // VIEW DE DELETAR MOTIVO
    $content = View::render('admin/actions/reasons/delete', [
      'title_form' => 'Deletar motivo',
      'descricao'  => $obReason->descricao,
      'id'         => $obReason->id
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Motivos', $content);
  }

  /**
   * Método responsável por excluir o motivo selecionado
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  never
   */
  public static function setDeleteReason(Request $request, $id): void {
    // VALIDA O ID DO MOTIVO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);

    // OBTEM O MOTIVO DO BANCO DE DADOS
    $obReason = EntityReason::getReasonById($id);
    if (!$obReason instanceof EntityReason)
      throw new \Exception("O motivo($id) não foi encontrado", 404);

    // EXCLUI O MOTIVO
    $obReason->delete();

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/admin/motivos?status=motivoDeletado');
  }
}
