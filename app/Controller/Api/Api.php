<?php

namespace App\Controller\Api;

use App\Http\Request;
use App\Model\Entity\Us as EntityUs;
use App\Model\Entity\User as EntityUser;
use App\Session\Login as SessionLogin;
use Firebase\JWT\JWT;
use WilliamCosta\DatabaseManager\Pagination;

use function App\Utils\casttoclass;

class Api {

  /**
   * Método responsável por retornar os detalhes da API para
   *
   * @return  array  
   */
  public static function getDetails() {
    // BUSCA AS NOSSAS IRMOFAÇÕES
    $obUs = new EntityUs;

    // RETORNAR OS DATALHES DA API
    return [
      'nome'       => "API — $obUs->nome",
      'versao'     => 'v1.0.0',
      'autores'    => $obUs->autores,
      'professoes' => [],
      'base'       => [
        'nome'     => 'William Costa',
        'GitHub'   => 'https://github.com/william-costa/william-costa',
        'refencia' => 'https://youtube.com/playlist?list=PL_zkXQGHYosGQwNkMMdhRZgm4GjspTnXs'
      ]
    ];
  }

  /**
   * Método responsável por retornar a paginação
   *
   * @param   Request     $request  
   * @param   Pagination  $obPagination
   *
   * @return  array             
   */
  protected static function getPagination(Request $request, Pagination $obPagination) {
    // QUERY PARAMS
    $queryParams = $request->getQueryParams();

    // PÁGINAS
    $pages = $obPagination->getPages();

    // RETORNA 
    return [
      'atual' => (int) ($queryParams['page'] ?? 1),
      'total' => !empty($pages) ? count($pages) : 1
    ];
  }

  /**
   * Método responsável por gerar e retornar um token JWT
   *
   * @param   Request  $request  
   *
   * @return  array             
   */
  public static function genarateToken(Request $request) {
    // POST VARS
    $postVars = $request->getPostVars();

    // VALIDA OS CAMPOS OBRIGÁTORIOS
    if (!isset($postVars['usuario']) || !isset($postVars['senha'])) throw new \Exception("Os campos 'usuario' e 'senha' são obrigatórios", 400);

    // BUSCA USUÁRIO PELO LOGIN
    $obUser = EntityUser::getUserByLogin($postVars['usuario']);

    // VALIDA O LOGIN
    if (!$obUser instanceof EntityUser) throw new \Exception("Usuário inválido", 401);

    // VALIDA A SENHA
    if (!password_verify($postVars['senha'], $obUser->senha)) throw new \Exception("Senha inválida", 401);

    // PAYLOAD
    $payload = [
      'id'    => $obUser->id,
      'login' => $obUser->login
    ];

    // RETORNA O TOKEN GERADO
    return [
      'token' => JWT::encode($payload, getenv('JWT_KEY'))
    ];
  }

  /**
   * Método responsável por cadastrar um novo usuário pós confirmação de email
   *
   * @param   Request  $request
   *
   * @return  never
   */
  public static function confirmEmail(Request $request): void {
    // QUERY PARAMS
    $queryParams = $request->getQueryParams();

    // VALIDA O ENVIO DO TOKEN
    if (!isset($queryParams['token']))
      throw new \Exception('Token é obrigatório', 403);

    // DECODA O TOKEN
    try {
      $jwt = JWT::decode($queryParams['token'], getenv('JWT_KEY'), ['HS256']);
    } catch (\Exception $e) {
      throw new \Exception('Token inválido', 403);
    }

    // INSERE OS DADOS PÓS CONFIRMAÇÃO
    $obUser = casttoclass($jwt, EntityUser::class);
    $obUser->insert();

    // LOGA O NOVO USUÁRIO
    SessionLogin::login($obUser);

    // REDIRECIONA-O (PARA UM PAGINA QUE DIFERE 'COMUM' DE 'ADMIN')
    $request->getRouter()->redirect('/login');
  }

  /**
   * Método responsável por alterar o email do usuário
   *
   * @param   Request  $request
   *
   * @return  never
   */
  public static function changeEmail(Request $request): void {
    // QUERY PARAMS
    $queryParams = $request->getQueryParams();

    // VALIDA O ENVIO DO TOKEN
    if (!isset($queryParams['token']))
      throw new \Exception('Token é obrigatório', 403);

    // DECODA O TOKEN
    try {
      $jwt = JWT::decode($queryParams['token'], getenv('JWT_KEY'), ['HS256']);
    } catch (\Exception $e) {
      throw new \Exception('Token inválido', 403);
    }

    // INSERE OS DADOS PÓS CONFIRMAÇÃO
    $obUser = EntityUser::getUserById($request->userLogged->id);
    $obUser->email = $jwt;
    $obUser->senha = null; # ignora a senha
    $obUser->update();

    // LOGA O NOVO USUÁRIO
    SessionLogin::login($obUser);

    // REDIRECIONA-O (PARA UM PAGINA QUE REINICIA O LOGIN)
    $request->getRouter()->redirect('/login');
  }
}
