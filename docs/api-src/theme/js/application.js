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

  // search
  searchBox.OnSelectItem(0);
  $('#MSearchSelectWindow').hide();
  $('#MSearchBox').css("margin-top", 8);

  var sResultWin = $('#MSearchResultsWindow');
  sResultWin.addClass('dropdown-menu');

  var iframe = $('#MSearchResults');
  iframe.on( "load", function() {
    iframe.addClass('embed-responsive-item');
    var head = iframe.contents().find('head');
    var body = iframe.contents().find('body');
    head.find('link').remove();
    head.append($("<link/>", {
      rel: "stylesheet",
      href: "../theme/css/style.css",
      type: "text/css"
    }));
    head.append($("<link/>", {
      rel: "stylesheet",
      href: "../theme/bootstrap3/css/bootstrap.min.css",
      type: "text/css"
    }));
    body.css("padding", 10);

    if (sResultWin.offset().left < 0) {
      sResultWin.css("left", 0);
    }
  });
});