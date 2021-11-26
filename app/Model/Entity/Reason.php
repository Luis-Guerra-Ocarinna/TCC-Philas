<?php

namespace App\Model\Entity;

use WilliamCosta\DatabaseManager\Database;
use PDOStatement;

class Reason {

  /** @var integer ID do motivo do agendamento */
  public $id;

  /** @var string Descrição do motivo do agendamento */
  public $descricao;

  /** @var integer Tempo Previsto para o motivo do agendamento específico */
  public $tempo_previsto;

  /** @var string Tabela atual no banco de dados da Entidade */
  private static $table = "motivo";

  /**
   * Método responsável por retornar Motivos
   *
   * @param   string  $where   
   * @param   string  $order   
   * @param   string  $limit   
   * @param   string  $fields  
   *
   * @return  PDOStatement          
   */
  public static function getReasons(string $where = null, string $order = null, string $limit = null, string $fields = '*') {
    return (new Database(self::$table))->select($where, $order, $limit, $fields);
  }

  /**
   * Método responsável por retornar um Motivo combase no seu id
   *
   * @param   integer  $id  
   *
   * @return  Reason    
   */
  public static function getReasonById(int $id) {
    return self::getReasons("id = $id")->fetchObject(self::class);
  }


  /**
   * Método responsável por cadastrar a intância atual
   *
   * @return  boolean
   */
  public function insert() {
    // INSERE O MOTIVO NO BANCO DE DADOS
    $this->id = (new Database(self::$table))->insert([
      'descricao'      => $this->descricao,
      'tempo_previsto' => $this->tempo_previsto
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
    // ATUALIZA O MOTIVO NO BANCO DE DADOS
    return (new Database(self::$table))->update('id = ' . $this->id, [
      'descricao'      => $this->descricao,
      'tempo_previsto' => $this->tempo_previsto
    ]);
  }

  /**
   * Método responsável por excluir a intância atual
   *
   * @return  bool
   */
  public function delete(): bool {
    // ALTERA OS CAMPOS COM CHAVE ESTRANGEIRA
    if (Schedule::deleteReasonForeignKeys($this))
      // EXCLUI O MOTIVO DO BANCO DE DADOS
      return (new Database(self::$table))->delete('id = ' . $this->id);
  }
}
