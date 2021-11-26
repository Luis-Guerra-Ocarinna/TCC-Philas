<?php

namespace App\Session;

use App\Model\Entity\User as EntityUser;
use Firebase\JWT\JWT;

class Login {

  /**
   * Método responsável por criar o login do usuário
   *
   * @param   EntityUser    $obUser
   * @param   boolean       $remember 
   *
   * @return  boolean        
   */
  public static function login(Object $obUser, bool $remember = false): bool {
    // DEFINE A SESSÃO DO USUÁRIO
    Main::set('user_logged', $obUser);

    // DEFINE O TOKEN EM COOKIES
    $payload = [
      'id'    => $obUser->id,
      'login' => $obUser->login
    ];

    // ENCODA O TOKEN
    $jwt = JWT::encode($payload, getenv('JWT_KEY'));

    // SALVA NOS COOKIES DE SESSÃO OU POR UM ANO
    setcookie('ph_login-token', $jwt, $remember ? time() + 86400 * 365 : 0, '/');

    // SUCESSO
    return true;
  }

  /**
   * Método reponsável por verificar se o usuário está logado
   *
   * @return  boolean
   */
  public static function isLogged(): bool {
    // BUSCA PELA SESSÃO DO USUÁRIO
    $return = Main::isSet('user_logged');

    // BUSCA POR TOKEN NOS COOKIES
    if (!$return && isset($_COOKIE['ph_login-token'])) {
      // VERIFICA SE O TOKEN É VÁLIDO
      try {
        // DECODE
        $jwt = JWT::decode($_COOKIE['ph_login-token'], getenv('JWT_KEY'), ['HS256']);

        // VALIDA OS DADOS FORNECIDOS NO JWT
        $obUser = EntityUser::getUserByLogin($jwt->login);
        if (!$obUser) return false;
        if (!$obUser->isValidToken($jwt)) throw new \Exception();

        // RENOVA O LOGIN
        self::login($jwt, true);

        // ESTÁ LOGADO
        return true;
      } catch (\Exception $e) {
        throw new \Exception("Token inválido", 403); // TODO: Gerar algum erro interno ou ao usuario
      }
    }

    // RETORNA A VERIFICAÇÃO
    return $return;
  }

  /**
   * Método reponsável por executar o logout do usuário
   *
   * @return  boolean
   */
  public static function logout(): bool {
    // DESLOGA O USUÁRIO
    Main::delete('user_logged', 'referer');
    setcookie('ph_login-token', '', 1, '/');

    // SUCESSO
    return true;
  }
}
