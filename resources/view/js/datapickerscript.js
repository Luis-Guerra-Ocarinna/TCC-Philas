jQuery.datetimepicker.setLocale('pt-BR');
jQuery.datetimepicker.setDateFormatter('moment');

var allowTimes = ['08:00', '08:05', '08:10', '08:15', '08:20', '08:25', '08:30', '08:35', '08:40', '08:45', '08:50', '08:55', '09:00', '09:05', '09:10', '09:15', '09:20', '09:25', '09:30', '09:35', '09:40', '09:45', '09:50', '09:55', '10:00', '10:05', '10:10', '10:15', '10:20', '10:25', '10:30', '10:35', '10:40', '10:45', '10:50', '10:55', '11:00', '14:00', '14:05', '14:10', '14:15', '14:20', '14:25', '14:30', '14:35', '14:40', '14:45', '14:50', '14:55', '15:00', '15:05', '15:10', '15:15', '15:20', '15:25', '15:30', '15:35', '15:40', '15:45', '15:50', '15:55', '16:00', '16:05', '16:10', '16:15', '16:20', '16:25', '16:30', '16:35', '16:40', '16:45', '16:50', '16:55', '17:00', '18:00', '18:05', '18:10', '18:15', '18:20', '18:25', '18:30', '18:35', '18:40', '18:45', '18:50', '18:55', '19:00', '19:05', '19:10', '19:15', '19:20', '19:25', '19:30', '19:35', '19:40', '19:45', '19:50', '19:55', '20:00', '20:05', '20:10', '20:15', '20:20', '20:25', '20:30', '20:35', '20:40', '20:45', '20:50', '20:55', '21:00'];

function setMinTime(cd, $i) {
  let date = moment(cd).format('DD/MM/YYYY');
  let today = moment().format('DD/MM/YYYY');

  $i.datetimepicker({ minTime: date == today ? 0 : false });
}

$('#data_marcada').datetimepicker({
  validateOnBlur: false,
  format: 'DD/MM/YYYY HH:mm',
  minDate: 0,
  minTime: 0,
  maxTime: false,
  // step: 5,
  allowTimes: allowTimes,
  onChangeDateTime: setMinTime,
  // TODO: desabilidar finais de semanas e *feriados*
  onGenerate: function (ct) {
    jQuery(this).find('.xdsoft_date.xdsoft_weekend').addClass('xdsoft_disabled');
  },
  weekends: ['01.01.2014', '02.01.2014', '03.01.2014', '04.01.2014', '05.01.2014', '06.01.2014'],
});

$('#toggle').on('click', function () {
  $('#data_marcada').datetimepicker('toggle');
});
