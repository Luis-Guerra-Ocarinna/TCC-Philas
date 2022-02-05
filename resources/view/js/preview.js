/// <reference path='../../typings/globals/jquery/index.d.ts' />

$(function () {
  $("#arquivo").change(function () {
    const file = $(this)[0].files[0];
    const fileReader = new FileReader();
    fileReader.onloadend = function () {
      $("#img").attr("src", fileReader.result);
      $("#img").css("display", "inline");
    };
    fileReader.readAsDataURL(file);
  });
});
