<?php

namespace App\Controller\Index;

use App\Http\Request;
use App\Model\Entity\User;
use App\Session\Login as SessionLogin;
use App\Session\Main as SessionMain;
use App\Utils\Alert;
use App\Utils\View;

class Login extends Page {

  /**
   * Método responsável por retornar a mensagem de status
   *
   * @param   Request  $request  
   *
   * @return  string             
   */
  private static function getStatus(Request $request) {
    // QUERY PARAMS
    $queryParams = $request->getQueryParams();

    // STATUS
    if (!isset($queryParams['status'])) return '';

    // MENSAGEM DE STATUS
    switch ($queryParams['status']) {
      case 'usuarioInvalido':
        return Alert::getError('Usuário inválido!');
      case 'senhaInvalida':
        return Alert::getError('Senha inválida!');
    }
  }

  /**
   * Método responsável por retornar o conteúdo (VIEW) do Login
   * 
   * @param   Request  $request
   * 
   * @return  string
   */
  public static function getLogin(Request $request): string {
    // VIEW DO LOGIN
    $content = View::render('formLogin', [
      'status' => self::getStatus($request)
    ]);

    // RENDERIZA VIEW DA PÁGINA
    return parent::getPage(
      'Login',
      $content,
      '',
      styles: [['signin', 'Custom styles for this template']]
    );
  }

  /**
   * Método responsável por definir o login do usuário
   *
   * @param   Request  $request  
   */
  public static function setLogin(Request $request) {
    // POST VARS
    $postVars = $request->getPostVars();
    $usuario  = $postVars['usuario'] ?? '';
    $senha    = $_POST['senha'] ?? '';
    $lembrar  = isset($_POST['lembrar']);

    // BUSCA O USUÁRIO PELO LOGIN
    $obUser = User::getUserByLogin($usuario);
    if (!$obUser instanceof User) $request->getRouter()->redirect('/login?status=usuarioInvalido');

    // VERIFICA A SENHA DO USUÁRIO
    if (!password_verify($senha, $obUser->senha)) $request->getRouter()->redirect('/login?status=senhaInvalida');

    // CRIA A SESSÃO DE LOGIN
    SessionLogin::login($obUser, $lembrar);

    // PÁGINA A SER ACESSADA
    $referer = SessionMain::isSet('referer') ? SessionMain::get('referer') : null;

    // VERIFICA SE É ADMIN
    if (strtoupper($obUser->tipo) == strtoupper(\App\Model\Entity\User::$tipos['admin']))
      $request->getRouter()->redirect($referer ?? '/admin');

    // REDIRECIONA O USUÁRIO PRO DASHBOARD
    $request->getRouter()->redirect($referer ?? '/usuario');
  }


  /**
   * Método reponsável por deslogar o usuário
   *
   * @param   Request  $request  
   */
  public static function setLogout(Request $request) {
    // DESTRÓI A SESSÃO DE LOGIN
    SessionLogin::logout();

    // REDIRECIONA O USUÁRIO PARA A TELA INICIAL
    $request->getRouter()->redirect('/');
  }
}
