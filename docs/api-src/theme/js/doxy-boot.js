$(document).ready(function() {

  $("div.headertitle").addClass("page-header");
  $("div.title").addClass("h1");

  if ($("h1").length > 0) {
    // hide header, if h1 exists
    $("div.header").hide();
  }

  $('li > a[href="index.html"] > span').before("<i class='fa fa-cog'></i> ");
  $('li > a[href="index.html"] > span').text("Home");
  $('li > a[href="modules.html"] > span').before("<i class='fa fa-square'></i> ");
  $('li > a[href="namespaces.html"] > span').before("<i class='fa fa-bars'></i> ");
  $('li > a[href="annotated.html"] > span').before("<i class='fa fa-list-ul'></i> ");
  $('li > a[href="classes.html"] > span').before("<i class='fa fa-book'></i> ");
  $('li > a[href="inherits.html"] > span').before("<i class='fa fa-sitemap'></i> ");
  $('li > a[href="functions.html"] > span').before("<i class='fa fa-list'></i> ");
  $('li > a[href="functions_func.html"] > span').before("<i class='fa fa-list'></i> ");
  $('li > a[href="functions_vars.html"] > span').before("<i class='fa fa-list'></i> ");
  $('li > a[href="functions_enum.html"] > span').before("<i class='fa fa-list'></i> ");
  $('li > a[href="functions_eval.html"] > span').before("<i class='fa fa-list'></i> ");
  $('img[src="ftv2ns.png"]').replaceWith('<span class="label label-danger">N</span> ');
  $('img[src="ftv2cl.png"]').replaceWith('<span class="label label-danger">C</span> ');
  $("span.icon:contains('N')").replaceWith('<span class="label label-primary">N</span> ');
  $("span.icon:contains('C')").replaceWith('<span class="label label-info">C</span> ');

  $("ul.tablist").addClass("nav nav-pills nav-justified");
  $("ul.tablist").css("margin-top", "0.5em");
  $("ul.tablist").css("margin-bottom", "0.5em");
  $("li.current").addClass("active");
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
  $(".memitem").addClass("panel panel-default");
  $(".memproto").addClass("panel-heading");
  $(".memdoc").addClass("panel-body");
  $("span.mlabel").addClass("label label-info");

  $("table.memberdecls").addClass("table");
  $("[class^=memitem]").addClass("active");

  $("div.ah").addClass("btn btn-default");
  $("span.mlabels").addClass("pull-right");
  $("table.mlabels").css("width", "100%")
  $("td.mlabels-right").addClass("pull-right");

  $("div.ttc").addClass("panel panel-info");
  $("div.ttname").addClass("panel-heading");
  $("div.ttdef,div.ttdoc,div.ttdeci").addClass("panel-body");

  $("table.doxtable").addClass('table');

  $("div.controller-action").addClass("panel panel-info");
  $("div.controller-action").each(function() {
    $(this).children("div:nth(0)").addClass("panel-heading");
    $(this).children("div:nth(1)").addClass("panel-body");
    $("div.controller-action em:contains('in')").addClass('label label-info');//.before("<i class='fa fa-sign-in'></i> ");
    $("div.controller-action em:contains('out')").addClass('label label-info');//.before("<i class='fa fa-sign-out'></i> ");
  });
});