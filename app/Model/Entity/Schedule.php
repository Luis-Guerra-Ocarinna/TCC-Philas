<?php

namespace App\Model\Entity;

use WilliamCosta\DatabaseManager\Database;
use PDOStatement;

class Schedule {

  /** @var integer ID do Atendimento */
  public $id;

  /** @var integer Relação ao Motivo do Atendimento */
  public $cod_motivo;

  /** @var string Descrição do Atendimento */
  public $descricao;

  /** @var integer Tempo previsto do Atendimento */
  public $tempo_previsto;

  /** @var string Data que foi marcado (ou atualizado) o Atendimento */
  public $data_marcada;

  /** @var string Data que foi inicado o Atendimento */
  public $data_iniciada;

  /** @var string Data que foi finalizado o Atendimento */
  public $data_finalizada;

  /** @var integer Relação ao Usuário (Atendido) participante do Atendimento */
  public $cod_atendido;

  /** @var integer Relação ao Usuário (Atendente) participante do Atendimento */
  public $cod_atendente;

  /** @var string Tabela atual no banco de dados da Entidade */
  private static $table = "atendimento";

  /**
   * Método responsável por retornar Atendimentos
   *
   * @param   string  $where   
   * @param   string  $order   
   * @param   string  $limit   
   * @param   string  $fields  
   *
   * @return  PDOStatement          
   */
  public static function getSchedules(string $where = null, string $order = null, string $limit = null, string $fields = '*') {
    return (new Database(self::$table))->select($where, $order, $limit, $fields);
  }

  /**
   * Método responsável por retornar um Atendimento combase no seu id
   *
   * @param   int     $id
   * @param   string  $where
   *
   * @return  Schedule
   */
  public static function getScheduleById(int $id, ?string $where = null) {
    return self::getSchedules("id = $id" . ($where ? " AND ($where)"  : ''), $where)->fetchObject(self::class);
  }


  /**
   * Método responsável por cadastrar a intância atual
   *
   * @return  boolean
   */
  public function insert() {
    // INSERE O ATEDIMENTO NO BANCO DE DADOS
    $this->id = (new Database(self::$table))->insert([
      'descricao'       => $this->descricao,
      'cod_motivo'      => $this->cod_motivo,
      'tempo_previsto'  => $this->tempo_previsto,
      'data_marcada'    => $this->data_marcada,
      'data_iniciada'   => $this->data_iniciada,
      'data_finalizada' => $this->data_finalizada,
      'cod_atendido'    => $this->cod_atendido,
      'cod_atendente'   => $this->cod_atendente
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
    // ATUALIZA O ATEDIMENTO NO BANCO DE DADOS
    return (new Database(self::$table))->update('id = ' . $this->id, [
      'descricao'       => $this->descricao,
      'cod_motivo'      => $this->cod_motivo,
      'tempo_previsto'  => $this->tempo_previsto,
      'data_marcada'    => $this->data_marcada,
      'data_iniciada'   => $this->data_iniciada,
      'data_finalizada' => $this->data_finalizada,
      'cod_atendido'    => $this->cod_atendido,
      'cod_atendente'   => $this->cod_atendente
    ]);
  }

  /**
   * Método responsável por excluir a intância atual
   *
   * @return  boolean
   */
  public function delete() {
    // EXCLUI O ATEDIMENTO DO BANCO DE DADOS
    return (new Database(self::$table))->delete('id = ' . $this->id);
  }

  /**
   * Método responsável por excluir os campos com chave estrangeira (usuário)
   *
   * @param   User  $obUser
   *
   * @return  bool
   */
  public static function deleteUserForeignKeys(User $obUser): bool {
    // RESULTADOS DOS ATENDIMENTOS COM O ATENDIDO
    $schdeules = self::getSchedules('cod_atendido = ' . $obUser->id);

    // ALTERA CADA REGISTRO
    while ($obSchedule = $schdeules->fetchObject(self::class)) {
      $obSchedule->cod_atendido = null;
      $obSchedule->descricao .= PHP_EOL . ' - Cliente excluído: ' . $obUser->nome . '(' . $obUser->id . ').';
      $obSchedule->update();
    }

    // RESULTADOS DOS ATENDIMENTOS COM O ATENDENTE
    $schdeules = self::getSchedules('cod_atendente = ' . $obUser->id);

    // ALTERA CADA REGISTRO
    while ($obSchedule = $schdeules->fetchObject(self::class)) {
      $obSchedule->cod_atendente = null;
      $obSchedule->descricao .= PHP_EOL . ' - Atendente excluído: ' . $obUser->nome . '(' . $obUser->id . ').';
      $obSchedule->update();
    }

    // SUCESSO
    return true;
  }

  /**
   * Método responsável por excluir os campos com chave estrangeira (motivo)
   *
   * @param   Reason  $obReason
   *
   * @return  bool
   */
  public static function deleteReasonForeignKeys(Reason $obReason): bool {
    // RESULTADOS DOS ATENDIMENTOS
    $schdeules = self::getSchedules('cod_motivo = ' . $obReason->id);

    // ALTERA CADA REGISTRO
    while ($obSchedule = $schdeules->fetchObject(self::class)) {
      $obSchedule->cod_motivo = null;
      $obSchedule->descricao .= PHP_EOL . ' - Motivo excluído: ' . $obReason->descricao . '(' . $obReason->id . ').';
      $obSchedule->tempo_previsto = $obReason->tempo_previsto;
      $obSchedule->update();
    }

    // SUCESSO
    return true;
  }
}
