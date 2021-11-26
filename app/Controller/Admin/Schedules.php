<?php

namespace App\Controller\Admin;

use App\Http\Request;
use App\Model\Entity\Schedule as EntitySchedule;
use App\Model\Entity\Reason as EntityReason;
use App\Model\Entity\User as EntityUser;
use App\Session\Main as SessionMain;
use App\Utils\Alert;
use App\Utils\Date;
use WilliamCosta\DatabaseManager\Pagination;
use App\Utils\View;

class Schedules extends Page {

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
      case 'atendimentoAtualizado':
        return Alert::getSuccess('Atendimento atualizado com sucesso!');
      case 'atendimentoDeletado':
        return Alert::getSuccess('Atendimento deletado com sucesso!');
      case 'atendimentoFinalizado':
        return Alert::getSuccess('Atendimento finalizado com sucesso!');
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
        'reason'        => $obReason->descricao . ' - Tempo Previsto: ' . $obReason->tempo_previsto . 'min',
        'selected'      => $current == $obReason->id ? 'selected' : ''
      ]);
    }

    return $itens;
  }

  /**
   * Método responsável por obter a renderização dos itens de atendimentos
   *
   * @param   Request     $request
   * @param   Pagination  $obPagination
   * @param   string      $where
   *
   * @return  string|bool
   */
  private static function getItems(Request $request, ?Pagination &$obPagination, string $where, string $card = 'normal'): string|bool {
    // ATENDIMENTOS
    $itens = '';

    // QUANTIDADE TOTAL DE ATENDIMENTOS
    $quantidadeTotal = EntitySchedule::getSchedules($where, fields: 'COUNT(*) as qtd')->fetchObject()->qtd;

    // QUERY PARAMS
    $queryParams = $request->getQueryParams();

    // PÁGINA ATUAL
    $paginaAtual = $queryParams['page'] ?? 1;

    // LIMITE POR PÁGINA
    $limit = $queryParams['per_page'] ?? 9;
    $limit = is_numeric($limit) ? $limit : 9;

    // VALIDANDO SE DEVE MOSTRAR TODOS
    $limit = $limit > 0 ? $limit : $quantidadeTotal;

    // INSTANCIA DE PAGINAÇÃO
    $obPagination = new Pagination($quantidadeTotal, $paginaAtual, $limit);

    // RESULTADOS DA HISTÓRICO
    $results = EntitySchedule::getSchedules($where, 'data_marcada ASC', $obPagination->getLimit());
    if (!(EntitySchedule::getSchedules($where, 'data_marcada ASC', $obPagination->getLimit())->fetchAll()))
      return false;

    // RENDERIZA O ITEM
    while ($obSchedule = $results->fetchObject(EntitySchedule::class)) {
      if (isset($obSchedule->cod_motivo))
        $motivo = EntityReason::getReasonById($obSchedule->cod_motivo)->descricao;
      else
        $motivo = '<i>Descrito</i>: ' . $obSchedule->descricao;

      // RENDERIZA O ITEM PARA CADA CARD
      $itens .= match ($card) {
        'today' => View::render('admin/actions/schedules/cards/todayItem', [
          'id'       => $obSchedule->id,
          'motivo'   => $motivo,
          'data'     => date('H:i', strtotime($obSchedule->data_marcada)),
          'atendido' => EntityUser::getUserById($obSchedule->cod_atendido)->nome,
        ]),
        'history' => self::renderHistoryItems($obSchedule),
        'normal' => View::render('admin/actions/schedules/cards/item', [
          'id'     => $obSchedule->id,
          'motivo' => $motivo,
          'data'   => $obSchedule->data_marcada ?
            date('d/m/Y H:i', strtotime($obSchedule->data_marcada)) : '<i>sem data marcada</i>'
        ])
      };
    }

    // RETORNA OS ATENDIMENTOS
    return $itens;
  }

  /**
   * Método responsável por renderizar os itens de histórico
   *
   * @param   EntitySchedule  $obSchedule
   *
   * @return  string
   */
  private static function renderHistoryItems(EntitySchedule $obSchedule): string {
    // COD MOTIVO
    $cod_motivo = $obSchedule->cod_motivo ?
      View::render('admin/actions/schedules/history/coluns/cod_motivo', [
        'id' => $obSchedule->cod_motivo,
        'descricao' => EntityReason::getReasonById($obSchedule->cod_motivo)->descricao,
      ]) : '<div class="fst-italic text-center text-muted">null</div>';

    // COD ATENDIDO
    $cod_atendido = $obSchedule->cod_atendido ?
      View::render('admin/actions/schedules/history/coluns/cod_user', [
        'id' => $obSchedule->cod_atendido,
        'nome' => EntityUser::getUserById($obSchedule->cod_atendido)->nome,
      ]) : '<div class="fst-italic text-center text-muted">null</div>';

    // COD ATENDENTE
    $cod_atendente = $obSchedule->cod_atendente ?
      View::render('admin/actions/schedules/history/coluns/cod_user', [
        'id' => $obSchedule->cod_atendente,
        'nome' => EntityUser::getUserById($obSchedule->cod_atendente)->nome,
      ]) : '<div class="fst-italic text-center text-muted">null</div>';

    // DESCRIÇÃO
    $descricao = $obSchedule->descricao ?
      View::render('admin/actions/schedules/history/coluns/descricao', [
        'descricao' => $obSchedule->descricao,
      ]) : '<div class="fst-italic text-center text-muted">null</div>';

    // TEMPO PREVISTO
    $tempo_previsto = $obSchedule->tempo_previsto ?: EntityReason::getReasonById($obSchedule->cod_motivo)->tempo_previsto;

    return View::render('admin/actions/schedules/history/item', [
      'id'              => $obSchedule->id,
      'cod_motivo'      => $cod_motivo,
      'descricao'       => $descricao,
      'tempo_previsto'  => $tempo_previsto,
      'data_marcada'    => date('d/m/Y H:i', strtotime($obSchedule->data_marcada)),
      'data_iniciada'   => date('d/m/Y H:i', strtotime($obSchedule->data_iniciada)),
      'data_finalizada' => date('d/m/Y H:i', strtotime($obSchedule->data_finalizada)),
      'cod_atendido'    => $cod_atendido,
      'cod_atendente'   => $cod_atendente
    ]);
  }

  /**
   * Método responsável por renderizar a view de atendimentos do dia
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getSchedules(Request $request): string {
    // VIEW DO CONTÉUDO
    $content = View::render('admin/actions/schedules/home', [
      'title'         => 'Atendimentos',
      'title_content' => 'Listagem de agendamentos do dia atual.',
      'status'        => self::getStatus($request),
      'content'       => self::getTodaySchedules($request)
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Atendimentos', $content);
  }

  /**
   * Método responsável por renderizar os atendimentos de hoje
   *
   * @param   Request  $request
   *
   * @return  string
   */
  private static function getTodaySchedules(Request $request): string {
    // ADMIN LOGADO
    $obAdmin = SessionMain::get('user_logged');

    // CLAUSULA WHERE
    $today    = date('Y-m-d', time());
    $tomorrow = date('Y-m-d', strtotime($today . ' + 1 day'));
    $where    = 'TRUE ' . "AND data_marcada >= '$today' AND data_marcada < '$tomorrow' AND data_finalizada IS NULL AND (cod_atendente IS NULL OR cod_atendente = $obAdmin->id)";

    // ATENDIMENTOS DE HOJE
    $todayItens = self::getItems($request, $obPagination, $where, 'today');
    $todayCards =  $todayItens ?
      View::render('admin/actions/schedules/cards/box', [
        'item'       => $todayItens,
        'pagination' => parent::getPagination($request, $obPagination)
      ]) :
      '<div class="text-center mt-5">Não há horários</div>';

    return $todayCards;
  }

  /**
   * Método responsável por renderizar a view de historico de atendimentos
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getHistoricSchedules(Request $request): string {
    // CLAUSULA WHERE
    $where = "data_finalizada IS NOT NULL";

    // ATENDIMENTOS JÁ FINALIZADOS
    $historyItens = self::getItems($request, $obPagination, $where, 'history');
    $historyBox   = $historyItens ?
      View::render('admin/actions/schedules/history/box', [
        'item'        => $historyItens,
        'pagination'  => parent::getPagination($request, $obPagination)
      ]) :
      '<div class="text-center mt-5">Não há histórico</div>';

    // VIEW DO CONTÉUDO
    $content = View::render('admin/actions/schedules/pages', [
      'title'         => 'Histórico de atendimentos',
      'title_content' => 'Listagem de todos atendimentos já concluidos.',
      'status'        => '',
      'schedule'      => $historyBox
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Atendimentos', $content);
  }

  /**
   * Método responsável por renderizar a view de atendimentos abertos
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getOpenSchedules(Request $request): string {
    // CLAUSULA WHERE
    $where = "data_finalizada IS NULL";

    // ATENDIMENTOS NÃO FINALIZADOS
    $openCards = self::getItems($request, $obPagination, $where);
    $openCards = $openCards ?
      View::render('admin/actions/schedules/cards/box', [
        'item'       => $openCards,
        'pagination' => parent::getPagination($request, $obPagination)
      ]) :
      '<div class="text-center mt-5">Não há horários</div>';

    // VIEW DO CONTÉUDO
    $content = View::render('admin/actions/schedules/pages', [
      'title'         => 'Atendimentos abertos',
      'title_content' => 'Listagem de todos atendimentos que não foram finalizados.',
      'status'        => '',
      'schedule'      => $openCards
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Atendimentos', $content);
  }

  /**
   * Método responsável por renderizar a view de atendimentos esperando por confirmação
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getConfirmSchedules(Request $request): string {
    // CLAUASULA WHERE
    $where = 'tempo_previsto IS NULL AND cod_motivo IS NULL AND descricao IS NOT NULL';

    // ATENDIMENTOS ESPERANDO POR CONFIRMAÇÃO
    $confirmCards = self::getItems($request, $obPagination, $where);
    $confirmCards = $confirmCards ?
      View::render('admin/actions/schedules/cards/box', [
        'item'       => $confirmCards,
        'pagination' => parent::getPagination($request, $obPagination)
      ]) :
      '<div class="text-center mt-5">Não há horários</div>';

    // VIEW DO CONTÉUDO
    $content =  View::render('admin/actions/schedules/pages', [
      'title'         => 'Atendimentos esperando por confirmação',
      'title_content' => 'Listagem de todos atendimentos que ainda não foram confirmados.',
      'status'        => '',
      'schedule'      => $confirmCards
    ]);


    // RETORNA A VIEW DA PÁGINA
    return parent::getPage(
      'Atendimentos',
      $content
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
   * Método responsável por renderizar o formulario de cadastro de agendamento
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getNewSchedules(Request $request): string {
    // TODO: Add Cadastro de Agendamento pelo Admin
    throw new \Exception('Não implementado', 501);

    // SELECT DE MOTIVOS
    $boxReason = View::render('user/schedules/reason/box', [
      'readonly'      => '',
      'selected'      => 'selected',
      'item'          => self::getReasonsItems(),
      'selectedOther' => ''
    ]);

    // VIEW DO CONTÉUDO
    $content = View::render('admin/actions/schedules/form', [
      'title'         => 'Cadastrar atendimento',
      'title_content' => 'Preencha com os dados do atendimento',
      'status'        => self::getStatus($request),
      'name'          => '',
      'email'         => '',
      'select_reason' => $boxReason,
      'data_marcada'  => '',
      'descricao'     => '',
      'action'        => 'Agendar'
    ]);

    return parent::getPage(
      'Atendimentos',
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
   * Método responsável por cadastrar um novo atendimento
   *
   * @param   Request  $request
   *
   * @return  never
   */
  public static function setNewSchedules(Request $request): void {
    throw new \Exception('Não implementado', 501);
  }

  /**
   * Método responsável por renderizar a view de sobre atendimento
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  string
   */
  public static function getEditSchedule(Request $request, $id): string {
    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obS = EntitySchedule::getScheduleById($id);
    if (!$obS instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);


    // 
    $cod_motivo = $obS->cod_motivo ?? '';
    $tempo_previsto = $cod_motivo ? EntityReason::getReasonById($cod_motivo)->tempo_previsto : ($obS->tempo_previsto ?: '');


    // SELECT DE MOTIVOS
    $boxReason = View::render('user/schedules/reason/box', [
      'readonly'      => '',
      'selected'      => '',
      'item'          => self::getReasonsItems($cod_motivo),
      'selectedOther' => $cod_motivo ? '' : 'selected',
    ]);

    // ENTIDADE DO ATENDIDO
    $obClient = EntityUser::getUserById($obS->cod_atendido);

    // VIEW DO CONTÉUDO
    $content = View::render('admin/actions/schedules/form', [
      'title'          => 'Atendimentos',
      'title_content'  => 'Dados do atendimento selecionado',
      'status'         => self::getStatus($request),
      'name'           => $obClient->nome,
      'email'          => $obClient->email,
      'select_reason'  => $boxReason,
      'data_marcada'   => $obS->data_marcada ?
        Date::format('Y-m-d H:i:s', $obS->data_marcada, 'd/m/Y H:i') : '',
      'descricao'      => $obS->descricao,
      'tempo_previsto' => $tempo_previsto,
      'action'         => 'Alterar'
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage(
      'Atendimentos',
      $content,
      styles: [['jquery.datetimepicker.min', 'Date Picker']],
      scripts: [
        ['https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js', 'Popper Bootstrap', true],
        ['https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment-with-locales.min.js', 'Moment', true],
        ['https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js', 'Date Picker', true],
        ['https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js', 'Cookie', true],
        ['datapickerscript', 'Data Picker Script'],
        ['scheduleAdmin', 'Atendimento Script']
      ]
    );
  }

  /**
   * Método responsável por alterar um atendimento
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  never
   */
  public static function setEditSchedule(Request $request, $id): void {
    // ADMIN LOGADO
    $obAdmin = SessionMain::get('user_logged');

    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obS = EntitySchedule::getScheduleById($id);
    if (!$obS instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // POST VARS
    $postVars = $request->getPostVars();

    // DADOS DO FORMULÁRIO
    $tempo_previsto = $postVars['tempo_previsto'] ?: null;
    $data_marcada   = $postVars['data_marcada'] ?: null;
    $descricao      = $postVars['descricao'] ?: null;
    $motivo         = $postVars['motivo'] ?: null;

    // VALIDA A DATA MARCADA
    if ($data_marcada) {
      // DATA MARCADA
      $data_marcada = Date::format('d/m/Y H:i', $data_marcada, 'Y-m-d H:i');

      self::validateScheduledDate($data_marcada, $tempo_previsto, $id);
    }

    // ATUALIZA O ATENDIMENTO
    $obS->tempo_previsto = $tempo_previsto;
    $obS->data_marcada   = $data_marcada;
    $obS->descricao      = $descricao;
    $obS->cod_motivo     = $motivo;
    $obS->cod_atendente  = $obAdmin->id;
    $obS->update();

    // REDIRECIONA O ADMIN
    $request->getRouter()->redirect('/admin/atendimentos?status=atendimentoAtualizado');
  }

  /**
   * Método responsável por renderizar a view de deletar atendimento
   *
   * @param   integer  $id
   *
   * @return  string
   */
  public static function getDeleteSchedule($id): string {
    // VIEW DO CONTÉUDO
    $content = View::render('admin/actions/schedules/formDelete', [
      'title_form' => 'Atendimentos',
      'content'    => 'Exclusão do atendimento selecionado',
      'id'         => $id
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage(
      'Atendimentos',
      $content
    );
  }

  /**
   * Método responsável por deletar atendimento selecionado
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  never
   */
  public static function setDeleteSchedule(Request $request, $id): void {
    // OBTEM O ATENDIMENTO DO BANCO DE DADOS
    $obScheduleId = EntitySchedule::getScheduleById($id);

    // DELETA O ATENDIMENTO SELECIONADO
    $obScheduleId->delete();

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/admin/atendimentos?status=atendimentoDeletado');
  }

  /**
   * Método responsável por renderizar a view de iniciar atendimento selecionado do dia
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  string
   */
  public static function getStartSchedule(Request $request, $id): string {
    // ADMIN LOGADO
    $obAdmin = SessionMain::get('user_logged');

    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obS = EntitySchedule::getScheduleById($id);
    if (!$obS instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // INICIADO POR OUTRO
    if (isset($obS->cod_atendente) && $obS->cod_atendente != $obAdmin->id)
      throw new \Exception("O atendimento($id) já foi iniciado por outro funcionário!", 400);

    // SALVA DATA INICIADA
    if (!isset($obS->data_iniciada)) {
      $obS->data_iniciada = date('Y-m-d H:i');
      $obS->cod_atendente = $obAdmin->id;
      $obS->update();
    }

    // TEMPO PREVISTO
    $expected_time = $obS->tempo_previsto ?: EntityReason::getReasonById($obS->cod_motivo)->tempo_previsto;

    // ENTIDADE DO ATENDIDO
    $obClient = EntityUser::getUserById($obS->cod_atendido);

    // VIEW DO CONTÉUDO
    $content = View::render('admin/actions/schedules/formStart', [
      'title'          => 'Atendimentos',
      'title_content'  => 'Iniciado atendimento selecionado',
      'data_iniciada'  => date('d/m/Y H:i', strtotime($obS->data_iniciada)),
      'expected_final' => date('d/m/Y H:i', strtotime($obS->data_iniciada . ' + ' . $expected_time . ' min')),
      'status'         => self::getStatus($request),
      'name'           => $obClient->nome ?? '',
      'email'          => $obClient->email ?? '',
      'data_marcada'   => date('d/m/Y H:i', strtotime($obS->data_marcada)),
      'tempo_previsto' => $expected_time,
      'motivo'         => $obS->cod_motivo ? EntityReason::getReasonById($obS->cod_motivo)->descricao : $obS->descricao,
      'action'         => 'Finalizar'
    ]);

    // RETORNA A VIEW DA PÁGINA 
    return parent::getPage('Atendimentos', $content, scripts: [['selectReadonly', 'Readonly']]);
  }

  /**
   * Método responsável por finalizar atendimento selecionado do dia
   *
   * @param   Request  $request
   * @param   int      $id
   *
   * @return  string
   */
  public static function setEndSchedule(Request $request, $id) {
    // VALIDA O ID DO ATENDIMETO
    if (!(is_numeric($id) ? intval($id) == $id : false))
      throw new \Exception("O id '$id' não é válido", 400);
    $obSchedule = EntitySchedule::getScheduleById($id);
    if (!$obSchedule instanceof EntitySchedule)
      throw new \Exception("O atendimento($id) não foi encontrado", 404);

    // SALVA DATA INICIADA
    $obSchedule->data_finalizada = date('Y-m-d H:i');
    $obSchedule->update();

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/admin/atendimentos?status=atendimentoFinalizado');
  }
}
