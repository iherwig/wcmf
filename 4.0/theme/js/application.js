$(function() {
  $("a").tooltip();

  // create toc
  if ($(".has-toc").length > 0) {

    $('<div class="col-md-1 visible-lg"><div id="toc"></div></div>').insertBefore(".panel-default");

    $("#toc").tocify({
      selectors: "h2,h3,h4,h5",
      theme: "bootstrap",
      context: ".panel-default",
      scrollTo: 50
    }).data("toc-tocify");
  }

  // create header links
  $("h2, h3, h4, h5, h6").each(function(i, el) {
    var el, icon, id;
    el = $(el);
    id = el.find('a').first().attr('id');
    icon = '<i class="fa fa-slack"></i>';
    if (id) {
      return el.prepend($("<a />").addClass("header-link").attr("href", "#" + id).html(icon));
    }
  });

  // search
  searchBox.OnSelectItem(0);

  var sBox = $('#MSearchBox');
  var sSelectWin = $('#MSearchSelectWindow');
  var sResultWin = $('#MSearchResultsWindow');
  var iframe = $('#MSearchResults');

  sBox.css("margin-top", 8);
  sBox.append(sResultWin);
  sSelectWin.hide();
  sResultWin.addClass('dropdown-menu');
  iframe.removeAttr('frameborder');
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
    sResultWin.css({
      "top": "auto",
      "left": "auto",
      "position": "fixed",
      "z-index": 20000
    })
  });
});