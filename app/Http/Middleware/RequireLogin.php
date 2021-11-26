<?php

namespace App\Http\Middleware;

use App\Http\Request;
use App\Session\Login as SessionLogin;
use App\Session\Main as SessionMain;
use Closure;

class RequireLogin implements MiddlewareInterface {

  /**
   * Método responsável por executar o middleware
   *
   * @param   Request  $request  
   * @param   Closure  $next     
   *
   * @return  Closure           
   */
  public function handle(Request $request, Closure $next) {
    // VERIFICA SE O USUÁRIO ESTÁ LOGADO
    if (!SessionLogin::isLogged()) {
      // ADICIONA A PÁGINA A SER ACESSADA NA REFERÊNCIA
      SessionMain::set('referer', $request->getRouter()->getUri());

      // REDIRECIONA PARA A PÁGINA DE LOGIN
      $request->getRouter()->redirect('/login');
    }

    // CONTINUA A EXECUÇÃO
    return $next($request);
  }
}
