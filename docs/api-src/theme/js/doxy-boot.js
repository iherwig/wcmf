$(document).ready(function() {

  // header
  $("div.headertitle").addClass("page-header");
  $("div.title").addClass("h1");
  if ($("h1").length > 0) {
    // hide header, if h1 exists
    $("div.header").hide();
  }

  // namespace/class list
  $("td.entry").each(function() {
    var entry = $(this);
    var nsIcon = '<span class="label label-default">N</span> ';
    var classIcon = '<span class="label label-warning">C</span> ';
    entry.find("span.icon:contains('N')").replaceWith(nsIcon);
    var link = entry.find("a.el");
    var icon = classIcon;
    if (link.length > 0) {
      if (link.attr("href").match(/^interface/)) {
        icon = '<span class="label label-info">I</span> ';
      }
    }
    else {
      icon = '<span class="label label-default">Ext</span> ';
    }
    entry.find("span.icon:contains('C')").replaceWith(icon);
  });

  $("iframe").attr("scrolling", "yes");

  $("#nav-path > ul").addClass("breadcrumb");

  $("table.params").addClass("table");
  $("div.ingroups").wrapInner("<small></small>");
  $("div.levels").css("margin", "0.5em");
  $("div.levels > span").addClass("btn btn-default btn-xs");
  $("div.levels > span").css("margin-right", "0.25em");

  $("table.directory").addClass("table table-striped");
  $("div.summary > a").addClass("btn btn-default btn-xs");
  $("table.fieldtable").addClass("table");
  $(".fragment").addClass("well").removeClass("fragment").each(function(i, node) {
    hljs.highlightBlock(node);
  });
  $(".line").each(function() {
    $(this).find("a[name], span.lineno").hide();
    $(this).html($(this).html().replace(/^&nbsp;/g, ''));
  });
  $(".memitem").addClass("panel panel-info");
  $(".memproto").addClass("panel-heading");
  $(".memdoc").addClass("panel-body");
  $("span.mlabel").addClass("label label-info");

  $("table.memberdecls").addClass("table");
  $("[class^=memitem]").addClass("active");

  $("div.ah").addClass("btn btn-default");
  $("span.mlabels").addClass("pull-right");
  $("table.mlabels").css("width", "100%")
  $("td.mlabels-right").addClass("pull-right");

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
  $("div.controller-action").addClass("panel panel-info");
  $("div.controller-action").each(function() {
    $(this).children("div:nth(0)").addClass("panel-heading");
    $(this).children("div:nth(1)").addClass("panel-body");
  });
  $('em').filter(function() {
    return $(this).text() === "in" || $(this).text() === "out";
  }).addClass('label label-info');

  // sections
  $("dl.section.note").addClass("alert").addClass("alert-info");
  $("dl.section.author").addClass("small");

  // image
  $(".image").addClass("text-center");
});