$(function() {
  $("a").tooltip();

  // create toc
  if ($(".has-toc").length > 0) {

    $('<div class="col-md-1 visible-lg"><div id="toc"></div></div>').insertBefore(".panel-default");

    $("#toc").tocify({
      selectors: "h2,h3,h4,h5",
      theme: "bootstrap",
      context: ".panel-default"
    }).data("toc-tocify");
  }
});