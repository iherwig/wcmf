$(function() {
  $('a').tooltip();

  // create toc
  if ($(".has-toc").length > 0) {

//    <div class="col-sm-offset-1 col-sm-10 panel panel-default">

//    <div class="col-sm-1 hidden-xs"><div id="toc"></div></div>
//    <div class="col-sm-offset-1 col-sm-10 panel panel-default">

    $('<div class="col-sm-1 hidden-xs"><div id="toc"></div></div>').insertBefore( ".panel-default" );

    var toc = $("#toc").tocify({
      selectors: "h2,h3,h4,h5",
      theme: "bootstrap",
      context: ".panel-default"
    }).data("toc-tocify");
  }
});