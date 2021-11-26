<?php

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Model\Entity\User as EntityUser;
use Closure;
use Firebase\JWT\JWT;

class ApiAuth implements MiddlewareInterface {

  /**
   * Método responsável por retonar se há uma autenticação básica
   *
   * @param   Request  $request
   * 
   * @return  boolean
   */
  private function hasBasicAuth(Request $request) {
    // HEADERS
    $headers = $request->getHeaders();

    // VERIFICA SE HÁ A AUTENTICAÇÃO
    if (!isset($headers['Authorization']) or !str_contains($headers['Authorization'], 'Basic')) return false;

    // VERIFICA SE A AUTENTICAÇÃO É VÁLIDA
    if (!isset($_SERVER['PHP_AUTH_USER']) or !isset($_SERVER['PHP_AUTH_PW'])) throw new \Exception('[Basic Auth] inválida', 400);

    // HÁ E É VÁLDIA
    return true;
  }

  private function hasBearer(Request $request) {
    // HEADER
    $headers = $request->getHeaders();

    // VERIFICA SE HÁ A AUTENTICAÇÃO
    if (!isset($headers['Authorization']) or !str_contains($headers['Authorization'], 'Bearer')) return false;

    // VERIFICA SE A AUTENTICAÇÃO É VÁLIDA
    if (empty(str_replace('Bearer ', '', $headers['Authorization']))) throw new \Exception('[Bearer Auth] inválida', 400);

    // HÁ E É VÁLDIA
    return true;
  }

  /**
   * Método responsável por verificar a autenticação básica, podendo retornar uma instância de usuário
   *
   * @return  EntityUser|void
   */
  private function getBasicAuthUser() {
    // BUSCA USUÁRIO PELO LOGIN
    $obUser = EntityUser::getUserByLogin($_SERVER['PHP_AUTH_USER']);

    // VALIDA O LOGIN FORNECIDO
    if (!$obUser instanceof EntityUser) throw new \Exception('Usuário inválido', 401);

    // RETORNA A ENTIDADE USUÁRIO
    return $obUser;
  }

  /**
   * Método responsável por verificar a autenticação titular, podendo retornar uma instância de usuário
   *
   * @param   string  $jwt
   * 
   * @return  EntityUser|void  
   */
  private function getBearerAuthUser(string $jwt) {
    // VALIDA O TOKEN FORNECIDO
    try {
      // DECODE
      $decoded = JWT::decode($jwt, getenv('JWT_KEY'), ['HS256']);

      // VALIDA OS DADOS FORNECIDOS NO JWT
      $obUser = EntityUser::getUserByLogin($decoded->login);
      if (!$obUser->isValidToken($decoded)) throw new \Exception();

      // RETORNA A ENTIDADE USUÁRIO
      return $obUser;
    } catch (\Exception $e) {
      throw new \Exception('Token inválido', 403); // TODO: Gerar um aviso ao usuário
    }
  }

  /**
   * Método responsável por validar o acesso via HTTP BASIC AUTH
   *
   * @param   Request    $request
   */
  private function basicAuth(Request $request) {
    // VERIFICA O LOGIN RECEBIDO
    if ($obUser = $this->getBasicAuthUser()) {
      // VALIDA A SENHA FORNECIDA
      if (!password_verify($_SERVER['PHP_AUTH_PW'], $obUser->senha)) throw new \Exception('Senha inválida', 401);

      // LOGA O USUÁRIO (SOMENTE NA REQUISIÇÃO ATUAL)
      $request->userLogged = $obUser;
    }
  }

  /**
   * Método responsável por validar o acesso via HTTP BEARER AUTH (JWT)
   *
   * @param   Request    $request
   */
  private function bearerAuth(Request $request) {
    // TOKEN PURO EM JWT
    $jwt = str_replace('Bearer ', '', $request->getHeaders()['Authorization']);

    // VERIFICA O TOKEN RECEBIDO E LOGA O USUÁRIO (SOMENTE NA REQUISIÇÃO ATUAL)
    $request->userLogged = $this->getBearerAuthUser($jwt);
  }

  /**
   * Método responsável por executar o middleware
   *
   * @param   Request  $request  
   * @param   Closure  $next     
   *
   * @return  Closure           
   */
  public function handle(Request $request, Closure $next) {
    // BUSCA PRO AUTENTICAÇÕES
    if ($this->hasBasicAuth($request)) {
      // REALIZA A VALIDAÇÃO DO ACESSO VIA BASIC AUTH
      $this->basicAuth($request);
    } else if ($this->hasBearer($request)) {
      // REALIZA A VALIDAÇÃO DO ACESSO VIA BEARER AUTH
      $this->bearerAuth($request);
    } else { # NÃO HOUVE AUTENTICAÇÃO
      throw new \Exception('Necessária autentição [Basic Auth] ou [Bearer Token]', 401);
    }

    // EXECUTA O PRÓXIMO NÍVEL DO MIDDLEWARE
    return $next($request);
  }
}
