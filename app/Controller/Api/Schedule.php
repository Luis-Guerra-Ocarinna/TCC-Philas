<?php

namespace App\Controller\Api;

use App\Http\Request;
use App\Model\Entity\Reason as EntityReason;
use App\Model\Entity\Schedule as EntitySchedule;
use App\Model\Entity\User as EntityUser;
use App\Utils\Date;
use WilliamCosta\DatabaseManager\Pagination;

class Schedule extends Api {

  /**
   * Método responsável por renderizar e retornar os dados do atendimento
   *
   * @param   EntitySchedule  $obSchedule
   *
   * @return  array
   */
  private static function getScheduleData(EntitySchedule $obSchedule): array {
    // CAST NOS VALORES NUMÉRICOS
    foreach ($obSchedule as &$value) {
      $value = is_numeric($value) ? (float) $value : $value;
    }

    // RETORNA A ENTIDADE ATENDIMETO RENDERIZADA
    return (array) $obSchedule;
  }

  /**
   * Método responsável por armazenar e retornar os dados do atendimento
   *
   * @param   array  $scheduleData
   *
   * @return  EntitySchedule
   */
  private static function setScheduleData(array $scheduleData): EntitySchedule {
    // INSTANCIA A ENTIDADE ATENDIMETO
    $obSchedule = new EntitySchedule;

    // RETIRA O ID DO LOOP
    unset($obSchedule->id);

    // CADASTRA OS DADOS
    foreach ($obSchedule as $prop => &$value) {
      // IGNORA OS NULOS
      if (!isset($scheduleData[$prop]) or empty($scheduleData[$prop])) continue;

      // SETA OS VALORES
      $value = is_numeric($scheduleData[$prop]) ? (float) $scheduleData[$prop] : $scheduleData[$prop];
    }

    // RETORNA OS DADOS
    return $obSchedule;
  }

  /**
   * Método responsável por retornar os itens de Atendimentos para a API
   *
   * @param   Request     $request
   * @param   Pagination  $obPagination
   * @param   string      $where
   *
   * @return  array
   */
  private static function getSchedulesItems(Request $request, ?Pagination &$obPagination, string $where = null): array {
    // ATENDIMETOS
    $itens = [];

    // QUANTIDADE TOTAL DE REGISTRO
    $quantidadeTotal = EntitySchedule::getSchedules($where, null, null, 'COUNT(*) as qtd')->fetchObject()->qtd;

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
    $results = EntitySchedule::getSchedules($where, 'id ASC', $obPagination->getLimit());

    // RENDERIZA O(S) ITEM(S)
    while ($obSchedule = $results->fetchObject(EntitySchedule::class)) {
      $itens[] = self::getScheduleData($obSchedule);
    }

    // RETORNA O(S) ATENDIMETO(S)
    return $itens;
  }

  /**
   * Método responsável por retornar a clausula WHERE do SQL válida
   *
   * @param   array|string  $where
   *
   * @return  string
   */
  private static function getWhere(array|string $where): string {
    // WHERES PERMITDOS COM SUAS ARROW FUNCTIONS
    $wheres = [
      'isopen'         => fn () => '(data_marcada IS NULL OR data_marcada <= 0)',
      'noexpectedtime' => fn () => '(tempo_previsto IS NULL AND cod_motivo IS NULL)',
    ];

    // CLAUSULA A SER RETORNADA
    $return = '';

    // USA RECURSIVIDADE PARA CASOS DE MULTIVALORADOS
    if (gettype($where) == 'array') {
      for ($i = 0; $i < count($where); $i++) {
        $return .= ($i > 0 ? ' AND ' : '') . self::getWhere($where[$i]);
      }
    } else {
      // REPARTE EM CLAUSULA : PARAMETRO
      $where = explode(':', $where, 2);

      // VERIFICA SE O WHERE EXISTE
      if (!array_key_exists($where[0], $wheres))
        throw new \Exception('Clausula Where inválida. Permitidas: [' . implode(', ', array_keys($wheres)) . ']');

      // ADICIONA A CLAUSULA WHERE
      $return = $wheres[$where[0]]($where[1] ?? null);
    }

    // RETORNA O SQL WHERE
    return $return;
  }

  /**
   * Método responsável por retornar os atendimentos cadastrados
   *
   * @param   Request  $request
   *
   * @return  array
   */
  public static function getSchedules(Request $request): array {
    // QUERY PARAMS
    $queryParams = $request->getQueryParams();

    // SQL WHERE
    $where = isset($queryParams['where']) ? self::getWhere($queryParams['where']) : null;

    return [
      'atendimentos' => self::getSchedulesItems($request, $obPagination, $where),
      'paginacao' => parent::getPagination($request, $obPagination)
    ];
  }

  /**
   * Método responsável por retornar um atendimento específico
   *
   * @param   int    $id
   *
   * @return  array
   */
  public static function getSchedule($id): array {
    // VALIDA O ID DO ATENDIMENTO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);

    // BUSCA ATENDIMETO PELO ID
    $obSchedule = EntitySchedule::getScheduleById($id);
    if (!$obSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // RETORNA OS DADOS DO ATENDIMENTO
    return self::getScheduleData($obSchedule);
  }

  /**
   * Método responsável por retorna os atendimentos do usuário logado
   *
   * @param   Request  $request
   *
   * @return  array
   */
  public static function getMySchedules(Request $request): array {
    // USUÁRIO LOGADO
    $userLogged = $request->userLogged;

    // QUERY PARAMS
    $queryParams = $request->getQueryParams();

    // SQL WHERE
    $where = isset($queryParams['where']) ? ' AND ' . self::getWhere($queryParams['where']) : null;

    // O QUE SERÁ RETORNADO
    $return = [];

    // GUARDA OS ATENDIMEOS QUE SE É O ATENDIDO
    $return = $atendido =  [
      'atendimentos' => self::getSchedulesItems($request, $obPagination, "cod_atendido = $userLogged->id" . $where),
      'paginacao' => parent::getPagination($request, $obPagination)
    ];

    // ADICIONA OS ATENDIEMNTOS QUE SE É O ATENDENTE
    if (strtolower($userLogged->tipo) != 'comum') {
      unset($return);

      $return['atendido'] = $atendido;

      $return['atendente'] = [
        'atendimentos' => self::getSchedulesItems($request, $obPagination, "cod_atendente = $userLogged->id" . $where),
        'paginacao' => parent::getPagination($request, $obPagination)
      ];
    }

    // RETORNA
    return $return;
  }

  /**
   * Método responsável por retornar os horários ocupados
   *
   * @param   Request  $request
   *
   * @return  array
   */
  public static function getOccupiedHours(Request $request): array {
    // QUERY PARAMS
    $queryParams = $request->getQueryParams();

    // VALIDA O(S) PARÂMETRO(S) OBRIGÁTORIO(S)
    if (!isset($queryParams['data']))
      throw new \Exception("O parâmetro 'data' é obrigátorio", 400);

    $format = 'Y-m-d';
    if (!Date::isvalid($queryParams['data'], $format))
      throw new \Exception("Data inválida! Experado: '$format'", 400);

    // ARMAZENA O DIA ENVIADO
    $date = $queryParams['data'];

    // PEGA OS ATENDIMENTOS DO DIA
    $next = date($format, strtotime($date . ' + 1 day'));
    $schedulesDay = EntitySchedule::getSchedules("data_marcada >= '$date' AND data_marcada < '$next'", 'data_marcada ASC');

    // HORAS OCUPADAS POR ID
    $occupiedHours = [];

    // RENDERIZA CADA HORÁRIO
    while ($obSchedule = $schedulesDay->fetchObject(EntitySchedule::class)) {
      $format = 'H:i';

      $data_marcada = $obSchedule->data_marcada;
      $tempo_previsto = $obSchedule->tempo_previsto ?? EntityReason::getReasonById($obSchedule->cod_motivo)->tempo_previsto;

      $occupiedHours[] = [
        'inicio' => date($format, strtotime($data_marcada)),
        'termino' => date($format, strtotime($data_marcada . " + $tempo_previsto minutes")),
      ];
    }

    // RETORNA OS HORÁRIOS OCUPADOS
    return $occupiedHours;
  }

  /**
   * Método responsável por validar os campos
   *
   * @param   array  $postVars
   *
   * @return  void
   */
  private static function validateFields(array $postVars): void {
    // VALIDA OS TIPOS DOS CAMPOS
    $props = [
      'cod_motivo'      => 'integer',
      'descricao'       => 'string',
      'tempo_previsto'  => 'integer',
      'data_marcada'    => 'string',
      'data_iniciada'   => 'string',
      'data_finalizada' => 'string',
      'cod_atendente'   => 'integer'
    ];
    foreach ($props as $prop => $type) {
      if (isset($postVars[$prop]) && gettype($postVars[$prop]) != $type)
        throw new \Exception("Valor inválido para o campo '$prop'! Experado: $type", 400);
    }

    // VALIDA O COD_MOTIVO 
    if (isset($postVars['cod_motivo'])) {
      // VALIDA SE EXISTE
      if (!EntityReason::getReasonById($postVars['cod_motivo']) instanceof EntityReason)
        throw new \Exception("O motivo($postVars[cod_motivo]) não foi encontrado", 404);
    }
    // VALIDA O ENVIDO DA DESCRICAO 
    else if (!isset($postVars['descricao']))
      throw new \Exception("É obrigatório envio do campo 'cod_motivo' ou 'descricao'", 400);

    // VALIDA O TEMPO_PREVISTO
    if (isset($postVars['tempo_previsto'])) {
      if ($postVars['tempo_previsto'] <= 0)
        throw new \Exception("Valor inválido para o campo 'tempo_previsto'! Experado: Positivo ", 400);
    }

    // VALIDA AS DATAS
    $dateFields = ['data_marcada', 'data_iniciada', 'data_finalizada'];
    foreach ($dateFields as $datefield) {
      if (isset($postVars[$datefield])) {
        $format = 'Y-m-d H:i';
        if (!Date::isvalid($postVars[$datefield], $format))
          throw new \Exception("Data inválida para o campo '$datefield'! Experado: '$format'", 400);
      }
    }

    // VALIDA O COD_ATENDENTE
    if (isset($postVars['cod_atendente'])) {
      // VALIDA SE EXISTE E É UM ATENDENTE
      $atendente = EntityUser::getUserById($postVars['cod_atendente']);
      if (
        (!$atendente instanceof EntityUser) ||
        strtoupper($atendente->tipo) != strtoupper(EntityUser::$tipos['admin'])
      ) throw new \Exception("O atendente($postVars[cod_atendente]) não foi encontrado", 404);
    }
  }

  /**
   * Método responsável por validar a disponibilidade da Data Maracada
   *
   * @param   string  $date
   * @param   int     $expectedTime
   * @param   int     $scheduleId
   *
   * @return  void
   */
  private static function validateScheduledDate(string $date, int $expectedTime, int $scheduleId = null): void {
    // VALIDA O HORÁRIO DA DATA MARCADA
    if (strtotime($date) <= time()) // TODO: adcionar antecedencia & Tempo comercial
      throw new \Exception('Data marcada inválida!', 400);

    // BUSCA PELA PRIMEIRA DATA ANTERIOR
    $previousDate = EntitySchedule::getSchedules("data_marcada <= '$date'" . ($scheduleId ? ' AND id !=' . $scheduleId : ''), 'data_marcada DESC', '1');
    $previousDate = $previousDate->fetchObject(EntitySchedule::class);

    // VERIFICA SE EXISTE DATA ANTERIOR
    if ($previousDate instanceof EntitySchedule) {
      // SETA O TEMPO PREVISTO DA DATA ANTERIOR
      $previousDate->tempo_previsto = $previousDate->tempo_previsto ?? EntityReason::getReasonById($previousDate->cod_motivo)->tempo_previsto;

      // SOMA O TEMPO PREVISTO E CONVERTE PARA UNIX TIME
      $previousDate = strtotime($previousDate->data_marcada . "+ $previousDate->tempo_previsto minutes");

      // VALIDA CONFLITO DE HORÁRIOS MARCADOS ANTERIORES
      if (strtotime($date) <= $previousDate)
        throw new \Exception('A data marcada está conflitando com uma data anterior', 409);
    }

    // BUSCA PELA PRIMEIRA DATA POSTERIOR
    $nextDate = EntitySchedule::getSchedules("data_marcada >= '$date'" . ($scheduleId ? ' AND id !=' . $scheduleId : ''), 'data_marcada ASC', '1');
    $nextDate = $nextDate->fetchObject(EntitySchedule::class);

    // VERIFICA SE EXISTE DATA POSTERIOR
    if ($nextDate instanceof EntitySchedule) {
      // CONVERTE PARA UNIX TIME
      $nextDate = strtotime($nextDate->data_marcada);

      // SOMA O TEMPO PREVISTO E CONVERTE PARA UNIX TIME
      $date = strtotime($date . "+ $expectedTime minutes");

      // VALIDA CONFLITO DE HORÁRIOS MARCADOS POSTERIORES
      if ($nextDate <= $date)
        throw new \Exception('A data marcada está conflitando com uma data posterior', 409);
    }
  }

  /**
   * Método responsável por cadastrar um novo atendimento
   *
   * @param   Request  $request
   *
   * @return  array
   */
  public static function setNewSchedule(Request $request): array {
    // TODO: Add validação de horarios e finais de semana
    throw new \Exception('Não implementado', 501);
    
    // POST VARS
    $postVars = $request->getPostVars();

    // CAMPOS VÁLIDOS PRA USUÁRIO COMUM
    $validFields = ['cod_motivo', 'descricao', 'data_marcada', 'cod_atendente'];

    // REMOVE QUALQUER CAMPOS NÃO VÁLIDO
    $postVars = array_filter($postVars, function ($field) use ($validFields) {
      return in_array($field, $validFields);
    }, ARRAY_FILTER_USE_KEY);

    // VALIDA O ENVIO DO COD_MOTIVO JUNTO DA DESCRICAO OU DO TEMPO_PREVISTO
    if (isset($postVars['cod_motivo']) && isset($postVars['descricao']))
      throw new \Exception("Se o campo 'cod_motivo' for informado, o campo 'descricao' não deverá ser, e vice versa", 400);

    // VALIDA OS CAMPOS
    self::validateFields($postVars);

    // VERIFICA SE A DATA MARCADA ESTÁ DISPONÍVEL
    if (isset($postVars['data_marcada'])) {
      // VALIDA O MOTIVO
      if (!isset($postVars['cod_motivo']))
        throw new \Exception('Só possível marcar um horário com um motivo', 400);

      // BUSCA PELO TEMPO PREVISTO
      $tempo_previsto = EntityReason::getReasonById($postVars['cod_motivo'])->tempo_previsto;

      self::validateScheduledDate($postVars['data_marcada'], $tempo_previsto);
    }

    // SETA O ATENDIDO COMO O USUÁRIO ATUAL
    $postVars['cod_atendido'] = $request->userLogged->id;

    // CADASTRA O NOVO ATENDIMETO
    $obSchedule = self::setScheduleData($postVars);
    $obSchedule->insert();

    // RETORNA OS DETALHES DO ATENDIMETO
    return self::getScheduleData($obSchedule);
  }

  /**
   * Método responsável por atualizar um atendimento
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  array
   */
  public static function setEditSchedule(Request $request, $id): array {
    // TODO: Add validação de horarios e finais de semana
    throw new \Exception('Não implementado', 501);
    
    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obSchedule = EntitySchedule::getScheduleById($id);
    if (!$obSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // POST VARS
    $postVars = $request->getPostVars();

    // VALIDA OS CAMPOS
    self::validateFields($postVars);

    // VERIFICA SE A DATA MARCADA ESTÁ DISPONÍVEL E SE SERÁ ATUALIZADA
    if (
      isset($postVars['data_marcada']) &&
      $postVars['data_marcada'] != EntitySchedule::getScheduleById($id)->data_marcada
    ) {
      // VALIDA OS DADOS NECESSÁRIOS PARA O TEMPO PREVISTO
      if (!isset($postVars['cod_motivo']) && !isset($postVars['tempo_previsto']))
        throw new \Exception('Só possível marcar um horário com um motivo ou tempo previsto', 400);

      // BUSCA PELO TEMPO PREVISTO
      $tempo_previsto = $postVars['tempo_previsto'] ?? EntityReason::getReasonById($postVars['cod_motivo'])->tempo_previsto;

      self::validateScheduledDate($postVars['data_marcada'], $tempo_previsto, $id);
    }

    // ATUALIZA O ATENDIMETO
    $obSchedule = self::setScheduleData($postVars);
    $obSchedule->id = $id;
    $obSchedule->update();

    // RETORNA OS DETALHES DO ATENDIMETO ATUALIZADO
    return self::getScheduleData($obSchedule);
  }

  /**
   * Método responsável por atualizar um atendimento do usuário logado
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  array
   */
  public static function setEditMySchedule(Request $request, $id): array {
    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    // VALIDA BASEADO NO USUÁRIO LOGADO
    $obCurrentSchedule = EntitySchedule::getSchedules("id = $id AND cod_atendido = {$request->userLogged->id}")->fetchObject(EntitySchedule::class);
    if (!$obCurrentSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // POST VARS
    $postVars = $request->getPostVars();

    // CAMPOS VÁLIDOS PRA USUÁRIO COMUM
    $validFields = ['cod_motivo', 'descricao', 'data_marcada', 'cod_atendente'];

    // REMOVE QUALQUER CAMPOS NÃO VÁLIDO
    $postVars = array_filter($postVars, function ($field) use ($validFields) {
      return in_array($field, $validFields);
    }, ARRAY_FILTER_USE_KEY);

    // MATEM OS DADOS INTEGROS
    if ($obCurrentSchedule->cod_motivo) {
      $postVars['cod_motivo'] = (int) $obCurrentSchedule->cod_motivo;
      $postVars['descricao']  = $obCurrentSchedule->descricao;
    } else if ($obCurrentSchedule->tempo_previsto) {
      $postVars['tempo_previsto'] = (int) $obCurrentSchedule->tempo_previsto;
      $postVars['descricao']      = $obCurrentSchedule->descricao;
    }

    // VALIDA O ENVIO DO COD_MOTIVO JUNTO DA DESCRICAO
    if (!$obCurrentSchedule->cod_motivo && isset($postVars['cod_motivo']) && isset($postVars['descricao']))
      throw new \Exception("Se o campo 'cod_motivo' for informado, o campo 'descricao' não deverá ser, e vice versa", 400);

    // VALIDA OS CAMPOS
    self::validateFields($postVars);

    // VERIFICA SE A DATA MARCADA ESTÁ DISPONÍVEL E SE SERÁ ATUALIZADA
    if (
      isset($postVars['data_marcada']) &&
      $postVars['data_marcada'] != $obCurrentSchedule->data_marcada
    ) {
      // VALIDA OS DADOS NECESSÁRIOS PARA O TEMPO PREVISTO
      if (!isset($postVars['cod_motivo']) && !isset($obCurrentSchedule->tempo_previsto))
        throw new \Exception('Só possível marcar um horário com um motivo', 400);

      // BUSCA PELO TEMPO PREVISTO
      $tempo_previsto = $obCurrentSchedule->tempo_previsto ?? EntityReason::getReasonById($postVars['cod_motivo'])->tempo_previsto;

      self::validateScheduledDate($postVars['data_marcada'], $tempo_previsto, $id);
    }

    // SETA O ATENDIDO COMO O USUÁRIO ATUAL
    $postVars['cod_atendido'] = $request->userLogged->id;

    // ATUALIZA O ATENDIMETO
    $obSchedule = self::setScheduleData($postVars);
    $obSchedule->id = $id;
    $obSchedule->update();

    // RETORNA OS DETALHES DO ATENDIMETO ATUALIZADO
    return self::getScheduleData($obSchedule);
  }

  /**
   * Método responsável por excluir um atendimento
   *
   * @param   int      $id
   *
   * @return  array
   */
  public static function setDeleteSchedule($id): array {
    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obSchedule = EntitySchedule::getScheduleById($id);
    if (!$obSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // EXCLUI O ATENDIMETO
    $obSchedule->delete();

    // RETORNA O SUCESSO DO ATENDIMETO EXLCUIDO
    return ['sucesso' => true];
  }

  /**
   * Método responsável por excluir um atendimento do usuário logado
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  array
   */
  public static function setDeleteMySchedule(Request $request, $id): array {
    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    // VALIDA BASEADO NO USUÁRIO LOGADO
    $obCurrentSchedule = EntitySchedule::getSchedules("id = $id AND cod_atendido = {$request->userLogged->id}")->fetchObject(EntitySchedule::class);
    if (!$obCurrentSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // EXCLUI O ATENDIMETO
    $obCurrentSchedule->delete();

    // RETORNA O SUCESSO DO ATENDIMETO EXLCUIDO
    return ['sucesso' => true];
  }
}
