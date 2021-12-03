<?php

namespace App\Model\Entity;

use WilliamCosta\DatabaseManager\Database;
use PDOStatement;
use stdClass;

class User {

  /** @var integer ID do usuário */
  public $id;

  /** @var string Nome do usuário */
  public $nome;

  /** @var string Login do usuário */
  public $login;

  /** @var string Senha do usuário */
  public $senha;

  /** @var string E-mail do usuário */
  public $email;

  /** @var string Telefone do usuário */
  public $telefone;

  /** @var string CPF do usuário */
  public $cpf;

  /** @var string Tipo relacionado as permissoões do usuário */
  public $tipo;

  /** @var array Tipos possíveis para os usuários*/
  public static $tipos = ['admin' => 'Admin', 'default' => 'Comum'];

  /** @var string Tabela atual no banco de dados da Entidade */
  private static $table = "usuario";

  /**
   * Método responsável por retornar Usuários
   *
   * @param   string  $where   
   * @param   string  $order   
   * @param   string  $limit   
   * @param   string  $fields  
   *
   * @return  PDOStatement     
   */
  public static function getUsers(string $where = null, string $order = null, string $limit = null, string $fields = '*') {
    return (new Database(self::$table))->select($where, $order, $limit, $fields);
  }

  /**
   * Método reponsável por retornar um Usuário com base em seu login
   *
   * @param   string  $login  
   *
   * @return  User          
   */
  public static function getUserByLogin(string $login) {
    $escape = fn ($v) => str_replace(['\\', '\'', '"'], ['\\\\', '\\\'', '\\"'], $v);

    return self::getUsers("`login` = '{$escape($login)}'")->fetchObject(self::class);
  }

  /**
   * Método responsável por retornar um Usuário combase no seu id
   *
   * @param   integer  $id  
   *
   * @return  User    
   */
  public static function getUserById(int $id) {
    return self::getUsers("id = $id")->fetchObject(self::class);
  }


  /**
   * Método responsável por cadastrar a intância atual
   *
   * @return  boolean
   */
  public function insert() {
    // INSERE O USUÁRIO NO BANCO DE DADOS
    $this->id = (new Database(self::$table))->insert([
      'nome'     => $this->nome,
      'login'    => $this->login,
      'senha'    => password_hash($this->senha, PASSWORD_DEFAULT),
      'email'    => $this->email,
      'telefone' => $this->telefone,
      'cpf'      => $this->cpf,
      'tipo'     => $this->tipo ?? self::$tipos['default']
    ]);

    // SUCESSO
    return true;
  }

  /**
   * Método responsável por atualizar a intância atual
   *
   * @return  boolean
   */
  public function update() {
    // DADOS DO USUÁRIO
    $userData = [
      'nome'     => $this->nome,
      'login'    => $this->login,
      'email'    => $this->email,
      'telefone' => $this->telefone,
      'cpf'      => $this->cpf,
      'tipo'     => $this->tipo ?? self::$tipos['default']
    ];

    // SE NÃO HOUVER SENHA, IGNORE-A
    if (isset($this->senha)) $userData['senha'] = password_hash($this->senha, PASSWORD_DEFAULT);

    // ATUALIZA O USUÁRIO NO BANCO DE DADOS
    return (new Database(self::$table))->update('id = ' . $this->id, $userData);
  }

  /**
   * Método responsável por excluir a intância atual
   *
   * @return  boolean
   */
  public function delete() {
    // ALTERA OS CAMPOS COM CHAVE ESTRANGEIRA
    if (Schedule::deleteUserForeignKeys($this))
      // EXCLUI O USUÁRIO DO BANCO DE DADOS
      return (new Database(self::$table))->delete('id = ' . $this->id);
  }

  /**
   * Método responsável por valigar um Token
   *
   * @param   stdClass  $token
   *
   * @return  boolean
   */
  public function isValidToken(stdClass $token) {
    // VALIDA: ID
    if ($token->id != $this->id) return false;

    // VALIDA: NOME
    if (isset($token->nome)) if ($token->nome != $this->nome) return false;

    // VALIDA: LOGIN
    if ($token->login != $this->login) return false;

    // VALIDA: EMAIL
    if (isset($token->email)) if ($token->email != $this->email) return false;

    // VALIDA: TELEFONE
    if (isset($token->telefone)) if ($token->telefone != $this->telefone) return false;

    // VALIDA: CPF
    if (isset($token->cpf)) if ($token->cpf != $this->cpf) return false;

    // VALIDA: TIPO
    if (isset($token->tipo)) if ($token->tipo != $this->tipo) return false;

    // TUDO VÁLIDO
    return true;
  }
}
