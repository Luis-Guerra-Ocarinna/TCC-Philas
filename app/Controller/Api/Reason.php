<?php

namespace App\Controller\Api;

use App\Http\Request;
use App\Model\Entity\Reason as EntityReason;
use WilliamCosta\DatabaseManager\Pagination;

class Reason extends Api {

  /**
   * Método responsável por renderizar e retornar os dados do motivo
   *
   * @param   EntityReason  $obReason
   *
   * @return  array
   */
  private static function getReasonData(EntityReason $obReason): array {
    // CAST NOS VALORES NUMÉRICOS
    foreach ($obReason as &$value) {
      $value = is_numeric($value) ? (int) $value : $value;
    }

    // RETORNA A ENTIDADE MOTIVO RENDERIZADA
    return (array) $obReason;
  }

  /**
   * Método responsável por armazenar e retornar os dados do motivo
   *
   * @param   array  $reasonData
   *
   * @return  EntityReason
   */
  private static function setReasonData(array $reasonData): EntityReason {
    // INSTANCIA A ENTIDADE MOTIVO
    $obReason = new EntityReason;

    // RETIRA O ID DO LOOP
    unset($obReason->id);

    // CADASTRA OS DADOS
    foreach ($obReason as $prop => &$value) {
      // SETA OS VALORES
      $value = is_numeric($reasonData[$prop]) ? (int) $reasonData[$prop] : $reasonData[$prop];
    }

    // RETORNA OS DADOS
    return $obReason;
  }

  /**
   * Método responsável por retornar os itens de Motivos para a API
   *
   * @param   Request     $request
   * @param   Pagination  $obPagination
   *
   * @return  array
   */
  private static function getReasonsItems(Request $request, ?Pagination &$obPagination): array {
    // MOTIVOS
    $itens = [];

    // QUANTIDADE TOTAL DE REGISTRO
    $quantidadeTotal = EntityReason::getReasons(null, null, null, 'COUNT(*) as qtd')->fetchObject()->qtd;

    // PÁGINA ATUAL
    $queryParams = $request->getQueryParams();
    $paginaAtual = $queryParams['page'] ?? 1;

    // LIMITE POR PÁGINA
    $limit = $queryParams['per_page'] ?? 3;
    $limit = is_numeric($limit) ? $limit : 3;

    // VALIDANDO SE DEVE MOSTRAS TODOS OS REGISTROS
    $limit = $limit > 0 ? $limit : $quantidadeTotal;

    // INSTÂNCIA DE PAGINAÇÃO
    $obPagination = new Pagination($quantidadeTotal, $paginaAtual, $limit);

    // RESULTADOS DA PÁGINA
    $results = EntityReason::getReasons(null, 'id ASC', $obPagination->getLimit());

    // RENDERIZA O(S) ITEM(S)
    while ($obReason = $results->fetchObject(EntityReason::class)) {
      $itens[] = self::getReasonData($obReason);
    }

    // RETORNA O(S) MOTIVO(S)
    return $itens;
  }

  /**
   * Método responsável por retornar os motivos cadastrados
   *
   * @param   Request  $request
   *
   * @return  array
   */
  public static function getReasons(Request $request): array {
    return [
      'motivos' => self::getReasonsItems($request, $obPagination),
      'paginacao' => parent::getPagination($request, $obPagination)
    ];
  }

  /**
   * Método responsável por validar os campos
   *
   * @param   array  $postVars
   *
   * @return  void
   */
  private static function validateFields(array $postVars): void {
    // VALIDA OS CAMPOS OBRIGÁTORIOS
    $fields = ['descricao', 'tempo_previsto'];
    foreach ($fields as $field) {
      if (!isset($postVars[$field]))
        throw new \Exception("O campo '$field' é obrigatório", 400);
    }

    // VALIDA OS TIPOS DOS CAMPOS
    $props = [
      'descricao'      => 'string',
      'tempo_previsto' => 'integer'
    ];
    foreach ($props as $prop => $type) {
      if (isset($postVars[$prop]) && gettype($postVars[$prop]) != $type)
        throw new \Exception("Valor inválido para o campo '$prop'! Experado: $type", 400);
    }
  }

  /**
   * Método responsável por cadastrar um novo motivo
   *
   * @param   Request  $request
   *
   * @return  array
   */
  public static function setNewReason(Request $request): array {
    // POST VARS
    $postVars = $request->getPostVars();

    // VALIDA OS CAMPOS
    self::validateFields($postVars);

    // VALIDA A DESCRIÇÃO (DUPLICAÇÃO)
    $obReasonDescription = EntityReason::getReasons("`descricao` = '$postVars[descricao]'");
    $obReasonDescription = $obReasonDescription->fetchObject(EntityReason::class);
    if ($obReasonDescription instanceof EntityReason)
      throw new \Exception("A descricao '$postVars[descricao]' já existe", 409); #422 303

    // CADASTRA O NOVO MOTIVO
    $obReason = self::setReasonData($postVars);
    $obReason->insert();

    // RETORNA OS DETALHES DO MOTIVO
    return self::getReasonData($obReason);
  }

  /**
   * Método responsável por atualizar um motivo
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  array
   */
  public static function setEditReason(Request $request, $id): array {
    // VALIDA O ID DO MOTIVO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obReason = EntityReason::getReasonById($id);
    if (!$obReason instanceof EntityReason)
      throw new \Exception("O motivo($id) não foi encontrado", 404);

    // POST VARS
    $postVars = $request->getPostVars();

    // VALIDA OS CAMPOS
    self::validateFields($postVars);

    // VALIDA O MOTIVO (DUPLICAÇÃO)
    $obReasonDescription = EntityReason::getReasons("`descricao` = '$postVars[descricao]'");
    $obReasonDescription = $obReasonDescription->fetchObject(EntityReason::class);
    if ($obReasonDescription instanceof EntityReason && $obReasonDescription->id != $id)
      throw new \Exception("A descricao '$postVars[descricao]' já existe", 409);

    // ATUALIZA O MOTIVO
    $obReason = self::setReasonData($postVars);
    $obReason->id = $id;
    $obReason->update();

    // RETORNA OS DETALHES DO MOTIVO ATUALIZADO
    return self::getReasonData($obReason);
  }

  /**
   * Método responsável por excluir um motivo
   *
   * @param   int      $id
   *
   * @return  array
   */
  public static function setDeleteReason($id): array {
    // VALIDA O ID DO MOTIVO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obReason = EntityReason::getReasonById($id);
    if (!$obReason instanceof EntityReason)
      throw new \Exception("O motivo($id) não foi encontrado", 404);

    // EXCLUI O MOTIVO
    $obReason->delete();

    // RETORNA O SUCESSO DO MOTIVO EXLCUIDO
    return ['sucesso' => true];
  }
}
