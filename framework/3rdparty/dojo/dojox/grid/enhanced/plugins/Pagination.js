/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.grid.enhanced.plugins.Pagination"]){
dojo._hasResource["dojox.grid.enhanced.plugins.Pagination"]=true;
dojo.provide("dojox.grid.enhanced.plugins.Pagination");
dojo.require("dijit.form.NumberTextBox");
dojo.require("dijit.form.Button");
dojo.require("dojox.grid.enhanced._Plugin");
dojo.require("dojox.grid.enhanced.plugins.Dialog");
dojo.require("dojox.grid.enhanced.plugins._StoreLayer");
dojo.requireLocalization("dojox.grid.enhanced","Pagination",null,"ROOT,kk");
dojo.declare("dojox.grid.enhanced.plugins.Pagination",dojox.grid.enhanced._Plugin,{name:"pagination",pageSize:25,defaultRows:25,_currentPage:0,_maxSize:0,init:function(){
this.gh=null;
this.grid.rowsPerPage=this.pageSize=this.grid.rowsPerPage?this.grid.rowsPerPage:this.pageSize;
this.grid.usingPagination=true;
this.nls=dojo.i18n.getLocalization("dojox.grid.enhanced","Pagination");
this._wrapStoreLayer();
this._createPaginators(this.option);
this._regApis();
},_createPaginators:function(_1){
this.paginators=[];
if(_1.position==="both"){
this.paginators=[new dojox.grid.enhanced.plugins._Paginator(dojo.mixin(_1,{position:"bottom",plugin:this})),new dojox.grid.enhanced.plugins._Paginator(dojo.mixin(_1,{position:"top",plugin:this}))];
}else{
this.paginators=[new dojox.grid.enhanced.plugins._Paginator(dojo.mixin(_1,{plugin:this}))];
}
},_wrapStoreLayer:function(){
var g=this.grid,ns=dojox.grid.enhanced.plugins;
this._store=g.store;
this.query=g.query;
this.forcePageStoreLayer=new ns._ForcedPageStoreLayer(this);
ns.wrap(g,"_storeLayerFetch",this.forcePageStoreLayer);
this.connect(g,"setQuery",function(_2){
if(_2!==this.query){
this.query=_2;
}
});
},_onNew:function(_3,_4){
var _5=Math.ceil(this._maxSize/this.pageSize);
if((this._currentPage+1===_5&&this.grid.rowCount<this.pageSize)||this.showAll){
dojo.hitch(this.grid,this._originalOnNew)(_3,_4);
this.forcePageStoreLayer.endIdx++;
}
this._maxSize++;
if(this.showAll){
this.pageSize++;
}
if(this.showAll&&this.grid.autoHeight){
this.grid._refresh();
}else{
dojo.forEach(this.paginators,function(p){
p.update();
});
}
},_removeSelectedRows:function(){
this._multiRemoving=true;
this._originalRemove();
this._multiRemoving=false;
this.grid.resize();
},_onDelete:function(){
if(!this._multiRemoving){
this.grid.resize();
}
if(this.grid.get("rowCount")===0){
this.prevPage();
}
},_regApis:function(){
var g=this.grid;
g.gotoPage=dojo.hitch(this,this.gotoPage);
g.nextPage=dojo.hitch(this,this.nextPage);
g.prevPage=dojo.hitch(this,this.prevPage);
g.gotoFirstPage=dojo.hitch(this,this.gotoFirstPage);
g.gotoLastPage=dojo.hitch(this,this.gotoLastPage);
g.changePageSize=dojo.hitch(this,this.changePageSize);
g.showGotoPageButton=dojo.hitch(this,this.showGotoPageButton);
g.getTotalRowCount=dojo.hitch(this,this.getTotalRowCount);
this.originalScrollToRow=dojo.hitch(g,g.scrollToRow);
g.scrollToRow=dojo.hitch(this,this.scrollToRow);
this._originalOnNew=dojo.hitch(g,g._onNew);
this._originalRemove=dojo.hitch(g,g.removeSelectedRows);
g.removeSelectedRows=dojo.hitch(this,this._removeSelectedRows);
g._onNew=dojo.hitch(this,this._onNew);
this.connect(g,"_onDelete",dojo.hitch(this,this._onDelete));
},destroy:function(){
this.inherited(arguments);
var g=this.grid;
try{
dojo.forEach(this.paginators,function(p){
p.destroy();
});
g.unwrap(this.forcePageStoreLayer.name());
g._onNew=this._originalOnNew;
g.removeSelectedRows=this._originalRemove;
g.scrollToRow=this.originalScrollToRow;
this.paginators=null;
this.nls=null;
}
catch(e){
console.error("Pagination destroy error: ",e);
}
},nextPage:function(){
if(this._maxSize>((this._currentPage+1)*this.pageSize)){
this.gotoPage(this._currentPage+2);
}
},prevPage:function(){
if(this._currentPage>0){
this.gotoPage(this._currentPage);
}
},gotoPage:function(_6){
var _7=Math.ceil(this._maxSize/this.pageSize);
_6--;
if(_6<_7&&_6>=0&&this._currentPage!==_6){
this._currentPage=_6;
this.grid.setQuery(this.query);
this.grid.resize();
}
},gotoFirstPage:function(){
this.gotoPage(1);
},gotoLastPage:function(){
var _8=Math.ceil(this._maxSize/this.pageSize);
this.gotoPage(_8);
},changePageSize:function(_9){
if(typeof _9=="string"){
_9=parseInt(_9,10);
}
var _a=this.pageSize*this._currentPage;
dojo.forEach(this.paginators,function(f){
f.currentPageSize=this.grid.rowsPerPage=this.pageSize=_9;
if(_9>=this._maxSize){
this.grid.rowsPerPage=this.defaultRows;
this.grid.usingPagination=false;
}else{
this.grid.usingPagination=true;
}
},this);
var _b=_a+Math.min(this.pageSize,this._maxSize);
var cp=this._currentPage;
if(_b>this._maxSize){
this.gotoLastPage();
}else{
this._currentPage=Math.ceil(_a/this.pageSize)+1;
if(cp!==this._currentPage){
this.gotoPage(this._currentPage);
}else{
this.grid._refresh(true);
}
}
this.grid.resize();
},showGotoPageButton:function(_c){
dojo.forEach(this.paginators,function(p){
p._showGotoButton(_c);
});
},scrollToRow:function(_d){
var _e=parseInt(_d/this.pageSize,10),_f=Math.ceil(this._maxSize/this.pageSize);
if(_e>_f){
return;
}
this.gotoPage(_e+1);
var _10=_d%this.pageSize;
this.grid.setScrollTop(this.grid.scroller.findScrollTop(_10)+1);
},getTotalRowCount:function(){
return this._maxSize;
}});
dojo.declare("dojox.grid.enhanced.plugins._ForcedPageStoreLayer",dojox.grid.enhanced.plugins._StoreLayer,{tags:["presentation"],constructor:function(_11){
this._plugin=_11;
},_fetch:function(_12){
var _13=this,_14=_13._plugin,_15=_14.grid,_16=_12.scope||dojo.global,_17=_12.onBegin;
_12.start=_14._currentPage*_14.pageSize+_12.start;
_13.startIdx=_12.start;
_13.endIdx=_12.start+_14.pageSize-1;
if(_17&&(_14.showAll||dojo.every(_14.paginators,function(p){
return !p.sizeSwitch&&!p.pageStepper&&!p.gotoButton;
}))){
_12.onBegin=function(_18,req){
_14._maxSize=_18;
_13.startIdx=0;
_13.endIdx=_18-1;
dojo.forEach(_14.paginators,function(f){
f.update();
});
req.onBegin=_17;
req.onBegin.call(_16,_18,req);
};
}else{
if(_17){
_12.onBegin=function(_19,req){
req.start=0;
req.count=_14.pageSize;
_14._maxSize=_19;
_13.endIdx=_13.endIdx>_19?(_19-1):_13.endIdx;
if(_13.startIdx>_19&&_19!==0){
_15._pending_requests[req.start]=false;
_14.gotoFirstPage();
}
dojo.forEach(_14.paginators,function(f){
f.update();
});
req.onBegin=_17;
req.onBegin.call(_16,Math.min(_14.pageSize,(_19-_13.startIdx)),req);
};
}
}
return dojo.hitch(this._store,this._originFetch)(_12);
}});
dojo.declare("dojox.grid.enhanced.plugins._Paginator",[dijit._Widget,dijit._Templated],{templateString:"<div dojoAttachPoint=\"paginatorBar\">\n\t<table cellpadding=\"0\" cellspacing=\"0\"  class=\"dojoxGridPaginator\">\n\t\t<tr>\n\t\t\t<td dojoAttachPoint=\"descriptionTd\" class=\"dojoxGridDescriptionTd\">\n\t\t\t\t<div dojoAttachPoint=\"descriptionDiv\" class=\"dojoxGridDescription\" />\n\t\t\t</td>\n\t\t\t<td dojoAttachPoint=\"sizeSwitchTd\" class=\"dojoxGridPaginatorSwitch\"></td>\n\t\t\t<td dojoAttachPoint=\"pageStepperTd\" class=\"dojoxGridPaginatorFastStep\">\n\t\t\t\t<div dojoAttachPoint=\"pageStepperDiv\" class=\"dojoxGridPaginatorStep\"></div>\n\t\t\t</td>\n\t\t</tr>\n\t</table>\n</div>\n",position:"bottom",_maxItemSize:0,description:true,pageStepper:true,maxPageStep:7,sizeSwitch:true,pageSizes:["10","25","50","100","All"],gotoButton:false,constructor:function(_1a){
dojo.mixin(this,_1a);
this.grid=this.plugin.grid;
this.itemTitle=this.itemTitle?this.itemTitle:this.plugin.nls.itemTitle;
this.descTemplate=this.descTemplate?this.descTemplate:this.plugin.nls.descTemplate;
},postCreate:function(){
this.inherited(arguments);
this._setWidthValue();
var _1b=this;
var g=this.grid;
this.plugin.connect(g,"_resize",dojo.hitch(this,"_resetGridHeight"));
this._originalResize=dojo.hitch(g,"resize");
g.resize=function(_1c,_1d){
_1b._changeSize=g._pendingChangeSize=_1c;
_1b._resultSize=g._pendingResultSize=_1d;
g.sizeChange();
};
this._placeSelf();
},destroy:function(){
this.inherited(arguments);
this.grid.focus.removeArea("pagination"+this.position.toLowerCase());
if(this._gotoPageDialog){
this._gotoPageDialog.destroy();
dojo.destroy(this.gotoPageTd);
delete this.gotoPageTd;
delete this._gotoPageDialog;
}
this.grid.resize=this._originalResize;
this.pageSizes=null;
},update:function(){
this.currentPageSize=this.plugin.pageSize;
this._maxItemSize=this.plugin._maxSize;
this._updateDescription();
this._updatePageStepper();
this._updateSizeSwitch();
this._updateGotoButton();
},_setWidthValue:function(){
var _1e=["description","sizeSwitch","pageStepper"];
var _1f=function(_20,_21){
var reg=new RegExp(_21+"$");
return reg.test(_20);
};
dojo.forEach(_1e,function(t){
var _22,_23=this[t];
if(_23===undefined||typeof _23=="boolean"){
return;
}
if(dojo.isString(_23)){
_22=_1f(_23,"px")||_1f(_23,"%")||_1f(_23,"em")?_23:parseInt(_23,10)>0?parseInt(_23,10)+"px":null;
}else{
if(typeof _23==="number"&&_23>0){
_22=_23+"px";
}
}
this[t]=_22?true:false;
this[t+"Width"]=_22;
},this);
},_regFocusMgr:function(_24){
this.grid.focus.addArea({name:"pagination"+_24,onFocus:dojo.hitch(this,this._onFocusPaginator),onBlur:dojo.hitch(this,this._onBlurPaginator),onMove:dojo.hitch(this,this._moveFocus),onKeyDown:dojo.hitch(this,this._onKeyDown)});
switch(_24){
case "top":
this.grid.focus.placeArea("pagination"+_24,"before","header");
break;
case "bottom":
default:
this.grid.focus.placeArea("pagination"+_24,"after","content");
break;
}
},_placeSelf:function(){
var g=this.grid;
var _25=dojo.trim(this.position.toLowerCase());
switch(_25){
case "top":
this.placeAt(g.viewsHeaderNode,"before");
this._regFocusMgr("top");
break;
case "bottom":
default:
this.placeAt(g.viewsNode,"after");
this._regFocusMgr("bottom");
break;
}
},_resetGridHeight:function(_26,_27){
var g=this.grid;
_26=_26||this._changeSize;
_27=_27||this._resultSize;
delete this._changeSize;
delete this._resultSize;
if(g._autoHeight){
return;
}
var _28=g._getPadBorder().h;
if(!this.plugin.gh){
this.plugin.gh=dojo.contentBox(g.domNode).h+2*_28;
}
if(_27){
_26=_27;
}
if(_26){
this.plugin.gh=dojo.contentBox(g.domNode).h+2*_28;
}
var gh=this.plugin.gh,hh=g._getHeaderHeight(),ph=dojo.marginBox(this.domNode).h;
ph=this.plugin.paginators[1]?ph*2:ph;
if(typeof g.autoHeight=="number"){
var cgh=gh+ph-_28;
dojo.style(g.domNode,"height",cgh+"px");
dojo.style(g.viewsNode,"height",(cgh-ph-hh)+"px");
this._styleMsgNode(hh,dojo.marginBox(g.viewsNode).w,cgh-ph-hh);
}else{
var h=gh-ph-hh-_28;
dojo.style(g.viewsNode,"height",h+"px");
var _29=dojo.some(g.views.views,function(v){
return v.hasHScrollbar();
});
dojo.forEach(g.viewsNode.childNodes,function(c,idx){
dojo.style(c,"height",h+"px");
});
dojo.forEach(g.views.views,function(v,idx){
if(v.scrollboxNode){
if(!v.hasHScrollbar()&&_29){
dojo.style(v.scrollboxNode,"height",(h-dojox.html.metrics.getScrollbar().h)+"px");
}else{
dojo.style(v.scrollboxNode,"height",h+"px");
}
}
});
this._styleMsgNode(hh,dojo.marginBox(g.viewsNode).w,h);
}
},_styleMsgNode:function(top,_2a,_2b){
var _2c=this.grid.messagesNode;
dojo.style(_2c,{"position":"absolute","top":top+"px","width":_2a+"px","height":_2b+"px","z-Index":"100"});
},_updateDescription:function(){
var s=this.plugin.forcePageStoreLayer;
if(this.description&&this.descriptionDiv){
this.descriptionDiv.innerHTML=this._maxItemSize>0?dojo.string.substitute(this.descTemplate,[this.itemTitle,this._maxItemSize,s.startIdx+1,s.endIdx+1]):"0 "+this.itemTitle;
}
if(this.descriptionWidth){
dojo.style(this.descriptionTd,"width",this.descriptionWidth);
}
},_updateSizeSwitch:function(){
if(!this.sizeSwitchTd){
return;
}
if(!this.sizeSwitch||this._maxItemSize<=0){
dojo.style(this.sizeSwitchTd,"display","none");
return;
}else{
dojo.style(this.sizeSwitchTd,"display","");
}
if(this.initializedSizeNode&&!this.pageSizeValue){
return;
}
if(this.sizeSwitchTd.childNodes.length<1){
this._createSizeSwitchNodes();
}
this._updateSwitchNodeClass();
this._moveToNextActivableNode(this._getAllPageSizeNodes(),this.pageSizeValue);
this.pageSizeValue=null;
},_createSizeSwitchNodes:function(){
var _2d=null;
if(!this.pageSizes||this.pageSizes.length<1){
return;
}
dojo.forEach(this.pageSizes,function(_2e){
_2e=dojo.trim(_2e);
_2d=dojo.create("span",{innerHTML:_2e,value:_2e,tabindex:0},this.sizeSwitchTd,"last");
var _2f=_2e.toLowerCase()=="all"?this.plugin.nls.allItemsLabelTemplate:dojo.string.substitute(this.plugin.nls.pageSizeLabelTemplate,[_2e]);
dijit.setWaiState(_2d,"label",_2f);
this.plugin.connect(_2d,"onclick",dojo.hitch(this,"_onSwitchPageSize"));
this.plugin.connect(_2d,"onmouseover",function(e){
dojo.addClass(e.target,"dojoxGridPageTextHover");
});
this.plugin.connect(_2d,"onmouseout",function(e){
dojo.removeClass(e.target,"dojoxGridPageTextHover");
});
_2d=dojo.create("span",{innerHTML:"|"},this.sizeSwitchTd,"last");
},this);
dojo.destroy(_2d);
this.initializedSizeNode=true;
if(this.sizeSwitchWidth){
dojo.style(this.sizeSwitchTd,"width",this.sizeSwitchWidth);
}
},_updateSwitchNodeClass:function(){
var _30=null;
var _31=false;
var _32=function(_33,_34){
if(_34){
dojo.addClass(_33,"dojoxGridActivedSwitch");
dojo.attr(_33,"tabindex","-1");
_31=true;
}else{
dojo.addClass(_33,"dojoxGridInactiveSwitch");
dojo.attr(_33,"tabindex","0");
}
};
dojo.forEach(this.sizeSwitchTd.childNodes,function(_35){
if(_35.value){
_30=_35.value;
dojo.removeClass(_35);
if(this.pageSizeValue){
_32(_35,_30===this.pageSizeValue&&!_31);
}else{
if(_30.toLowerCase()=="all"){
_30=this._maxItemSize;
}
_32(_35,this.currentPageSize===parseInt(_30,10)&&!_31);
}
}
},this);
},_updatePageStepper:function(){
if(!this.pageStepperTd){
return;
}
if(!this.pageStepper||this._maxItemSize<=0){
dojo.style(this.pageStepperTd,"display","none");
return;
}else{
dojo.style(this.pageStepperTd,"display","");
}
if(this.pageStepperDiv.childNodes.length<1){
this._createPageStepNodes();
this._createWardBtns();
}else{
this._resetPageStepNodes();
}
this._updatePageStepNodeClass();
this._moveToNextActivableNode(this._getAllPageStepNodes(),this.pageStepValue);
this.pageStepValue=null;
},_createPageStepNodes:function(){
var _36=this._getStartPage(),_37=this._getStepPageSize(),_38=null;
for(var i=_36;i<this.maxPageStep+1;i++){
_38=dojo.create("div",{innerHTML:i,value:i,tabindex:i<_36+_37?0:-1},this.pageStepperDiv,"last");
dijit.setWaiState(_38,"label",dojo.string.substitute(this.plugin.nls.pageStepLabelTemplate,[i+""]));
this.plugin.connect(_38,"onclick",dojo.hitch(this,"_onPageStep"));
this.plugin.connect(_38,"onmouseover",function(e){
dojo.addClass(e.target,"dojoxGridPageTextHover");
});
this.plugin.connect(_38,"onmouseout",function(e){
dojo.removeClass(e.target,"dojoxGridPageTextHover");
});
dojo.style(_38,"display",i<_36+_37?"block":"none");
}
if(this.pageStepperWidth){
dojo.style(this.pageStepperTd,"width",this.pageStepperWidth);
}
},_createWardBtns:function(){
var _39=this;
var _3a={prevPage:"&#60;",firstPage:"&#171;",nextPage:"&#62;",lastPage:"&#187;"};
var _3b=function(_3c,_3d,_3e){
var _3f=dojo.create("div",{value:_3c,title:_3d,tabindex:1},_39.pageStepperDiv,_3e);
_39.plugin.connect(_3f,"onclick",dojo.hitch(_39,"_onPageStep"));
dijit.setWaiState(_3f,"label",_3d);
var _40=dojo.create("span",{value:_3c,title:_3d,innerHTML:_3a[_3c]},_3f,_3e);
dojo.addClass(_40,"dojoxGridWardButtonInner");
};
_3b("prevPage",this.plugin.nls.prevTip,"first");
_3b("firstPage",this.plugin.nls.firstTip,"first");
_3b("nextPage",this.plugin.nls.nextTip,"last");
_3b("lastPage",this.plugin.nls.lastTip,"last");
},_resetPageStepNodes:function(){
var _41=this._getStartPage(),_42=this._getStepPageSize(),_43=this.pageStepperDiv.childNodes,_44=null;
for(var i=_41,j=2;j<_43.length-2;j++,i++){
_44=_43[j];
if(i<_41+_42){
dojo.attr(_44,"innerHTML",i);
dojo.attr(_44,"value",i);
dojo.style(_44,"display","block");
dijit.setWaiState(_44,"label",dojo.string.substitute(this.plugin.nls.pageStepLabelTemplate,[i+""]));
}else{
dojo.style(_44,"display","none");
}
}
},_updatePageStepNodeClass:function(){
var _45=null,_46=this._getCurrentPageNo(),_47=this._getPageCount(),_48=0;
var _49=function(_4a,_4b,_4c){
var _4d=_4a.value,_4e=_4b?"dojoxGrid"+_4d+"Btn":"dojoxGridInactived",_4f=_4b?"dojoxGrid"+_4d+"BtnDisable":"dojoxGridActived";
if(_4c){
dojo.addClass(_4a,_4f);
dojo.attr(_4a,"tabindex","-1");
}else{
dojo.addClass(_4a,_4e);
dojo.attr(_4a,"tabindex","0");
}
};
dojo.forEach(this.pageStepperDiv.childNodes,function(_50){
dojo.removeClass(_50);
if(isNaN(parseInt(_50.value,10))){
dojo.addClass(_50,"dojoxGridWardButton");
var _51=_50.value=="prevPage"||_50.value=="firstPage"?1:_47;
_49(_50,true,(_46==_51));
}else{
_45=parseInt(_50.value,10);
_49(_50,false,(_45===_46||dojo.style(_50,"display")==="none"));
}
},this);
},_showGotoButton:function(_52){
this.gotoButton=_52;
this._updateGotoButton();
},_updateGotoButton:function(){
if(!this.gotoButton){
if(this.gotoPageTd){
if(this._gotoPageDialog){
this._gotoPageDialog.destroy();
}
dojo.destroy(this.gotoPageDiv);
dojo.destroy(this.gotoPageTd);
delete this.gotoPageDiv;
delete this.gotoPageTd;
}
return;
}
if(!this.gotoPageTd){
this._createGotoNode();
}
dojo.toggleClass(this.gotoPageDiv,"dojoxGridPaginatorGotoDivDisabled",this.plugin.pageSize>=this.plugin._maxSize);
},_createGotoNode:function(){
this.gotoPageTd=dojo.create("td",{},dojo.query("tr",this.domNode)[0],"last");
dojo.addClass(this.gotoPageTd,"dojoxGridPaginatorGotoTd");
this.gotoPageDiv=dojo.create("div",{tabindex:"0",title:this.plugin.nls.gotoButtonTitle},this.gotoPageTd,"first");
dojo.addClass(this.gotoPageDiv,"dojoxGridPaginatorGotoDiv");
this.plugin.connect(this.gotoPageDiv,"onclick",dojo.hitch(this,"_openGotopageDialog"));
var _53=dojo.create("span",{title:this.plugin.nls.gotoButtonTitle,innerHTML:"&#8869;"},this.gotoPageDiv,"last");
dojo.addClass(_53,"dojoxGridWardButtonInner");
},_openGotopageDialog:function(_54){
if(!this._gotoPageDialog){
this._gotoPageDialog=new dojox.grid.enhanced.plugins.pagination._GotoPageDialog(this.plugin);
}
if(!this._currentFocusNode){
this.grid.focus.focusArea("pagination"+this.position,_54);
}else{
this._currentFocusNode=this.gotoPageDiv;
}
if(this.focusArea!="pageStep"){
this.focusArea="pageStep";
}
this._gotoPageDialog.updatePageCount();
this._gotoPageDialog.showDialog();
},_onFocusPaginator:function(_55,_56){
if(!this._currentFocusNode){
if(_56>0){
return this._onFocusPageSizeNode(_55)?true:this._onFocusPageStepNode(_55);
}else{
if(_56<0){
return this._onFocusPageStepNode(_55)?true:this._onFocusPageSizeNode(_55);
}else{
return false;
}
}
}else{
if(_56>0){
return this.focusArea==="pageSize"?this._onFocusPageStepNode(_55):false;
}else{
if(_56<0){
return this.focusArea==="pageStep"?this._onFocusPageSizeNode(_55):false;
}else{
return false;
}
}
}
},_onFocusPageSizeNode:function(_57){
var _58=this._getPageSizeActivableNodes();
if(_57&&_57.type!=="click"){
if(_58[0]){
dijit.focus(_58[0]);
this._currentFocusNode=_58[0];
this.focusArea="pageSize";
this._stopEvent(_57);
return true;
}else{
return false;
}
}
if(_57&&_57.type=="click"){
if(dojo.indexOf(this._getPageSizeActivableNodes(),_57.target)>-1){
this.focusArea="pageSize";
this._stopEvent(_57);
return true;
}
}
return false;
},_onFocusPageStepNode:function(_59){
var _5a=this._getPageStepActivableNodes();
if(_59&&_59.type!=="click"){
if(_5a[0]){
dijit.focus(_5a[0]);
this._currentFocusNode=_5a[0];
this.focusArea="pageStep";
this._stopEvent(_59);
return true;
}else{
if(this.gotoPageDiv){
dijit.focus(this.gotoPageDiv);
this._currentFocusNode=this.gotoPageDiv;
this.focusArea="pageStep";
this._stopEvent(_59);
return true;
}else{
return false;
}
}
}
if(_59&&_59.type=="click"){
if(dojo.indexOf(this._getPageStepActivableNodes(),_59.target)>-1){
this.focusArea="pageStep";
this._stopEvent(_59);
return true;
}else{
if(_59.target==this.gotoPageDiv){
dijit.focus(this.gotoPageDiv);
this._currentFocusNode=this.gotoPageDiv;
this.focusArea="pageStep";
this._stopEvent(_59);
return true;
}
}
}
return false;
},_onFocusGotoPageNode:function(_5b){
if(!this.gotoButton||!this.gotoPageTd){
return false;
}
if(_5b&&_5b.type!=="click"||(_5b.type=="click"&&_5b.target==this.gotoPageDiv)){
dijit.focus(this.gotoPageDiv);
this._currentFocusNode=this.gotoPageDiv;
this.focusArea="gotoButton";
this._stopEvent(_5b);
return true;
}
return true;
},_onBlurPaginator:function(_5c,_5d){
var _5e=this._getPageSizeActivableNodes(),_5f=this._getPageStepActivableNodes();
if(_5d>0&&this.focusArea==="pageSize"&&(_5f.length>1||this.gotoButton)){
return false;
}else{
if(_5d<0&&this.focusArea==="pageStep"&&_5e.length>1){
return false;
}
}
this._currentFocusNode=null;
this.focusArea=null;
return true;
},_onKeyDown:function(_60,_61){
if(_61){
return;
}
if(_60.altKey||_60.metaKey){
return;
}
var dk=dojo.keys;
if(_60.keyCode===dk.ENTER||_60.keyCode===dk.SPACE){
if(dojo.indexOf(this._getPageStepActivableNodes(),this._currentFocusNode)>-1){
this._onPageStep(_60);
}else{
if(dojo.indexOf(this._getPageSizeActivableNodes(),this._currentFocusNode)>-1){
this._onSwitchPageSize(_60);
}else{
if(this._currentFocusNode===this.gotoPageDiv){
this._openGotopageDialog(_60);
}
}
}
}
this._stopEvent(_60);
},_moveFocus:function(_62,_63,evt){
var _64;
if(this.focusArea=="pageSize"){
_64=this._getPageSizeActivableNodes();
}else{
if(this.focusArea=="pageStep"){
_64=this._getPageStepActivableNodes();
if(this.gotoPageDiv){
_64.push(this.gotoPageDiv);
}
}
}
if(_64.length<1){
return;
}
var _65=dojo.indexOf(_64,this._currentFocusNode);
var _66=_65+_63;
if(_66>=0&&_66<_64.length){
dijit.focus(_64[_66]);
this._currentFocusNode=_64[_66];
}
this._stopEvent(evt);
},_getPageSizeActivableNodes:function(){
return dojo.query("span[tabindex='0']",this.sizeSwitchTd);
},_getPageStepActivableNodes:function(){
return (dojo.query("div[tabindex='0']",this.pageStepperDiv));
},_getAllPageSizeNodes:function(){
var _67=[];
dojo.forEach(this.sizeSwitchTd.childNodes,function(_68){
if(_68.value){
_67.push(_68);
}
});
return _67;
},_getAllPageStepNodes:function(){
var _69=[];
for(var i=0,len=this.pageStepperDiv.childNodes.length;i<len;i++){
_69.push(this.pageStepperDiv.childNodes[i]);
}
return _69;
},_moveToNextActivableNode:function(_6a,_6b){
if(!_6b){
return;
}
if(_6a.length<2){
this.grid.focus.tab(1);
}
var nl=[],_6c=null,_6d=0;
dojo.forEach(_6a,function(n){
if(n.value==_6b){
nl.push(n);
_6c=n;
}else{
if(dojo.attr(n,"tabindex")=="0"){
nl.push(n);
}
}
});
if(nl.length<2){
this.grid.focus.tab(1);
}
_6d=dojo.indexOf(nl,_6c);
if(dojo.attr(_6c,"tabindex")!="0"){
_6c=nl[_6d+1]?nl[_6d+1]:nl[_6d-1];
}
dijit.focus(_6c);
this._currentFocusNode=_6c;
},_stopEvent:function(_6e){
if(_6e&&_6e instanceof Event){
dojo.stopEvent(_6e);
}
},_onSwitchPageSize:function(e){
var _6f=this.pageSizeValue=e.target.value;
if(!_6f){
return;
}
if(dojo.trim(_6f.toLowerCase())=="all"){
_6f=this._maxItemSize;
}
this.plugin.showAll=parseInt(_6f,10)>=this._maxItemSize?true:false;
this.plugin.grid.usingPagination=!this.plugin.showAll;
_6f=parseInt(_6f,10);
if(isNaN(_6f)||_6f<=0){
return;
}
if(!this._currentFocusNode){
this.grid.focus.focusArea("pagination"+this.position,e);
}
if(this.focusArea!="pageSize"){
this.focusArea="pageSize";
}
this.plugin.changePageSize(_6f);
},_onPageStep:function(e){
var p=this.plugin,_70=this.pageStepValue=e.target.value;
if(!this._currentFocusNode){
this.grid.focus.focusArea("pagination"+this.position,e);
}
if(this.focusArea!="pageStep"){
this.focusArea="pageStep";
}
if(!isNaN(parseInt(_70,10))){
p.gotoPage(_70);
}else{
switch(e.target.value){
case "prevPage":
p.prevPage();
break;
case "nextPage":
p.nextPage();
break;
case "firstPage":
p.gotoFirstPage();
break;
case "lastPage":
p.gotoLastPage();
}
}
},_getCurrentPageNo:function(){
return this.plugin._currentPage+1;
},_getPageCount:function(){
if(!this._maxItemSize||!this.currentPageSize){
return 0;
}
return Math.ceil(this._maxItemSize/this.currentPageSize);
},_getStartPage:function(){
var cp=this._getCurrentPageNo();
var ms=parseInt(this.maxPageStep/2,10);
var pc=this._getPageCount();
if(cp<ms||(cp-ms)<1){
return 1;
}else{
if(pc<=this.maxPageStep){
return 1;
}else{
if(pc-cp<ms&&cp-this.maxPageStep>=0){
return pc-this.maxPageStep+1;
}else{
return (cp-ms);
}
}
}
},_getStepPageSize:function(){
var sp=this._getStartPage();
var _71=this._getPageCount();
if((sp+this.maxPageStep)>_71){
return _71-sp+1;
}else{
return this.maxPageStep;
}
}});
dojo.declare("dojox.grid.enhanced.plugins.pagination._GotoPageDialog",null,{pageCount:0,constructor:function(_72){
this.plugin=_72;
this.pageCount=this.plugin.paginators[0]._getPageCount();
this._dialogNode=dojo.create("div",{},dojo.body(),"last");
this._gotoPageDialog=new dojox.grid.enhanced.plugins.Dialog({"refNode":_72.grid.domNode,"title":this.plugin.nls.dialogTitle},this._dialogNode);
this._createDialogContent();
this._gotoPageDialog.startup();
},_createDialogContent:function(){
this._specifyNode=dojo.create("div",{innerHTML:this.plugin.nls.dialogIndication},this._gotoPageDialog.containerNode,"last");
this._pageInputDiv=dojo.create("div",{},this._gotoPageDialog.containerNode,"last");
this._pageTextBox=new dijit.form.NumberTextBox();
this._pageTextBox.constraints={fractional:false,min:1,max:this.pageCount};
this.plugin.connect(this._pageTextBox.textbox,"onkeyup",dojo.hitch(this,"_setConfirmBtnState"));
this._pageInputDiv.appendChild(this._pageTextBox.domNode);
this._pageLabel=dojo.create("label",{innerHTML:dojo.string.substitute(this.plugin.nls.pageCountIndication,[this.pageCount])},this._pageInputDiv,"last");
this._buttonDiv=dojo.create("div",{},this._gotoPageDialog.containerNode,"last");
this._confirmBtn=new dijit.form.Button({label:this.plugin.nls.dialogConfirm,onClick:dojo.hitch(this,this._onConfirm)});
this._confirmBtn.set("disabled",true);
this._cancelBtn=new dijit.form.Button({label:this.plugin.nls.dialogCancel,onClick:dojo.hitch(this,this._onCancel)});
this._buttonDiv.appendChild(this._confirmBtn.domNode);
this._buttonDiv.appendChild(this._cancelBtn.domNode);
this._styleContent();
this._gotoPageDialog.onCancel=dojo.hitch(this,this._onCancel);
this.plugin.connect(this._gotoPageDialog,"_onKey",dojo.hitch(this,"_onKeyDown"));
},_styleContent:function(){
dojo.addClass(this._specifyNode,"dojoxGridDialogMargin");
dojo.addClass(this._pageInputDiv,"dojoxGridDialogMargin");
dojo.addClass(this._buttonDiv,"dojoxGridDialogButton");
dojo.style(this._pageTextBox.domNode,"width","50px");
},updatePageCount:function(){
this.pageCount=this.plugin.paginators[0]._getPageCount();
this._pageTextBox.constraints={fractional:false,min:1,max:this.pageCount};
dojo.attr(this._pageLabel,"innerHTML",dojo.string.substitute(this.plugin.nls.pageCountIndication,[this.pageCount]));
},showDialog:function(){
this._gotoPageDialog.show();
},_onConfirm:function(_73){
if(this._pageTextBox.isValid()&&this._pageTextBox.getDisplayedValue()!==""){
this.plugin.gotoPage(this._pageTextBox.getDisplayedValue());
this._gotoPageDialog.hide();
this._pageTextBox.reset();
}
dojo.stopEvent(_73);
},_onCancel:function(_74){
this._pageTextBox.reset();
this._gotoPageDialog.hide();
dojo.stopEvent(_74);
},_onKeyDown:function(_75){
if(_75.altKey||_75.metaKey){
return;
}
var dk=dojo.keys;
if(_75.keyCode===dk.ENTER){
this._onConfirm(_75);
}
},_setConfirmBtnState:function(){
if(this._pageTextBox.isValid()&&this._pageTextBox.getDisplayedValue()!==""){
this._confirmBtn.set("disabled",false);
}else{
this._confirmBtn.set("disabled",true);
}
},destroy:function(){
this._pageTextBox.destroy();
this._confirmBtn.destroy();
this._cancelBtn.destroy();
this._gotoPageDialog.destroy();
dojo.destroy(this._specifyNode);
dojo.destroy(this._pageInputDiv);
dojo.destroy(this._pageLabel);
dojo.destroy(this._buttonDiv);
dojo.destroy(this._dialogNode);
}});
dojox.grid.EnhancedGrid.registerPlugin(dojox.grid.enhanced.plugins.Pagination);
}
