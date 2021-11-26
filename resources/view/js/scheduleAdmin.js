/// <reference path="../../../../../typings/globals/jquery/index.d.ts" />

// ALLOWTIMES
let returns = [8, 14, 18];
let breaks = [11, 17, 21];

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
var urlAPI = location.protocol + '//' + location.host + '/TCC/Protp_Philas/mvc' + '/api/v1';

// INPUTS
var motivo = $('#motivo');
var descricao = $('#descricao');
var data_marcada = $('#data_marcada');
var tempo_previsto = $('#tempo_previsto');

// GRUPO DOS ELEMENTOS
var divData_marcada = data_marcada.parent().parent();
var divDescricao = descricao.parent();

// TEMPO PRIVISTO DA DATA MARCADA
var expected_time = 0;

// SETA O TEMPO PREVISTO E HABILITA A DATA NA ALTERAÇÃO
if (tempo_previsto.val()) {
  expected_time = parseInt(tempo_previsto.val());
}

$(function () {
  motivo.on('change', function () {
    let selected = $(this).find(':selected');

    data_marcada.val('');

    if (selected.val() == '') {
      // RESETA O TEMPO PREVISTO
      expected_time = 0;
      if (tempo_previsto[0]) tempo_previsto.val('');
    } else {
      // DEFINI O TEMPO PREVISTO
      expected_time = selected.data('tempo_previsto');
      if (tempo_previsto[0]) tempo_previsto.val(expected_time);
    }
  });

  tempo_previsto.on('change', function () {
    data_marcada.val('');
    expected_time = $(this).val();
  });

  data_marcada.on('change', function () {
    if ($(this).val() == '') $(this).removeAttr('required');
    else $(this).attr('required', 'required');
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
