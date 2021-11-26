for (sl of document.querySelectorAll('select[readonly]')) {
  sl.onmousedown = function (e) {
    this.focus();
    e.preventDefault();
  };

  sl.onkeydown = function (e) {
    // space or enter
    if (e.keyCode == 32 || e.keyCode == 13) {
      e.preventDefault();
    }
  };

  // TODO: nn usar GAMBIARRA PRA FUNCINAR NO CELULAR
  if (divDescricao && divData_marcada) {
    sl.onfocus = function () {
      if (sl.value == '') descricao.focus();
      else data_marcada.focus();
    };
  }
}
