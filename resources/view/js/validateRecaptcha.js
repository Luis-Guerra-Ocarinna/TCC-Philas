/// <reference path="../../../../../typings/globals/jquery/index.d.ts" />

$(function () {
  window.verifyRecaptchaCallback = function (response) {
    $('input[data-recaptcha]').val(response).trigger('change');
  };

  window.expiredRecaptchaCallback = function () {
    $('input[data-recaptcha]').val('').trigger('change');
  };
});
