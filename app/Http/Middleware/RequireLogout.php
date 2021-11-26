<?php

namespace App\Http\Middleware;

use App\Http\Request;
use App\Session\Login as SessionLogin;
use App\Session\Main as SessionMain;
use Closure;

class RequireLogout implements MiddlewareInterface {

  /**
   * Método responsável por executar o middleware
   *
   * @param   Request  $request  
   * @param   Closure  $next     
   *
   * @return  Closure           
   */
  public function handle(Request $request, Closure $next) {
    // VERIFICA SE O USUÁRIO ESTÁ DESLOGADO
    if (SessionLogin::isLogged()) {
      // BUSCA PELO USUÁRIO LOGADO
      $userLogged = $request->userLogged ?? SessionMain::get('user_logged');

      // REDIRECIONA BASEADO NO TIPO DO USU
      if (strtoupper($userLogged->tipo) == strtoupper(\App\Model\Entity\User::$tipos['admin']))
        $request->getRouter()->redirect('/admin');
      else
        $request->getRouter()->redirect('/usuario');
    }

    // CONTINUA A EXECUÇÃO
    return $next($request);
  }
}
