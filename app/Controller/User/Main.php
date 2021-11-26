<?php

namespace App\Controller\User;

use App\Http\Request;
use App\Model\Entity\User as EntityUser;
use App\Session\Login as SessionLogin;
use App\Utils\Alert;
use App\Utils\CPF;
use App\Utils\Masker;
use App\Utils\View;
use App\Utils\Email;

class Main extends Page {

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

      case 'atendimentoSucesso':
        return Alert::getSuccess('Atendimento criado com sucesso!', [
          'Confira-o.',
          URL . '/usuario/atendimento/abertos'
        ]);

      case 'atendimentoSucessoConfirmar':
        return Alert::getSuccess('Atendimento criado com sucesso!', [
          'Confira-o.',
          URL . '/usuario/atendimento/confirmar'
        ]);

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
    $request->getRouter()->redirect('/singup?status=' . $status);
  }

  /**
   * Método responsável por retornar o conteúdo (VIEW) da Home
   *
   * @param   Request  $request
   * 
   * @return  string  
   */
  public static function getHome(Request $request): string {
    // VIEW DA HOME
    $content =  View::render('user/home', [
      'status' => self::getStatus($request),
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Início', $content);
  }

  /**
   * Método responsável por retornar o formuário de cadastro
   *
   * @param   Request  $request
   *
   * @return  string            
   */
  public static function getNewUser(Request $request): string {
    // CONTEÚDO DO FORMULÁRIO
    $content = View::render('user/formNew', [
      'status'     => self::getStatus($request)
    ]);

    // RETORNAR A PÁGINA RENDERIZA
    return parent::getPage(
      'Cadastrar-se',
      $content,
      '',
      scripts: [['https://www.google.com/recaptcha/api.js', 'reCAPTCHA', true], ['validateRecaptcha', 'Validação do reCAPTCHA']]
    );
  }

  /**
   * Método responsável por cadastrar um novo usuários no banco
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function setNewUser(Request $request): string {
    // POST VARS
    $postVars = $request->getPostVars();

    // VAFLIDAÇÃO CAPTCHA
    if ($postVars['g-recaptcha-response'] == null || empty($postVars['g-recaptcha-response']))
      throw new \Exception('reCAPTCHA ausente', 403);
    // RESPONSE
    $captcha = $postVars['g-recaptcha-response'];
    // SECRET UNICA
    $secret = getenv('G_CAPTCHA_KEY');
    // IP DO USUÁRIO
    $ip = $_SERVER['REMOTE_ADDR'];
    // CONVERTE A VALIDAÇÂO EM JSON
    $var = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$captcha&remoteip=$ip");
    $resposta = json_decode($var, true);

    if (!$resposta['success'])
      throw new \Exception('Erro com o reCAPTCHA', 403);

    // DADOS DO NOVO USUÁRIO
    $nome     = (isset($postVars['nome']) and isset($postVars['sobrenome'])) ? "$postVars[nome] $postVars[sobrenome]" : '';
    $senha    = $postVars['senha'];
    $telefone = $postVars['telefone'] ?? '';
    $cpf      = Masker::remove($postVars['cpf']);
    $login    = $postVars['usuario'];
    $email    = $postVars['email'];

    // VALIDA O LOGIN DO USUÁRIO
    $obUserLogin = EntityUser::getUserByLogin($login);
    if ($obUserLogin instanceof EntityUser) self::returnStatus($request, 'usuarioExistente');

    // VALIDA O CPF
    if (!CPF::isvalid($cpf)) self::returnStatus($request, 'cpfInvalido');

    // NOVA INSTÂNCIA DA ENTIDADE USUÁRIO
    $obUser = new EntityUser;

    $obUser->nome     = $nome;
    $obUser->senha    = $senha;
    $obUser->telefone = $telefone;
    $obUser->cpf      = $cpf;
    $obUser->login    = $login;
    $obUser->email    = $email;

    // ENVIA CONFIRMAÇÃO EMAIL
    Email::sendConfirmEmail($obUser);

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/verificarEmail');
  }
};
