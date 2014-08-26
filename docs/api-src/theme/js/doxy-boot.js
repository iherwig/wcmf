$(document).ready(function() {

  // header
  $("div.headertitle").addClass("page-header");
  $("div.title").addClass("h1");
  if ($("h1").length > 0) {
    // hide header, if h1 exists
    $("div.header").hide();
  }

  // namespace/class list
  $("a.el").each(function() {
    var parent = $(this).parent();
    var classIcon = $(this).attr('href').match(/^interface/) ?
      '<span class="label label-info">I</span> ' : '<span class="label label-warning">C</span> ';
    parent.find("span.icon:contains('N')").replaceWith('<span class="label label-default">N</span> ');
    parent.parent().find("span.icon:contains('C')").replaceWith(classIcon);
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
  $(".fragment").addClass("well");
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

  // controller action tables
  $("table.doxtable").addClass('table');
  $("table.doxtable tr").each(function() {
    $(this).children("td:nth(0)").addClass("text-nowrap");
  });
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
});