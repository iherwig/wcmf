/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.grid.enhanced.plugins.NestedSorting"]){
dojo._hasResource["dojox.grid.enhanced.plugins.NestedSorting"]=true;
dojo.provide("dojox.grid.enhanced.plugins.NestedSorting");
dojo.require("dojox.grid.enhanced._Plugin");
dojo.declare("dojox.grid.enhanced.plugins.NestedSorting",dojox.grid.enhanced._Plugin,{name:"nestedSorting",constructor:function(){
this._sortDef=[];
this._sortData={};
this._headerNodes={};
this._excludedColIdx=[];
this.grid.setSortIndex=dojo.hitch(this,"_setGridSortIndex");
this.grid.getSortProps=dojo.hitch(this,"getSortProps");
if(this.grid.sortFields){
this._setGridSortIndex(this.grid.sortFields,null,true);
}
this.connect(this.grid.views,"render","_initSort");
this.nls=this.grid._nls;
this.initCookieHandler();
dojo.addClass(this.grid.domNode,"dojoxGridWithNestedSorting");
},onStartUp:function(){
this.inherited(arguments);
this.connect(this.grid,"onHeaderCellClick","_onHeaderCellClick");
this.connect(this.grid,"onHeaderCellMouseOver","_onHeaderCellMouseOver");
this.connect(this.grid,"onHeaderCellMouseOut","_onHeaderCellMouseOut");
},_setGridSortIndex:function(_1,_2,_3){
if(typeof (_1)=="number"){
if(_2===undefined){
return;
}
this.setSortData(_1,"order",_2?"asc":"desc");
}else{
if(typeof (_1)=="object"){
for(var i=0;i<_1.length;i++){
var d=_1[i];
var _4=this.grid.getCellByField(d.attribute);
if(!_4){
console.warn("Cell not found for sorting: ",d.attribute);
continue;
}
this.setSortData(_4.index,"index",i);
this.setSortData(_4.index,"order",d.descending?"desc":"asc");
}
}else{
return;
}
}
this._updateSortDef();
if(!_3){
this.grid._refresh();
}
},getSortProps:function(){
return this._sortDef;
},_initSort:function(){
dojo.toggleClass(this.grid.domNode,"dojoxGridSorted",!!this._sortDef.length);
dojo.toggleClass(this.grid.domNode,"dojoxGridSingleSorted",this._sortDef.length==1);
dojo.toggleClass(this.grid.domNode,"dojoxGridNestSorted",this._sortDef.length>1);
var _5,g=this.grid,_6=this._excludedColIdx=[];
this._headerNodes=dojo.query("th",g.viewsHeaderNode).forEach(function(n){
_5=parseInt(dojo.attr(n,"idx"),10);
if(dojo.style(n,"display")==="none"||g.layout.cells[_5]["noSort"]){
_6.push(_5);
}
});
this._headerNodes.forEach(this._initHeaderNode,this);
this._focusRegions=[];
},_initHeaderNode:function(_7){
dojo.create("a",{className:"dojoxGridSortBtn dojoxGridSortBtnSingle",href:"javascript:void(0);",onmousedown:dojo.stopEvent,title:this.nls.singleSort},_7.firstChild,"last");
dojo.create("a",{className:"dojoxGridSortBtn dojoxGridSortBtnNested",href:"javascript:void(0);",onmousedown:dojo.stopEvent,title:this.nls.nestedSort,innerHTML:"1"},_7.firstChild,"last");
this._updateHeaderNodeUI(_7);
},_onHeaderCellClick:function(e){
if(dojo.hasClass(e.target,"dojoxGridSortBtn")){
this._onSortBtnClick(e);
dojo.stopEvent(e);
}
},_onHeaderCellMouseOver:function(e){
if(this._sortDef.length>1){
return;
}
if(this._sortData[e.cellIndex]&&this._sortData[e.cellIndex].index===0){
return;
}
for(var p in this._sortData){
if(this._sortData[p].index===0){
dojo.addClass(this._headerNodes[p],"dojoxGridHeaderNodeShowIndex");
break;
}
}
},_onHeaderCellMouseOut:function(e){
for(var p in this._sortData){
if(this._sortData[p].index===0){
dojo.removeClass(this._headerNodes[p],"dojoxGridHeaderNodeShowIndex");
break;
}
}
},_onSortBtnClick:function(e){
var _8=e.cell.index;
if(dojo.hasClass(e.target,"dojoxGridSortBtnSingle")){
this._prepareSingleSort(_8);
this._currRegionClass="dojoxGridSortBtnSingle";
}else{
if(dojo.hasClass(e.target,"dojoxGridSortBtnNested")){
this._prepareNestedSort(_8);
this._currRegionClass="dojoxGridSortBtnNested";
}else{
return;
}
}
dojo.stopEvent(e);
this._doSort(_8);
},_doSort:function(_9){
if(!this._sortData[_9]||!this._sortData[_9].order){
this.setSortData(_9,"order","asc");
}else{
if(this.isAsc(_9)){
this.setSortData(_9,"order","desc");
}else{
if(this.isDesc(_9)){
this.removeSortData(_9);
}
}
}
this._updateSortDef();
this.grid._refresh();
},setSortData:function(_a,_b,_c){
var sd=this._sortData[_a];
if(!sd){
sd=this._sortData[_a]={};
}
sd[_b]=_c;
},removeSortData:function(_d){
var d=this._sortData,i=d[_d].index;
delete d[_d];
for(var p in d){
if(d[p].index>i){
d[p].index--;
}
}
},_prepareSingleSort:function(_e){
var d=this._sortData;
for(var p in d){
if(p!=_e||dojo.hasClass(this.grid.domNode,"dojoxGridNestSorted")){
delete d[p];
}
}
this.setSortData(_e,"index",0);
},_prepareNestedSort:function(_f){
var i=this._sortData[_f]?this._sortData[_f].index:null;
if(i===0||!!i){
return;
}
this.setSortData(_f,"index",this._sortDef.length);
},_updateSortDef:function(){
this._sortDef.length=0;
var d=this._sortData;
for(var p in d){
this._sortDef[d[p].index]={attribute:this.grid.layout.cells[p].field,descending:d[p].order=="desc"};
}
},_updateHeaderNodeUI:function(_10){
var _11=this._getCellByNode(_10);
var _12=_11.index;
var _13=this._sortData[_12];
var _14=dojo.query(".dojoxGridSortNode",_10)[0];
var _15=dojo.query(".dojoxGridSortBtnSingle",_10)[0];
var _16=dojo.query(".dojoxGridSortBtnNested",_10)[0];
function _17(){
var _18="Column "+(_11.index+1)+" "+_11.field;
var _19="none";
var _1a="ascending";
if(_13){
_19=_13.order=="asc"?"ascending":"descending";
_1a=_13.order=="asc"?"descending":"none";
}
var _1b=_18+" - is sorted by "+_19;
var _1c=_18+" - is nested sorted by "+_19;
var _1d=_18+" - choose to sort by "+_1a;
var _1e=_18+" - choose to nested sort by "+_1a;
dijit.setWaiState(_15,"label",_1b);
dijit.setWaiState(_16,"label",_1c);
_15.onmouseover=function(){
dijit.setWaiState(this,"label",_1d);
};
_15.onmouseout=function(){
dijit.setWaiState(this,"label",_1b);
};
_16.onmouseover=function(){
dijit.setWaiState(this,"label",_1e);
};
_16.onmouseout=function(){
dijit.setWaiState(this,"label",_1c);
};
};
_17();
if(!_13){
_16.innerHTML=this._sortDef.length+1;
return;
}
if(_13.index||(_13.index===0&&this._sortDef.length>1)){
_16.innerHTML=_13.index+1;
}
dojo.addClass(_14,"dojoxGridSortNodeSorted");
if(this.isAsc(_12)){
dojo.addClass(_14,"dojoxGridSortNodeAsc");
}else{
if(this.isDesc(_12)){
dojo.addClass(_14,"dojoxGridSortNodeDesc");
}
}
dojo.addClass(_14,(_13.index===0?"dojoxGridSortNodeMain":"dojoxGridSortNodeSub"));
},isAsc:function(_1f){
return this._sortData[_1f].order=="asc";
},isDesc:function(_20){
return this._sortData[_20].order=="desc";
},_getCellByNode:function(_21){
for(var i=0;i<this._headerNodes.length;i++){
if(this._headerNodes[i]==_21){
return this.grid.layout.cells[i];
}
}
return null;
},clearSort:function(){
this._sortData={};
this._sortDef.length=0;
},initCookieHandler:function(){
if(this.grid.addCookieHandler){
this.grid.addCookieHandler({name:"sortOrder",onLoad:dojo.hitch(this,"_loadNestedSortingProps"),onSave:dojo.hitch(this,"_saveNestedSortingProps")});
}
},_loadNestedSortingProps:function(_22,_23){
this._setGridSortIndex(_22);
},_saveNestedSortingProps:function(_24){
return this.getSortProps();
},_initFocus:function(){
var f=this.focus=this.grid.focus;
this._focusRegions=[];
this._currRegion=null;
this._currRegionClass=null;
var _25=f.getArea("header");
_25.onFocus=f.focusHeader=dojo.hitch(this,"_focusHeader");
_25.onBlur=f.blurHeader=f._blurHeader=dojo.hitch(this,"_blurHeader");
_25.onMove=dojo.hitch(this,"_onMove");
_25.onKeyDown=dojo.hitch(this,"_onKeyDown");
_25.getRegions=dojo.hitch(this,"_getRegions");
_25.onRegionFocus=dojo.hitch(this,"_onRegionFocus");
_25.onRegionBlur=dojo.hitch(this,"_onRegionBlur");
},_focusHeader:function(evt){
var f=this.focus;
f.currentArea("header");
if(f._isHeaderHidden()){
f.findAndFocusGridCell();
return true;
}
var _26=this._validRegion(this._currRegion)?this._currRegion:undefined;
if(!_26){
var _27=f._colHeadFocusIdx;
if(!_27){
_27=f.isNoFocusCell()?0:f.cell.index;
}
while(_27>=0&&_27<this._headerNodes.length&&dojo.indexOf(this._excludedColIdx,_27)>=0){
f._colHeadFocusIdx=++_27;
}
f._colHeadNode=this._headerNodes[_27];
if(f._colHeadNode){
var cls=this._currRegionClass;
if(!cls){
cls=this._singleSortTip(_27)?"dojoxGridSortBtnSingle":"dojoxGridSortBtnNested";
}
_26=dojo.query("."+cls,f._colHeadNode)[0];
}
}
if(_26&&f._colHeadNode){
dojo.addClass(f._colHeadNode,"dojoxGridCellSortFocus");
dojo.addClass(_26,f.focusClass);
f._focusifyCellNode(false);
dijit.focus(_26);
return true;
}
f.findAndFocusGridCell();
return false;
},_blurHeader:function(evt){
dojo.removeAttr(this.grid.domNode,"aria-activedescendant");
return true;
},_onMove:function(_28,_29,evt){
if(!_29){
return;
}
var _2a=this._currRegion;
if(this._singleSortTip(dojo.attr(_2a,"colIdx"))&&(dojo.hasClass(_2a,"dojoxGridSortNode")&&_29>0||dojo.hasClass(_2a,"dojoxGridSortBtnSingle")&&_29<0)){
_29*=2;
}
var _2b=this._focusRegions;
var _2c=dojo.indexOf(_2b,_2a)+_29;
if(_2c>=0&&_2c<_2b.length){
var _2d=_2b[_2c];
if(_2d){
dojo.addClass(_2d,this.focus.focusClass);
dijit.focus(_2d);
var _2e=this._headerNodes[dojo.attr(_2d,"colIdx")];
if(_2e){
dojo.addClass(_2e,"dojoxGridCellSortFocus");
}
}
}
},_onKeyDown:function(e,_2f){
if(_2f){
switch(e.keyCode){
case dojo.keys.ENTER:
case dojo.keys.SPACE:
if(dojo.hasClass(e.target,"dojoxGridSortBtnSingle")||dojo.hasClass(e.target,"dojoxGridSortBtnNested")){
this._onSortBtnClick(e);
}
}
}
},_getRegions:function(){
if(this._focusRegions.length<=0){
var _30=this._focusRegions=[],_31=this._excludedColIdx;
this._headerNodes.filter(function(n,i){
return dojo.indexOf(_31,i)<0;
}).forEach(function(n,i){
var idx=dojo.attr(n,"idx");
var _32=dojo.query(".dojoxGridSortNode",n)[0];
var _33=dojo.query(".dojoxGridSortBtnNested",n)[0];
var _34=dojo.query(".dojoxGridSortBtnSingle",n)[0];
if(_32&&_33&&_34){
dojo.attr(_32,"tabindex",0);
_30.push(dojo.attr(_32,"colIdx",idx));
_30.push(dojo.attr(_33,"colIdx",idx));
_30.push(dojo.attr(_34,"colIdx",idx));
}
});
}
return this._focusRegions;
},_onRegionFocus:function(evt){
var _35=evt.target;
if(!_35){
return;
}
var f=this.focus,_36=dojo.attr(_35,"colIdx");
var _37=this._headerNodes[_36];
if(_37&&_36!==f._colHeadFocusIdx){
f.currentArea("header");
f._colHeadNode=_37;
f._colHeadFocusIdx=_36;
f._scrollHeader(_36);
dojo.addClass(f._colHeadNode,"dojoxGridCellSortFocus");
dojo.addClass(_35,f.focusClass);
dojo.attr(this.grid.domNode,"aria-activedescendant",dojo.attr(_37,"id"));
}
this._currRegion=_35;
},_onRegionBlur:function(evt){
var _38=evt.target,f=this.focus;
if(_38){
dojo.removeClass(_38,f.focusClass);
}
if(f._colHeadNode){
dojo.removeClass(f._colHeadNode,"dojoxGridCellSortFocus");
}
},_validRegion:function(_39){
return dojo.indexOf(this._focusRegions,_39)>=0;
},_singleSortTip:function(_3a){
var def=this._sortDef,_3b=this._sortData[_3a];
return (def.length===0||def.length==1&&_3b&&_3b.index===0);
},destroy:function(){
this._sortDef=this._sortData=null;
this._headerNodes=this._focusRegions=null;
this.inherited(arguments);
}});
dojox.grid.EnhancedGrid.registerPlugin(dojox.grid.enhanced.plugins.NestedSorting);
}
