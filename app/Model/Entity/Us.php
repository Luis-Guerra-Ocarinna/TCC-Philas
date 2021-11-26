<?php

namespace App\Model\Entity;

use App\Utils\JSONFile;

use function App\Utils\casttoclass;

class Us {

  /** @var string $nome Nome da nossa organazação */
  public $nome;

  /** @var string $descricao Descrição da nossa organização */
  public $descricao;

  /** @var Autor[] $autores Nossos nomes e contatos */
  public $autores;

  /** @param Content $conteudo Conteúdo do index */
  public $conteudo;

  /** @var string $__file Arquivo de armazenamento */
  private static $__file;

  /**
   * Método responsável por setar o caminho pra o JSON
   *
   * @param   string  $file
   */
  public static function config(string $file) {
    self::$__file = $file;
  }

  /**
   * Método responsável por carregar a classe a partir o JSON
   */
  public function __construct() {
    // OBTÉM OS DADOS
    $data = (new SGBD(self::$__file))->select();

    // SETA CADA VALOR COM SUA REGRA

    $this->nome = $data->nome;

    $this->descricao = $data->descricao;

    foreach ($data->autores as $autor => $content) {
      $this->autores[$autor] = casttoclass($content, Autor::class);
    }

    $this->conteudo = casttoclass($data->conteudo, Conteudo::class);
  }

  /**
   * Método responsável por atualizar os dados
   */
  public function update() {
    (new SGBD(self::$__file))->update($this);
  }
}

/**
 * Classe responsável por controlar JSON como DB
 */
class SGBD {
  /** @var JSONFile $jsonfile JSON a ser manipulado*/
  private JSONFile $jsonfile;

  /**
   * Método responsável por carregar o JSON
   *
   * @param   string  $jsonfile
   */
  public function __construct(string $jsonfile) {
    $this->jsonfile = new JSONFile($jsonfile);
  }

  /**
   * Método responsável por retornar o JSON
   *
   * @return  mixed
   */
  public function select(): mixed {
    return $this->jsonfile;
  }

  /**
   * Método responsável por atualizar os dados
   *
   * @param   object|array   $values
   */
  public function update(object|array $values) {
    // DEFINI CADA VALOR
    foreach ($values as $k => $v) {
      $this->jsonfile->$k = $v;
    }

    // SALVA
    $this->jsonfile->save();
  }
}

class Autor {
  /** @var string $nome Nome do(a) Autor(a) */
  public string $nome;

  /** @var string $emial Email do(a) Autor(a) */
  public string $email;

  /** @var string $descricao Decrição do(a) Autor(a) */
  public string $descricao;
}

class Conteudo {
  /** @var string $titulo Títuto do Conteúdo */
  public string $titulo;

  /** @var string $texto "Conteúdo" do Conteúdo */
  public string $texto;

  /** @var string $imagem Imagem do Conteúdo */
  public string $imagem;
}
