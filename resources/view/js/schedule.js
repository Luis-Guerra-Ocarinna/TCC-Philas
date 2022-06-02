/// <reference path="../../typings/globals/jquery/index.d.ts" />

// ALLOWTIMES TODO: Otimizar && Torna Dinamico os horários permitidos
// var allowTimes = ['08:00', '08:05', '08:10', '08:15', '08:20', '08:25', '08:30', '08:35', '08:40', '08:45', '08:50', '08:55', '09:00', '09:05', '09:10', '09:15', '09:20', '09:25', '09:30', '09:35', '09:40', '09:45', '09:50', '09:55', '10:00', '10:05', '10:10', '10:15', '10:20', '10:25', '10:30', '10:35', '10:40', '10:45', '10:50', '10:55', '11:00', '14:00', '14:05', '14:10', '14:15', '14:20', '14:25', '14:30', '14:35', '14:40', '14:45', '14:50', '14:55', '15:00', '15:05', '15:10', '15:15', '15:20', '15:25', '15:30', '15:35', '15:40', '15:45', '15:50', '15:55', '16:00', '16:05', '16:10', '16:15', '16:20', '16:25', '16:30', '16:35', '16:40', '16:45', '16:50', '16:55', '17:00', '18:00', '18:05', '18:10', '18:15', '18:20', '18:25', '18:30', '18:35', '18:40', '18:45', '18:50', '18:55', '19:00', '19:05', '19:10', '19:15', '19:20', '19:25', '19:30', '19:35', '19:40', '19:45', '19:50', '19:55', '20:00', '20:05', '20:10', '20:15', '20:20', '20:25', '20:30', '20:35', '20:40', '20:45', '20:50', '20:55', '21:00'];
let returns = [8, 14, 18];
let breaks = [11, 17, 21];
/*
for (let hour = 0; hour < 24; hour++) {
  if (hour < returns[0]) continue;
  if (hour > breaks[0] && hour < returns[1]) continue;
  if (hour > breaks[1] && hour < returns[2]) continue;
  if (hour > breaks[2]) continue;

  hourUse = hour > 9 ? hour : `0${hour}`;

  for (let minute = 0; minute < 60; minute += 5) {
    minuteUse = minute > 9 ? minute : `0${minute}`;
    allowTimes.push(`${hourUse}:${minuteUse}`);

    if (hour == 11 || hour == 17 || hour == 21) break;
  }
}
*/

// SET ALLOWTIMES
function setAllowTimes(currentDate, $input) {
  $.ajax({
    url:
      urlAPI +
      '/schedules/occupied/' +
      '?' +
      $.param({
        data: moment(currentDate.toLocaleDateString(), 'DD/MM/YYYY').format('YYYY-MM-DD'),
      }),
    method: 'GET',

    beforeSend: function (jqXHR) {
      jqXHR.setRequestHeader('Authorization', 'Bearer ' + $.cookie('ph_login-token'));
    },

    success: function (data) {
      let r = returns.map((t) => moment(t, 'H').format('HH:mm'));
      let b = breaks.map((t) => moment(t, 'H').subtract(expected_time, 'm').format('HH:mm'));

      $input.datetimepicker({
        allowTimes: allowTimes.filter(function (value) {
          if (r[0] > value) return false;
          if (b[0] < value && value < r[1]) return false;
          if (b[1] < value && value < r[2]) return false;
          if (b[2] < value) return false;

          for (const range of data) {
            let start = moment(range.inicio, 'HH:mm').subtract(expected_time, 'm').format('HH:mm');
            let end = range.termino;

            if (start <= value && value <= end) return false;
          }

          return true;
        }),
      });
    },
  });
}

// API URL
var urlAPI = `${location.protocol}//${location.host}/api/v1`;

// INPUTS
var data_marcada = $('#data_marcada');
var descricao = $('#descricao');
var motivo = $('#motivo');

var tempo_previsto = $('#tempo_previsto');

if (data_marcada[0] && descricao[0]) {
  // GRUPO DOS ELEMENTOS
  var divData_marcada = data_marcada.parent().parent();
  var divDescricao = descricao.parent();

  // TEMPO PRIVISTO DA DATA MARCADA
  var expected_time = 0;

  // SETA O TEMPO PREVISTO NA ALTERAÇÃO
  if (motivo.find(':selected').val() != '') {
    expected_time = motivo.find(':selected').data('tempo_previsto') ?? 0;
  } else {
    divData_marcada.hide();
    divDescricao.show();

    data_marcada.removeAttr('required');
    descricao.attr('required', 'require');

    // RESETA O TEMPO PREVISTO
    expected_time = 0;
    if (tempo_previsto[0]) tempo_previsto.val('');
  }

  // TOGGLE ENTRE DATA MARCADA E DESCRIÇÃO BASEADO NO MOTIVO ESCOLHIDO
  $(function () {
    motivo.on('change', function () {
      let selected = $(this).find(':selected');

      data_marcada.val('');

      if (selected.val() == '') {
        divData_marcada.hide();
        divDescricao.show();

        data_marcada.removeAttr('required');
        descricao.attr('required', 'require');

        // RESETA O TEMPO PREVISTO
        expected_time = 0;
        if (tempo_previsto[0]) tempo_previsto.val('');
      } else {
        divData_marcada.show();
        divDescricao.hide();

        data_marcada.attr('required', 'require');
        descricao.removeAttr('required');

        // DEFINI O TEMPO PREVISTO
        expected_time = selected.data('tempo_previsto');
        if (tempo_previsto[0]) tempo_previsto.val(expected_time);
      }
    });
  });

  // SETA OS HORÁRIOS PERMITIDOS
  data_marcada.datetimepicker({
    onShow: function (c, $i) {
      if (expected_time) {
        setAllowTimes(c, $i);
        $i.datetimepicker({ onSelectDate: setAllowTimes });
      }
    },
  });

  if (tempo_previsto[0]) {
    $(function () {
      tempo_previsto.on('change', function () {
        expected_time = $(this).val();
      });
    });
  }
}
