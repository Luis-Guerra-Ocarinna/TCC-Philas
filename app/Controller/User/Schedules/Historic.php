<?php

namespace App\Controller\User\Schedules;

use App\Controller\User\Page;
use App\Http\Request;
use App\Model\Entity\Schedule as EntitySchedule;
use App\Model\Entity\Reason as EntityReason;
use App\Session\Main as SessionMain;
use App\Utils\Alert;
use App\Utils\Date;
use WilliamCosta\DatabaseManager\Pagination;
use App\Utils\View;

class Historic extends Page {

  /** @var string[] $vars Claususas Where para cada tipo de atendimento */
  private static $WHERES = [
    'history' => '(data_iniciada IS NOT NULL AND data_iniciada > 0)',
    'open'    => '(data_iniciada IS NULL OR data_iniciada <= 0) AND (descricao IS NULL OR descricao = "")',
    'confirm' => '(data_iniciada IS NULL OR data_iniciada <= 0) AND (descricao IS NOT NULL AND descricao != "")',
  ];

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
      case 'deletadoSucesso':
        return Alert::getSuccess('O atendimento foi deletado com sucesso!');
      case 'alteradoSucesso':
        return Alert::getSuccess('O atendimento foi alterado com sucesso!');
      default:
        return '';
    }
  }

  /**
   * Método responsável por obter a renderezação dos itens de motivos para o formulário
   *
   * @param   string  $current
   *
   * @return  string
   */
  public static function getReasonsItems(string $current = ''): string {
    // MOTIVOS
    $itens = '';

    // RESULTADOS DOS MOTIVOS
    $motivos = EntityReason::getReasons();

    //RENDERIZA OS MOTIVOS
    while ($obReason = $motivos->fetchObject(EntityReason::class)) {
      $itens .= View::render('user/schedules/reason/item', [
        'cod_reason'    => $obReason->id,
        'expected_time' => $obReason->tempo_previsto,
        'reason'        => $obReason->descricao /* CONFUSO: . ' - Tempo Previsto: ' . $obReason->tempo_previsto . 'min' */,
        'selected'      => $current == $obReason->id ? 'selected' : ''
      ]);
    }

    return $itens;
  }

  /**
   * Método responsável por obter a renderização dos itens de atendimentos
   *
   * @param   string       $card
   * @param   Request      $request
   * @param   Pagination   $obPagination
   *
   * @return  string|bool
   */
  private static function getItems(string $card, Request $request, ?Pagination &$obPagination): string|bool {
    // ATENDIMENTOS
    $itens = '';

    // ID DO USUÁRIO
    $id = SessionMain::get('user_logged')?->id;

    // CLAUSULA WHERE
    $where = "cod_atendido = $id AND " . self::$WHERES[$card];

    // QUANTIDADE TOTAL DE AGENDAMENTOS
    $quantidadeTotal = EntitySchedule::getSchedules($where, fields: 'COUNT(*) as qtd')->fetchObject()->qtd;

    // QUERY PARAMS
    $queryParams = $request->getQueryParams();

    // PÁGINA ATUAL
    $paginaAtual = $queryParams['page'] ?? 1;

    // LIMITE POR PÁGINA
    $limit = $queryParams['per_page'] ?? 3;
    $limit = is_numeric($limit) ? $limit : 3;

    // VALIDANDO SE DEVE MOSTRAR TODOS
    $limit = $limit > 0 ? $limit : $quantidadeTotal;

    // INSTANCIA DE PAGINAÇÃO
    $obPagination = new Pagination($quantidadeTotal, $paginaAtual, $limit);

    // RESULTADOS DA CONSULTA
    $results = EntitySchedule::getSchedules($where, 'id DESC', $obPagination->getLimit());
    if (!(EntitySchedule::getSchedules($where, 'id DESC', $obPagination->getLimit())->fetchAll()))
      return false;

    // RENDERIZA OS ITENS
    while ($obSchedule = $results->fetchObject(EntitySchedule::class)) {
      if (isset($obSchedule->cod_motivo))
        $motivo = EntityReason::getReasonById($obSchedule->cod_motivo)->descricao;
      else
        $motivo = "<i>Descrito</i>: $obSchedule->descricao";

      // RENDERIZA O ITEM PARA CADA CARD
      $itens .= match ($card) {
        'history' => View::render('user/schedules/historic/item', [
          'motivo' => $motivo,
          'id'     => $obSchedule->id,
          'data'   => date('d/m/Y H:i', strtotime($obSchedule->data_finalizada))
        ]),
        'open'    => View::render('user/schedules/open/item', [
          'motivo' => $motivo,
          'id'     => $obSchedule->id,
          'data'   => $obSchedule->data_marcada ? date('d/m/Y H:i', strtotime($obSchedule->data_marcada)) : '<i>sem data marcada</i>'
        ]),
        'confirm' => View::render('user/schedules/confirm/item', [
          'motivo'       => $motivo,
          'id'           => $obSchedule->id,
          'data'         => $obSchedule->data_marcada ? date('d/m/Y H:i', strtotime($obSchedule->data_marcada)) : '<i>sem data marcada</i>',
          'status_color' => isset($obSchedule->tempo_previsto) || isset($obSchedule->cod_motivo) ? 'success' : 'secondary',
          'status'       => isset($obSchedule->tempo_previsto) || isset($obSchedule->cod_motivo) ? 'Validado' : 'Pendente'
        ]),
      };
    }

    // RETORNA OS ITENS
    return $itens;
  }

  /**
   * Método responsável por retornar a view do histórico
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getHistoric(Request $request): string {
    // CARDS
    $history = self::getItems('history', $request, $obPagination);
    $history = $history ?
      View::render('user/schedules/historic/box', ['item' => $history]) :
      '<div class="text-center mt-5">Não há horários</div>';

    // VIEW DO HISTÓRICO
    $content = View::render('user/schedules/historic', [
      'historic'   => $history,
      'pagination' => parent::getPagination($request, $obPagination)
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Histórico', $content);
  }

  /**
   * Método responsável por retornar a view dos antendimentos abertos
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getOpenSchedules(Request $request): string {
    // CARDS
    $open = self::getItems('open', $request, $obPagination);
    $open = $open ?
      View::render('user/schedules/open/box', ['item' => $open]) :
      '<div class="text-center mt-5">Não há horários</div>';

    // VIEW DO HISTÓRICO
    $content = View::render('user/schedules/open', [
      'open'       => $open,
      'pagination' => parent::getPagination($request, $obPagination),
      'status'     => self::getStatus($request)
    ]);

    // RETORNA A VIEW DA PÁGINA
    return parent::getPage('Abertos', $content);
  }

  /**
   * Método responsável por retornar a view de editar atendimentos abertos
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getEditOpenSchedules(Request $request, $id): string {
    // USUÁRIO LOGADO
    $obUser = SessionMain::get('user_logged');

    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obSchedule = EntitySchedule::getScheduleById($id, "cod_atendido = $obUser->id AND " . self::$WHERES['open']);
    if (!$obSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // SELECT DE MOTIVOS
    $cod_motivo = $obSchedule->cod_motivo ?? '';
    $boxReason = View::render('user/schedules/reason/box', [
      'readonly'      => '',
      'selected'      => '',
      'item'          => self::getReasonsItems($cod_motivo),
      'selectedOther' => $cod_motivo ? '' : 'selected'
    ]);

    // VIEW DO HISTÓRICO
    $content = View::render('user/schedules/openEdit', [
      'status'        => self::getStatus($request),
      'name'          => $obUser->nome,
      'email'         => $obUser->email,
      'select_reason' => $boxReason,
      'data_marcada'  => $obSchedule->data_marcada ?
        Date::format('Y-m-d H:i:s', $obSchedule->data_marcada, 'd/m/Y H:i') : '',
      'descricao'     => $obSchedule->descricao ?? ''
    ]);

    // RETORNA A VIEW DA PÁGINA
    return parent::getPage(
      'Abertos',
      $content,
      styles: [['jquery.datetimepicker.min', 'Date Picker']],
      scripts: [
        ['https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js', 'Popper Bootstrap', true],
        ['https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment-with-locales.min.js', 'Moment', true],
        ['https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js', 'Date Picker', true],
        ['https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js', 'Cookie', true],
        ['datapickerscript', 'Data Picker Script'],
        ['schedule', 'Atendimento Script']
      ]
    );
  }

  /**
   * Método responsável por validar a disponibilidade da Data Marcada
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
      if ($nextDate <= $date) // TODO: retornar alerta
        throw new \Exception('A data marcada está conflitando com uma data posterior', 409);
    }
  }

  /**
   * Método responsável por editar atendimentos abertos
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  never
   */
  public static function setEditOpenSchedules(Request $request, $id): void {
    // USUÁRIO LOGADO
    $obUser = SessionMain::get('user_logged');

    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obSchedule = EntitySchedule::getScheduleById($id, "cod_atendido = $obUser->id AND " . self::$WHERES['open']);
    if (!$obSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // POST VARS
    $postVars     = $request->getPostVars();
    $motivo       = $postVars['motivo'] ?: null;
    $data_marcada = $postVars['data_marcada'] ?: null;
    $descricao    = $postVars['descricao'] ?: null;

    // VALIDA A DATA
    if (!$descricao) {
      // BUSCA PELO TEMPO PREVISTO
      $tempo_previsto = EntityReason::getReasonById($motivo)->tempo_previsto;

      // DATA A MARCADA
      $data_marcada = Date::format('d/m/Y H:i', $postVars['data_marcada'], 'Y-m-d H:i');

      self::validateScheduledDate($data_marcada, $tempo_previsto, $id);
    } else {
      $data_marcada = null;
    }

    $obSchedule->cod_motivo   = $motivo;
    $obSchedule->descricao    = $descricao;
    $obSchedule->cod_atendido = $obUser->id;
    $obSchedule->data_marcada = $data_marcada;
    $obSchedule->update();

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/usuario/atendimento/' . (isset($motivo) ? 'abertos' : 'confirmar') . '?status=alteradoSucesso');
  }

  /**
   * Método responsável por retornar a view de delete dos agendamentos abertos
   *
   * @param   int     $id
   *
   * @return  string
   */
  public static function getDeleteOpenSchedules($id): string {
    // USUÁRIO LOGADO
    $obUser = SessionMain::get('user_logged');

    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obSchedule = EntitySchedule::getScheduleById($id, "cod_atendido = $obUser->id AND " . self::$WHERES['open']);
    if (!$obSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // VIEW DO HISTÓRICO
    $content = View::render('user/schedules/formDelete', [
      'title_form' => 'Atendimentos Abertos',
      'content'    => 'Exclusão do atendimento selecionado.',
      'id'         => $id
    ]);

    // RETORNA A VIEW DA PÁGINA
    return parent::getPage('Histórico', $content);
  }

  /**
   * Método responsável por deletar agendamentos abertos
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  never
   */
  public static function setDeleteOpenSchedules(Request $request, $id): void {
    // USUÁRIO LOGADO
    $obUser = SessionMain::get('user_logged');

    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obSchedule = EntitySchedule::getScheduleById($id, "cod_atendido = $obUser->id AND " . self::$WHERES['open']);
    if (!$obSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // DELETA O ATENDIMENTO SELECIONADO
    $obSchedule->delete();

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/usuario/atendimento/abertos?status=deletadoSucesso');
  }

  /**
   * Método responsável por retornar a view dos antendimentos a serem confirmados
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getConfirmSchedules(Request $request): string {
    // CARDS
    $confirm = self::getItems('confirm', $request, $obPagination);
    $confirm = $confirm ?
      View::render('user/schedules/confirm/box', ['item' => $confirm]) :
      '<div class="text-center mt-5">Não há horários</div>';

    // VIEW DO HISTÓRICO
    $content = View::render('user/schedules/confirm', [
      'confirm'    => $confirm,
      'pagination' => parent::getPagination($request, $obPagination),
      'status'     => self::getStatus($request)
    ]);

    // RETORNA A VIEW DA PÁGINA
    return parent::getPage('Confirmar', $content);
  }

  /**
   * Método responsável por retornar a view de editar os agendamentos a serem confirmados
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  string
   */
  public static function getEditConfirmSchedules(Request $request, $id): string {
    // USUÁRIO LOGADO
    $obUser = SessionMain::get('user_logged');

    // VALIDA O ID DO ATENDIMENTO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obS = EntitySchedule::getScheduleById($id, "cod_atendido = $obUser->id AND " . self::$WHERES['confirm']);
    if (!$obS instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // 
    $cod_motivo = $obS->cod_motivo ?? '';
    $tempo_previsto = $cod_motivo ? EntityReason::getReasonById($cod_motivo)->tempo_previsto : ($obS->tempo_previsto ?? '');

    // SELECT DE MOTIVOS
    $boxReason = View::render('user/schedules/reason/box', [
      'selected'      => '',
      'item'          => self::getReasonsItems($cod_motivo),
      'selectedOther' => $cod_motivo ? '' : 'selected'
    ]);

    // VIEW DO HISTÓRICO
    $content = View::render('user/schedules/confirmEdit', [
      'status'         => self::getStatus($request),
      'name'           => $obUser->nome,
      'email'          => $obUser->email,
      'select_reason'  => $boxReason,
      'data_marcada'   => $obS->data_marcada ? date('d/m/Y H:i', strtotime($obS->data_marcada)) : '',
      'descricao'      => $obS->descricao ?? '',
      'tempo_previsto' => $tempo_previsto ?? '',
      'readonly'       => $tempo_previsto ? 'readonly' : '',
    ]);

    // RETORNA A VIEW DA PÁGINA
    return parent::getPage(
      'Confirmar',
      $content,
      styles: [['jquery.datetimepicker.min', 'Date Picker']],
      scripts: [
        ['https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js', 'Popper Bootstrap', true],
        ['https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment-with-locales.min.js', 'Moment', true],
        ['https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js', 'Date Picker', true],
        ['https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js', 'Cookie', true],
        ['datapickerscript', 'Data Picker Script'],
        ['scheduleConfirm', 'Atendimento Script'],
        ['selectReadonly', 'Select Readonly']
      ]
    );
  }

  /**
   * Método responsável por editar os atendiementos a serem confirmados
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  never
   */
  public static function setEditConfirmSchedules(Request $request, $id): void {
    // USUÁRIO LOGADO
    $obUser = SessionMain::get('user_logged');

    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obSchedule = EntitySchedule::getScheduleById($id, "cod_atendido = $obUser->id AND " . self::$WHERES['confirm']);
    if (!$obSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // POST VARS
    $postVars = $request->getPostVars();

    // DADOS DO FORMULÁRIO
    $cod_motivo   = $postVars['motivo'] ?: null;
    $descricao    = $postVars['descricao'] ?: null;
    $data_marcada = $postVars['data_marcada'] ?: null;

    // ADMIN JÁ CONFIRMOU O ATENDIMENTO
    $adminConfirm = isset($obSchedule->tempo_previsto) || isset($obSchedule->cod_motivo);

    // VALIDAÇÕES
    if ($cod_motivo || $adminConfirm) {

      // MANTÉM A CONFIRMAÇÃO DO ADMIN
      if ($adminConfirm) {
        $cod_motivo = $obSchedule->cod_motivo;
        $descricao  = $obSchedule->descricao;
      } else {
        $descricao = null;
      }

      // VALIDA A DATA MARCADA
      if ($data_marcada) {
        // BUSCA PELO TEMPO PREVISTO
        $tempo_previsto = $obSchedule->tempo_previsto ?? EntityReason::getReasonById($cod_motivo)->tempo_previsto;

        // DATA MARCADA
        $data_marcada = Date::format('d/m/Y H:i', $data_marcada, 'Y-m-d H:i');

        self::validateScheduledDate($data_marcada, $tempo_previsto, $id);
      }
    } else {
      $cod_motivo   = null;
      $data_marcada = null;
    }

    // ATUALIZA O ATENDIMENTO
    $obSchedule->cod_motivo   = $cod_motivo;
    $obSchedule->descricao    = $descricao;
    $obSchedule->data_marcada = $data_marcada;
    $obSchedule->update();

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/usuario/atendimento/' .  ($adminConfirm || $descricao ? 'confirmar' : 'abertos') . '?status=alteradoSucesso');
  }

  /**
   * Método responsável por retornar a view de deletar dos antendimentos a serem confirmados 
   *
   * @param   int      $id
   *
   * @return  string
   */
  public static function getDeleteConfirmSchedules($id): string {
    // USUÁRIO LOGADO
    $obUser = SessionMain::get('user_logged');

    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obSchedule = EntitySchedule::getScheduleById($id, "cod_atendido = $obUser->id AND " . self::$WHERES['confirm']);
    if (!$obSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // VIEW DO HISTÓRICO
    $content = View::render('user/schedules/formDelete', [
      'title_form' => 'Atendimentos a Serem Confirmados',
      'content'    => 'Exclusão do atendimento selecionado.',
      'id'         => $id

    ]);

    // RETORNA A VIEW DA PÁGINA
    return parent::getPage('Histórico', $content);
  }

  /**
   * Método responsável por deletar os antendimentos a serem confirmados 
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  never
   */
  public static function setDeleteConfirmSchedules(Request $request, $id): void {
    // USUÁRIO LOGADO
    $obUser = SessionMain::get('user_logged');

    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obSchedule = EntitySchedule::getScheduleById($id, "cod_atendido = $obUser->id AND " . self::$WHERES['confirm']);
    if (!$obSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // DELETA O ATENDIMENTO SELECIONADO
    $obSchedule->delete();

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/usuario/atendimento/confirmar?status=deletadoSucesso');
  }
}
