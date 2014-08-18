$(function() {
  $('a').tooltip();

  if ($("#toc").length > 0) {
    var toc = $("#toc").tocify().data("toc-tocify");
    toc.setOptions({
      showEffect: "fadeIn",
      scrollTo: 50,
      smoothScroll: false
    });
  }
});