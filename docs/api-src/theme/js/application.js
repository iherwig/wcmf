$(function() {
  $('a').tooltip();

  // create toc
  if ($('.has-toc').length > 0) {
    $('#toc-container').removeClass('d-none');
    $('#content-container').removeClass('col-lg-12').addClass('col-lg-9');
    $('#content-container').insertBefore($('#toc-container'));
    Toc.init({
      $nav: $('#toc')
    });
  }
  else {
  }

  // create header links
  $('h2, h3, h4, h5, h6').each(function(i, el) {
    var el, icon, id;
    el = $(el);
    id = el.find('a').first().attr('id');
    icon = '<i class="fas fa-link"></i>';
    if (id) {
      return el.prepend($('<a />').addClass('header-link').attr('href', '#' + id).html(icon));
    }
  });

  // search
  var searchField = $('#search-field');
  searchField.prop('disabled', true);
  $.ajax({
    type: "GET" ,
    url: "search/searchdata.xml" ,
    dataType: "xml" ,
    success: function(xml) {
      var docs = $(xml).find('doc');
      searchField.prop('disabled', false);
      searchField.typeahead({
        items: 15,
        minLength: 1,
        source: function(query, result) {
          $(".typeahead.dropdown-menu").addClass('dropdown-menu-right');
          var results = [];
          var urls = {};
          docs.each(function() {
            var fields = $(this).children();
            var type = fields[0].textContent;
            var name = fields[1].textContent;
            var url = fields[2].textContent.split('#')[0];
            var text = fields[4].textContent;
            if ((name.toLowerCase().indexOf(query.toLowerCase()) >= 0 || text.toLowerCase().indexOf(query.toLowerCase()) >= 0) && 
                  url.match('.+\.html(#[0-9]+)?') && !urls[url]) {
              var item = {name: '['+type+'] '+name, url: url};
              results.push(item);
              urls[url] = true;
            }
          });
          result(results);
        },
        afterSelect: function(item) {
          document.location = item.url;
        }
      });
    }
  });

  // change visual styles

  // header
  if ($("h1").length == 0) {
    // show header, if no h1 exists
    $(".headertitle").addClass("pb-2 mt-4 mb-2 border-bottom");
    $(".headertitle .title").addClass("h1").show();
  }

  // namespace/class list
  $("td.entry").each(function() {
    var el = $(this);
    var nsIcon = '<span class="badge badge-default">N</span> ';
    var classIcon = '<span class="badge badge-warning">C</span> ';
    el.find("span.icon:contains('N')").replaceWith(nsIcon);
    var link = el.find("a.el");
    var icon = classIcon;
    if (link.length > 0) {
      if (link.attr("href").match(/^interface/)) {
        icon = '<span class="badge badge-info">I</span> ';
      }
    }
    else {
      icon = '<span class="badge badge-default">Ext</span> ';
    }
    el.find("span.icon:contains('C')").replaceWith(icon);
  });

  $("#nav-path > ul").addClass("breadcrumb");
  $("#nav-path > ul li").addClass("breadcrumb-item");

  $("table.params").addClass("table");
  $("div.ingroups").wrapInner("<small></small>");
  $("div.levels").css("margin", "0.5em");
  $("div.levels > span").addClass("btn btn-secondary btn-sm");
  $("div.levels > span").css("margin-right", "0.25em");

  $("table.directory").addClass("table table-striped");
  $("div.summary > a").addClass("btn btn-secondary btn-sm");
  $("table.fieldtable").addClass("table");

  $(".fragment").removeClass("fragment").addClass("card").each(function(i, node) {
    hljs.highlightBlock(node);    
  });
  $(".memtitle").hide();
  $(".memitem").addClass("card border border-secondary rounded mb-2");
  $(".memproto").addClass("card-header");
  $(".memdoc").addClass("card-body");
  $("span.mlabel").addClass("badge badge-info");

  $("table.memberdecls").addClass("table");
  $("[class^=memitem]").addClass("active");

  $("div.ah").addClass("btn btn-secondary");
  $("span.mlabels").addClass("float-right");
  $("table.mlabels").css("width", "100%")
  $("td.mlabels-right").addClass("float-right");

  $("div.ttc").hide();

  // tables
  $("table.doxtable").addClass('table').addClass('table-striped');
  $("table.doxtable tr").each(function() {
    $(this).children("td:nth(0)").addClass("text-nowrap");
  });

  // image tables
  $("div.image-table table").removeClass('table-striped');
  $("div.image-table th").addClass("text-center");

  // controller action tables
  $("div.controller-action").addClass("card border border-secondary rounded mb-2");
  $("div.controller-action").each(function() {
    var el = $(this);
    el.children("div:nth(0)").addClass("card-header");
    el.children("div:nth(1)").addClass("card-body");
  });
  $('em').filter(function() {
    var el = $(this);
    return el.text() === "in" || el.text() === "out";
  }).addClass('badge badge-info');

  // sections
  $("dl.section.note").addClass("alert").addClass("alert-info");
  $("dl.section.author").addClass("small");

  // image
  $(".image").addClass("text-center");  
});