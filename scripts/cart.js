$(document).ready(function () {
  var winWidth = $(window).width();
  if (winWidth < 1000) {
    $(".navbar-collapse.collapse ul").css("background-color", "#bedbbb");
  }

  $(window).resize(function () {
    var winWidth = $(window).width();
    if (winWidth > 1000) {
      $(".navbar-collapse.collapse ul").css("background-color", "transparent");
      $(".navbar-collapse.collapse .nav-link").css("color", "white");
    }
    if (winWidth < 1000) {
      $(".navbar-collapse.collapse ul").css("background-color", "#bedbbb"); //d0e8f2
      $(".navbar-collapse.collapse .nav-link").css("color", "black");
    }
  });
});
