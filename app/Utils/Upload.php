<?php

namespace App\Utils;

/**
 * Classe responsável por controlar uploads de arquivos
 */
// TODO: retirar a gambiarra (comversão de imagemens para jpg)
class Upload {

  /**
   * Nome do arquivo (sem extensão)
   * @var string
   */
  private $name;

  /**
   * Extensão do arquivo (sem ponto)
   * @var string
   */
  private $extension;

  /**
   * Tipo do arquivo
   * @var string
   */
  private $type;

  /**
   * Nome temporário/Caminho temporário do arquivo
   * @var string
   */
  private $tmpName;

  /**
   * Código de erro do upload
   * @var integer
   */
  private $error;

  /**
   * Tamanho do arquivo
   * @var integer
   */
  private $size;

  /**
   * Contador de multiplicação de arquivo
   * @var integer
   */
  private $duplicates = 0;

  /**
   * Construtor da classe
   * @param   array  $file  $_FILES[campo]
   */
  public function __construct($file) {
    $this->type    = $file['type'];
    $this->tmpName = $file['tmp_name'];
    $this->error   = $file['error'];
    $this->size    = $file['size'];

    $info            = pathinfo($file['name']);
    $this->name      = $info['filename'];
    $this->extension = /* $info['extension'] */ 'jpg';
  }

  /**
   * Método responsável por alterar o nome do arquivo
   * @param   string  $name  
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Método responsável por gerar um novo nome aleatorio
   */
  public function generateNewName() {
    $this->name = time() . '-' . rand(100000, 999999) . '-' . uniqid();
  }

  /**
   * Método responsável por retornar o nome do arquivo com sua extensão
   * @return  string  
   */
  public function getBaseName() {
    // VALIDA EXTENSÃO
    $extension = strlen($this->extension) ? '.' . $this->extension : '';

    // VALIDA DUPLICAÇÃO
    $duplicates = $this->duplicates > 0 ? '-' . $this->duplicates : '';

    // RETORNA O NOME COMPLETO
    return $this->name . $duplicates . $extension;
  }

  /**
   * Método responsável por obter um nome possivel para o arquivo
   * @param   string  $dir        
   * @param   boolean  $overwrite  
   * @return  string              
   */
  public function getPossibleBaseName($dir, $overwrite) {
    // SOBRESCREVER ARQUIVO
    if ($overwrite) return $this->getBaseName();

    // NÃO PODE SOBRESCREVER ARQUIVO
    $basename = $this->getBaseName();

    // VERIFICAR DUPLICAÇÃO
    if (!file_exists($dir . '/' . $basename)) {
      return $basename;
    }

    // INCREMENTAR DUPLICAÇÕES
    $this->duplicates++;

    // RETORNO O PRÓPRIO MÉTODO
    return $this->getPossibleBaseName($dir, $overwrite);
  }

  /**
   * Método responsável por mover o arquivo de upload
   * @param   string  $dir  
   * @param   boolean $overwrite
   * @return  boolean        
   */
  public function upload($dir, $overwrite = true) {
    // VERIFICA ERRO
    if ($this->error != 0) return false;

    // CAMINHO COMPLETO DO DESTINO
    $path = $dir . '/' . $this->getPossibleBaseName($dir, $overwrite);

    // MOVE O ARQUIVO PARA A PASTA DE DESTINO
    return move_uploaded_file($this->tmpName, $path);
  }

  /* private function moveAsJPG($path) {
    // jpg, png, gif or bmp?
    $exploded = explode('.', $path);
    $ext = $exploded[count($exploded) - 1];

    if (preg_match('/jpg|jpeg/i', $ext))
      $imageTmp = imagecreatefromjpeg($this->tmpName);
    else if (preg_match('/png/i', $ext))
      $imageTmp = imagecreatefrompng($this->tmpName);
    else if (preg_match('/gif/i', $ext))
      $imageTmp = imagecreatefromgif($this->tmpName);
    else if (preg_match('/bmp/i', $ext))
      $imageTmp = imagecreatefrombmp($this->tmpName);
    else
      return 0;

    imagejpeg($imageTmp, $path, 100);
    imagedestroy($imageTmp);

    return 1;
  } */
}
