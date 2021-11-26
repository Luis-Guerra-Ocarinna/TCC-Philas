<?php

namespace App\Controller\Admin;

use App\Http\Request;
use App\Model\Entity\User as EntityUser;
use App\Session\Main as SessionMain;
use App\Utils\Alert;
use App\Utils\CPF;
use App\Utils\Masker;
use App\Utils\View;
use WilliamCosta\DatabaseManager\Pagination;

class Users extends Page {

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
      case 'usuarioExistente':
        return Alert::getError('O usuário já está em uso!');
      case 'cpfInvalido':
        return Alert::getError('O CPF é inválido!');
      case 'usuarioCadastrado':
        return Alert::getSuccess('O usuário foi cadastrado com sucesso!');
      case 'usuarioAlterado':
        return Alert::getSuccess('O usuário foi alterado com sucesso!');
      case 'usuarioDeletado':
        return Alert::getSuccess('O usuário foi deletado com sucesso!');
      default:
        return '';
    }
  }

  /**
   * Método responsável por redirecionar o admin com um status
   *
   * @param   Request  $request
   * @param   string   $status
   *
   * @return  never
   */
  private static function returnStatus(Request $request, string $status): void {
    $request->getRouter()->redirect('/admin/usuarios/new?status=' . $status);
  }

  /**
   * Método responsável por renderizar os itens da lista usuários
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getUsersItems(Request $request, ?Pagination &$obPagination): string {
    // ATENDIEMTO
    $itens = '';

    // ADMIN LOGADO
    $obAdmin = SessionMain::get('user_logged');

    // QUANTIDADE TOTAL DE ATENDIEMTO
    $quantidadeTotal = EntityUser::getUsers("id != $obAdmin->id", null, null, 'COUNT(*) as qtd')->fetchObject()->qtd;

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
    $results = EntityUser::getUsers("id != $obAdmin->id", 'id DESC', $obPagination->getLimit());

    // RENDERIZA O ITEM TODO
    while ($obUser = $results->fetchObject(EntityUser::class)) {
      $itens .= View::render('admin/actions/users/item', [
        'id'    => $obUser->id,
        'name'  => $obUser->nome,
        'user'  => $obUser->login,
        'email' => $obUser->email,
        'phone' => $obUser->telefone,
        'cpf'   => $obUser->cpf,
        'tipo'  => $obUser->tipo
      ]);
    }

    //RETORNA OS ITENS
    return $itens;
  }

  /**
   * Método responsável por renderizar a tela de listagem de usuários
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getUsers(Request $request): string {
    // VIEW DA LISTAGEM DE USUÁRIOS
    $content = View::render('admin/actions/users/list', [
      'itens'      => self::getUsersItems($request, $obPagination),
      'pagination' => parent::getPagination($request, $obPagination),
      'status'     => self::getStatus($request),
      'title_form' => 'Usuários',
      'content'    => 'Listagem de usuários cadastrados no banco de dados.'
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage(
      'Usuários',
      $content
    );
  }

  /**
   * Método responsável por obter a renderização dos itens de Tipos para o formulario
   *
   * @return  string
   */
  public static function getTypesItems(string $current = ''): string {
    // TIPOS
    $itens = '';

    // ARRAY DE TIPOS
    $tipos = EntityUser::$tipos;

    // RENDERIZA OS MOTIVOS
    while ($tipo = array_shift($tipos)) {
      $itens .= View::render('admin/actions/users/type/item', [
        'tipo'     => $tipo,
        'selected' => $current == $tipo ? 'selected' : ''
      ]);
    }

    return $itens;
  }

  /**
   * Método responsável por renderizar o formulario de novo usuário
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getNewUser(Request $request): string {
    // SELECT DE TIPOS
    $boxType = View::render('admin/actions/users/type/box', [
      'selected' => 'selected',
      'item'     => self::getTypesItems()
    ]);

    // VIEW DA MINHA CONTA
    $content = View::render('admin/actions/users/form', [
      'title_form'     => 'Cadastrar Usuário',
      'status'         => self::getStatus($request),
      'name'           => '',
      'lastname'       => '',
      'pw_placeholder' => '********************',
      'pw_required'     => 'required',
      'phone'          => '',
      'cpf'            => '',
      'user'           => '',
      'email'          => '',
      'select_type'    => $boxType
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Usuários', $content);
  }

  /**
   * Método responsável por cadastrar um novo usuário no banco
   *
   * @param   Request  $request
   * 
   * @return  never
   */
  public static function setNewUser(Request $request): void {
    // POST VARS
    $postVars = $request->getPostVars();

    // DADOS DO FORMULÁRIO
    $nome     = ($postVars['nome'] && $postVars['sobrenome']) ? "$postVars[nome] $postVars[sobrenome]" : null;
    $senha    = $postVars['senha'];
    $telefone = $postVars['telefone'] ?: null;
    $cpf      = Masker::remove($postVars['cpf']);
    $login    = $postVars['usuario'];
    $email    = $postVars['email'] ?: null;
    $tipo     = $postVars['tipo'] ?: null;

    // VALIDA O LOGIN DO USUÁRIO
    $obUserLogin = EntityUser::getUserByLogin($login);
    if ($obUserLogin instanceof EntityUser) self::returnStatus($request, 'usuarioExistente');

    // VALIDA O CPF
    if (!CPF::isvalid($cpf)) self::returnStatus($request, 'cpfInvalido');

    // NOVA INSTÂNCIA DA ENTIDADE USUÁRIO
    $obUser = new EntityUser;

    // SALVA OS DADOS
    $obUser->nome     = $nome;
    $obUser->senha    = $senha;
    $obUser->telefone = $telefone;
    $obUser->cpf      = $cpf;
    $obUser->login    = $login;
    $obUser->email    = $email;
    $obUser->tipo     = $tipo;
    $obUser->insert();

    // REDIRECIONA O ADMIN
    self::returnStatus($request, 'usuarioCadastrado');
  }

  /**
   * Método responsável por renderizar o formulario de editar usuário selecionado
   *
   * @param   Request  $request
   * @param   integer  $id
   *
   * @return  string
   */
  public static function getEditUser(Request $request, $id): string {
    // VALIDA O ID DO MOTIVO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);

    // OBTEM O USUÁRIO DO BANCO DE DADOS
    $obUser = EntityUser::getUserById(intval($id));
    if (!$obUser instanceof EntityUser)
      throw new \Exception("O usuário($id) não foi encontrado", 404);

    // SELECT DE TIPOS
    $boxType = View::render('admin/actions/users/type/box', [
      'selected' => '',
      'item'     => self::getTypesItems($obUser->tipo)
    ]);

    // NOME COMPLETO DO USUÁRIO REPARTIDO
    $fullname = explode(' ', $obUser->nome, 2);

    // VIEW DE EDITAR USUÁRIO SELECIONADO
    $content = View::render('admin/actions/users/form', [
      'title_form'     => 'Editar Usuário',
      'status'         => self::getStatus($request),
      'name'           => $fullname[0] ?? '',
      'lastname'       => $fullname[1] ?? '',
      'pw_placeholder' => 'Preencher somente para alteração',
      'pw_required'     => '',
      'phone'          => $obUser->telefone ?? '',
      'cpf'            => $obUser->cpf ?? '',
      'user'           => $obUser->login,
      'email'          => $obUser->email,
      'select_type'    => $boxType
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Usuários', $content);
  }

  /**
   * Método responsável por editar o usuário selecionado
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  never
   */
  public static function setEditUser(Request $request, $id): void {
    // VALIDA O ID DO MOTIVO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);

    // OBTEM O USUÁRIO DO BANCO DE DADOS
    $obUser = EntityUser::getUserById(intval($id));
    if (!$obUser instanceof EntityUser)
      throw new \Exception("O usuário($id) não foi encontrado", 404);

    // POST VARS
    $postVars = $request->getPostVars();

    // DADOS DO FORMULÁRIO
    $nome     = ($postVars['nome'] && $postVars['sobrenome']) ? "$postVars[nome] $postVars[sobrenome]" : null;
    $senha    = $postVars['senha'] ?: null;
    $telefone = $postVars['telefone'] ?: null;
    $cpf      = Masker::remove($postVars['cpf']);
    $login    = $postVars['usuario'];
    $email    = $postVars['email'] ?: null;
    $tipo     = $postVars['tipo'] ?: null;

    // VALIDA O LOGIN (DUPLICAÇÃO)
    $obUserLogin = EntityUser::getUserByLogin($postVars['usuario']);
    if ($obUserLogin instanceof EntityUser && $obUserLogin->id != $id)
      $request->getRouter()->redirect('/admin/usuarios/' . $id . '/edit?status=usuarioExistente');

    // VALIDA O CPF
    if (!CPF::isvalid($cpf)) self::returnStatus($request, 'cpfInvalido');

    // ATUALIZA OS DADOS
    $obUser->nome     = $nome;
    $obUser->senha    = $senha;
    $obUser->telefone = $telefone;
    $obUser->cpf      = $cpf;
    $obUser->login    = $login;
    $obUser->email    = $email;
    $obUser->tipo     = $tipo;
    $obUser->update();

    // REDIRECIONA O ADMIN
    $request->getRouter()->redirect('/admin/usuarios/' . $id . '/edit?status=usuarioAlterado');
  }

  /**
   * Método responsável por renderizar o formulario de deletar usuário selecionado
   *
   * @param   Request  $request
   * @param   integer  $id
   *
   * @return  string
   */
  public static function getDeleteUser($id): string {
    // OBTEM O USUÁRIO DO BANCO DE DADOS
    $obUserId = EntityUser::getUserById($id);

    // VIEW DE DELETAR USUÁRIO
    $content = View::render('admin/actions/users/delete', [
      'title_form' => 'Deletar usuário',
      'name'       => $obUserId->nome,
      'id'         => $id
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage(
      'Usuários',
      $content
    );
  }

  /**
   * Método responsável por excluir o usuário selecionado
   *
   * @param   Request  $request
   * @param   integer  $id
   *
   * @return  string
   */
  public static function setDeleteUser(Request $request, $id) {
    // OBTEM O USUÁRIO DO BANCO DE DADOS
    $obUserId = EntityUser::getUserById($id);

    // DELETA O USUÁRIO SELECIONADO
    $obUserId->delete();

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/admin/usuarios?status=usuarioDeletado');
  }
}
