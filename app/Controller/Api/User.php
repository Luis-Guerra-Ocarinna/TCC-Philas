<?php

namespace App\Controller\Api;

use App\Http\Request;
use App\Model\Entity\User as EntityUser;
use App\Utils\CPF;
use App\Utils\Masker;
use WilliamCosta\DatabaseManager\Pagination;

class User extends Api {

  /**
   * Método responsável por renderizar e retornar os dados do usuário
   *
   * @param   EntityUser  $obUser
   *
   * @return  array
   */
  private static function getUserData(EntityUser $obUser): array {
    // RETIRA A SENHA DO RETORNO
    unset($obUser->senha);

    // CAST NOS VALORES NUMÉRICOS
    foreach ($obUser as $prop => &$value) {
      // IGNORA O CPF
      if (strtoupper($prop) == "CPF") continue;

      $value = is_numeric($value) ? (float) $value : $value;
    }

    // RETORNA A ENTIDADE USUÁRIO RENDERIZADA
    return (array) $obUser;
  }

  /**
   * Método responsável por armazenar e retornar os dados do usuário
   *
   * @param   array  $userData
   *
   * @return  EntityUser
   */
  private static function setUserData(array $userData): EntityUser {
    // INSTANCIA A ENTIDADE USUÁRIO
    $obUser = new EntityUser;

    // RETIRA O ID DO LOOP
    unset($obUser->id);

    // CADASTRA OS DADOS
    foreach ($obUser as $prop => &$value) {
      // IGNORA OS NULOS
      if (!isset($userData[$prop]) or empty($userData[$prop])) continue;

      // RETIRA A MASCÁRA DO CPF
      if (strtoupper($prop) == "CPF") $userData[$prop] = Masker::remove($userData[$prop]);

      // SETA OS VALORES
      $value = $userData[$prop];
    }

    // RETORNA OS DADOS
    return $obUser;
  }

  /**
   * Método responsável por retornar os itens de Usuários para a API
   *
   * @param   Request      $request       
   * @param   Pagination   $obPagination  
   *
   * @return  array
   */
  private static function getUsersItems(Request $request, ?Pagination &$obPagination): array {
    // USUÁRIOS
    $itens = [];

    // QUANTIDADE TOTAL DE REGISTRO
    $quantidadeTotal = EntityUser::getUsers(null, null, null, 'COUNT(*) as qtd')->fetchObject()->qtd;

    // PÁGINA ATUAL
    $queryParams = $request->getQueryParams();
    $paginaAtual = $queryParams['page'] ?? 1;

    // LIMITE POR PÁGINA
    $limit = $queryParams['per_page'] ?? 3;
    $limit = is_numeric($limit) ? $limit : 3;

    // VALIDANDO SE DEVE MOSTRAS TODOS OS REGISTROS
    $limit = $limit > 0 ? $limit : $quantidadeTotal;

    // INSTÂNCIA DE PAGINAÇÃO
    $obPagination = new Pagination($quantidadeTotal, $paginaAtual, $limit);

    // RESULTADOS DA PÁGINA
    $results = EntityUser::getUsers(null, 'id ASC', $obPagination->getLimit());

    // RENDERIZA O(S) ITEM(S)
    while ($obUser = $results->fetchObject(EntityUser::class)) {
      $itens[] = self::getUserData($obUser);
    }

    // RETORNA O(S) USUÁRIO(S)
    return $itens;
  }

  /**
   * Método responsável por retonar os usuarios cadastrados
   *
   * @param   Request  $request
   *
   * @return  array
   */
  public static function getUsers(Request $request): array {
    return [
      'usuarios'  => self::getUsersItems($request, $obPagination),
      'paginacao' => parent::getPagination($request, $obPagination)
    ];
  }

  /**
   * Método responsável por retornar um usuário específico
   *
   * @param   int|string   $identifier
   *
   * @return  array
   */
  public static function getUser(int|string $identifier): array {
    // BUSCA USUÁRIO PELO LOGIN
    $obUser = EntityUser::getUserByLogin($identifier);
    if (!$obUser instanceof EntityUser) {
      // VALIDA O ID
      if (is_numeric($identifier)) $obUser = EntityUser::getUserById($identifier);

      // BUSCA USUÁRIO PELO ID
      if (!$obUser instanceof EntityUser) throw new \Exception("Usuário($identifier) não encontrado", 404);
    }

    // RETORNA OS DADOS DO USUÁRIO
    return self::getUserData($obUser);
  }

  /**
   * Método responsável por retornar o usuário atual
   *
   * @param   Request  $request
   *
   * @return  array
   */
  public static function getCurrentUser(Request $request): array {
    // RETORNA OS DADOS DO USUÁRIO ATUAL
    return self::getUserData($request->userLogged);
  }

  /**
   * Método responsável por validar os campos
   *
   * @param   array  $postVars
   *
   * @return  void
   */
  private static function validateFields(array &$postVars): void {
    // VALIDA OS CAMPOS OBRIGÁTORIOS
    $fields = ['login', 'cpf'];
    foreach ($fields as $field) {
      if (!isset($postVars[$field]))
        throw new \Exception("O campo '$field' é obrigatório", 400);
    }

    // VALIDA OS TIPOS DOS CAMPOS
    $props = [
      'nome'     => 'string',
      'login'    => 'string',
      'senha'    => 'string',
      'email'    => 'string',
      'telefone' => 'string',
      'cpf'      => 'string',
      'tipo'     => 'string'
    ];
    foreach ($props as $prop => $type) {
      if (isset($postVars[$prop]) && gettype($postVars[$prop]) != $type)
        throw new \Exception("Valor inválido para o campo '$prop'! Experado: $type", 400);
    }

    // VALIDA O CPF
    if (!CPF::isvalid(Masker::remove($postVars['cpf'])))
      throw new \Exception("CPF '$postVars[cpf]' inválido", 400);
  }

  /**
   * Método responsável por cadastrar um novo usuário
   *
   * @param   Request  $request
   *
   * @return  array
   */
  public static function setNewUser(Request $request): array {
    // NÃO CADASRAR PELA API, EVITAR BOTS
    throw new \Exception('Não é possível cadastrar usuários pela API', 501);
    
    // POST VARS
    $postVars = $request->getPostVars();

    // VALIDA OS CAMPOS
    self::validateFields($postVars);
    if (!isset($postVars['senha']))
      throw new \Exception("O campo 'senha' é obrigatório", 400);

    // VALIDA O LOGIN (DUPLICAÇÃO)
    if (EntityUser::getUserByLogin($postVars['login']) instanceof EntityUser)
      throw new \Exception("O login '$postVars[login]' já está em uso", 409); #422 303

    // DEFINE O TIPO DO USUÁRIO PARA PADRÃO
    $postVars['tipo'] = null;

    // CADASTRA O NOVO USUÁRIO
    $obUser = self::setUserData($postVars);
    $obUser->insert();

    // RETORNA OS DETALHES DO USUÁRIO CADASTRADO
    return self::getUserData($obUser);
  }

  /**
   * Método responsável por atualizar o usuário atual
   *
   * @param   Request  $request
   *
   * @return  array
   */
  public static function setEditCurrentUser(Request $request): array {
    // POST VARS
    $postVars = $request->getPostVars();

    // VALIDA OS CAMPOS
    self::validateFields($postVars);

    // VALIDA O LOGIN (DUPLICAÇÃO)
    $obUserLogin = EntityUser::getUserByLogin($postVars['login']);
    if ($obUserLogin instanceof EntityUser && $obUserLogin->id != $request->userLogged->id)
      throw new \Exception("O login '$postVars[login]' já está em uso", 409); #422 303

    // DEFINE O TIPO DO USUÁRIO PARA O ATUAL
    $postVars['tipo'] = $request->userLogged->tipo;

    // ATUALIZA O USUÁRIO
    $obUser = self::setUserData($postVars);
    $obUser->id = $request->userLogged->id;
    $obUser->update();

    // RETORNA OS DETALHES DO USUÁRIO ATUALIZADO
    return self::getUserData($obUser);
  }

  /**
   * Método responsável por atualizar a senha de um usuário
   *
   * @param   Request  $request
   *
   * @return  array
   */
  public static function setEditCurrentUserPassword(Request $request): array {
    // POST VARS
    $postVars = $request->getPostVars();

    // VALIDA O CAMPO SENHA
    if (!isset($postVars['senha']))
      throw new \Exception("O campo 'senha' é obrigátorio!", 400);

    // ATUALIZA SOMENTE A SENHA DO USUÁRIO
    $obUser = EntityUser::getUserById($request->userLogged->id);
    $obUser->senha = $postVars['senha'];
    $obUser->update();

    // RETORNA O SUCESSO DA ALTERAÇÃO
    return ['sucesso' => true];
  }

  /**
   * Método responsável por atualizar um usuário
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  array
   */
  public static function setEditUser(Request $request, $id): array {
    // VALIDA O ID DO USUÁRIO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obUser = EntityUser::getUserById($id);
    if (!$obUser instanceof EntityUser)
      throw new \Exception("O usuário($id) não foi encontrado", 404);

    // POST VARS
    $postVars = $request->getPostVars();

    // VALIDA OS CAMPOS
    self::validateFields($postVars);

    // VALIDA O LOGIN (DUPLICAÇÃO)
    $obUserLogin = EntityUser::getUserByLogin($postVars['login']);
    if ($obUserLogin instanceof EntityUser && $obUserLogin->id != $id)
      throw new \Exception("O login '$postVars[login]' já está em uso", 409);

    // ATUALIZA O USUÁRIO
    $obUser = self::setUserData($postVars);
    $obUser->id = $id;
    $obUser->update();

    // RETORNA OS DETALHES DO USUÁRIO ATUALIZADO
    return self::getUserData($obUser);
  }

  /**
   * Método responsável por excluir um usuário
   *
   * @param   int    $id
   *
   * @return  array
   */
  public static function setDeleteUser($id): array {
    // VALIDA O ID DO USUÁRIO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obReason = EntityUser::getUserById($id);
    if (!$obReason instanceof EntityUser)
      throw new \Exception("O usuário($id) não foi encontrado", 404);

    // EXCLUI O USUÁRIO
    $obReason->delete();

    // RETORNA O SUCESSO DO USUÁRIO EXLCUIDO
    return ['sucesso' => true];
  }
}
