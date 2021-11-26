<?php


namespace App\Controller\User\Schedules;

use App\Controller\User\Page;
use App\Http\Request;
use App\Model\Entity\Reason as EntityReason;
use App\Model\Entity\Schedule as EntitySchedule;
use App\Session\Main as SessionMain;
use App\Utils\Alert;
use App\Utils\Date;
use App\Utils\View;

class Schedule extends Page {

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
      case 'atendimentoSucesso':
        return Alert::getSuccess('Atendimento criado com sucesso!');
      default:
        return '';
    }
  }

  /**
   * Método responsável por obter a renderezação dos itens de motivos para o formulário
   *
   * @return  string  
   */
  public static function getReasonsItems(): string {
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
      ]);
    }

    return $itens;
  }

  /**
   * Método responsável por renderizar o formulário
   *
   * @param   Request  $request
   *
   * @return  string
   */
  public static function getNewSchedules(Request $request): string {
    // USUÁRIO LOGADO
    $obUser = SessionMain::get('user_logged');

    // SELECT DE MOTIVOS
    $boxReason = View::render('user/schedules/reason/box', [
      'readonly'      => '',
      'selected'      => 'selected',
      'item'          => self::getReasonsItems(),
      'selectedOther' => ''
    ]);

    // VIEW DA AGENDAMENTO
    $content =  View::render('user/schedules/formNew', [
      'name'          => $obUser->nome,
      'email'         => $obUser->email,
      'select_reason' => $boxReason,
      'status'        => self::getStatus($request),
      'action'        => 'Confirmar'
    ]);

    return parent::getPage(
      'Agendar',
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
   * Método responsável por cadastrar um antendimento
   *
   * @param   Request  $request
   *
   * @return  never
   */
  // TODO: Validações no back-end
  public static function setNewSchedules(Request $request): void {
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

      self::validateScheduledDate($data_marcada, $tempo_previsto);
    } else {
      $data_marcada = null;
    }

    // NOVA INSTÂNCIA DA ENTIDADE ATENDIMENTO
    $obSchedule = new EntitySchedule;

    $obSchedule->cod_motivo   = $motivo;
    $obSchedule->descricao    = $descricao;
    $obSchedule->cod_atendido = SessionMain::get('user_logged')->id;
    $obSchedule->data_marcada = $data_marcada;
    $obSchedule->insert();

    // REDIRECIONA O USUÁRIO
    $request->getRouter()->redirect('/usuario?status=atendimentoSucesso' . ($descricao ? 'Confirmar' : ''));
  }
}
