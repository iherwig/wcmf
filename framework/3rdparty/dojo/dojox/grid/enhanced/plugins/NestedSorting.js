/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.grid.enhanced.plugins.NestedSorting"]){
dojo._hasResource["dojox.grid.enhanced.plugins.NestedSorting"]=true;
dojo.provide("dojox.grid.enhanced.plugins.NestedSorting");
dojo.require("dojox.grid.enhanced._Plugin");
dojo.declare("dojox.grid.enhanced.plugins.NestedSorting",dojox.grid.enhanced._Plugin,{name:"nestedSorting",_currMainSort:"none",_currRegionIdx:-1,_a11yText:{"dojoxGridDescending":"&#9662;","dojoxGridAscending":"&#9652;","dojoxGridAscendingTip":"&#1784;","dojoxGridDescendingTip":"&#1783;","dojoxGridUnsortedTip":"x"},constructor:function(){
this._sortDef=[];
this._sortData={};
this._headerNodes={};
this._excludedColIdx=[];
this.nls=this.grid._nls;
this.grid.setSortInfo=function(){
};
this.grid.setSortIndex=dojo.hitch(this,"_setGridSortIndex");
this.grid.getSortProps=dojo.hitch(this,"getSortProps");
if(this.grid.sortFields){
this._setGridSortIndex(this.grid.sortFields,null,true);
}
this.connect(this.grid.views,"render","_initSort");
this.initCookieHandler();
},onStartUp:function(){
this.inherited(arguments);
this.connect(this.grid,"onHeaderCellClick","_onHeaderCellClick");
this.connect(this.grid,"onHeaderCellMouseOver","_onHeaderCellMouseOver");
this.connect(this.grid,"onHeaderCellMouseOut","_onHeaderCellMouseOut");
},_setGridSortIndex:function(_1,_2,_3){
if(!isNaN(_1)){
if(_2===undefined){
return;
}
this.setSortData(_1,"order",_2?"asc":"desc");
}else{
if(dojo.isArray(_1)){
var i,d,_4;
this.clearSort();
for(i=0;i<_1.length;i++){
d=_1[i];
_4=this.grid.getCellByField(d.attribute);
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
this.grid.sort();
}
},getSortProps:function(){
return this._sortDef.length?this._sortDef:null;
},_initSort:function(_5){
var g=this.grid,n=g.domNode,_6=this._sortDef.length;
dojo.toggleClass(n,"dojoxGridSorted",!!_6);
dojo.toggleClass(n,"dojoxGridSingleSorted",_6===1);
dojo.toggleClass(n,"dojoxGridNestSorted",_6>1);
if(_6>0){
this._currMainSort=this._sortDef[0].descending?"desc":"asc";
}
var _7,_8=this._excludedCoIdx=[];
this._headerNodes=dojo.query("th",g.viewsHeaderNode).forEach(function(n){
_7=parseInt(dojo.attr(n,"idx"),10);
if(dojo.style(n,"display")==="none"||g.layout.cells[_7]["noSort"]||(g.canSort&&!g.canSort(_7,g.layout.cells[_7]["field"]))){
_8.push(_7);
}
});
this._headerNodes.forEach(this._initHeaderNode,this);
this._initFocus();
if(_5){
this._focusHeader();
}
},_initHeaderNode:function(_9){
if(dojo.indexOf(this._excludedCoIdx,dojo.attr(_9,"idx"))>=0){
dojo.addClass(_9,"dojoxGridNoSort");
return;
}
this._connects=dojo.filter(this._connects,function(_a){
if(_a._sort){
dojo.disconnect(_a);
return false;
}
return true;
});
var n=dojo.create("a",{className:"dojoxGridSortBtn dojoxGridSortBtnNested",title:this.nls.nestedSort,innerHTML:"1"},_9.firstChild,"last");
var h=this.connect(n,"onmousedown",dojo.stopEvent);
h._sort=true;
n=dojo.create("a",{className:"dojoxGridSortBtn dojoxGridSortBtnSingle",title:this.nls.singleSort},_9.firstChild,"last");
h=this.connect(n,"onmousedown",dojo.stopEvent);
h._sort=true;
this._updateHeaderNodeUI(_9);
},_onHeaderCellClick:function(e){
this._focusRegion(e.target);
if(dojo.hasClass(e.target,"dojoxGridSortBtn")){
this._onSortBtnClick(e);
dojo.stopEvent(e);
this._focusRegion(this._getCurrentRegion());
}
},_onHeaderCellMouseOver:function(e){
if(!e.cell){
return;
}
if(this._sortDef.length>1){
return;
}
if(this._sortData[e.cellIndex]&&this._sortData[e.cellIndex].index===0){
return;
}
var p;
for(p in this._sortData){
if(this._sortData[p].index===0){
dojo.addClass(this._headerNodes[p],"dojoxGridCellShowIndex");
break;
}
}
if(!dojo.hasClass(dojo.body(),"dijit_a11y")){
return;
}
var i=e.cell.index,_b=e.cellNode;
var _c=dojo.query(".dojoxGridSortBtnSingle",_b)[0];
var _d=dojo.query(".dojoxGridSortBtnNested",_b)[0];
var _e="none";
if(dojo.hasClass(this.grid.domNode,"dojoxGridSingleSorted")){
_e="single";
}else{
if(dojo.hasClass(this.grid.domNode,"dojoxGridNestSorted")){
_e="nested";
}
}
var _f=dojo.attr(_d,"orderIndex");
if(_f===null||_f===undefined){
dojo.attr(_d,"orderIndex",_d.innerHTML);
_f=_d.innerHTML;
}
if(this.isAsc(i)){
_d.innerHTML=_f+this._a11yText.dojoxGridDescending;
}else{
if(this.isDesc(i)){
_d.innerHTML=_f+this._a11yText.dojoxGridUnsortedTip;
}else{
_d.innerHTML=_f+this._a11yText.dojoxGridAscending;
}
}
if(this._currMainSort==="none"){
_c.innerHTML=this._a11yText.dojoxGridAscending;
}else{
if(this._currMainSort==="asc"){
_c.innerHTML=this._a11yText.dojoxGridDescending;
}else{
if(this._currMainSort==="desc"){
_c.innerHTML=this._a11yText.dojoxGridUnsortedTip;
}
}
}
},_onHeaderCellMouseOut:function(e){
var p;
for(p in this._sortData){
if(this._sortData[p].index===0){
dojo.removeClass(this._headerNodes[p],"dojoxGridCellShowIndex");
break;
}
}
},_onSortBtnClick:function(e){
var _10=e.cell.index;
if(dojo.hasClass(e.target,"dojoxGridSortBtnSingle")){
this._prepareSingleSort(_10);
}else{
if(dojo.hasClass(e.target,"dojoxGridSortBtnNested")){
this._prepareNestedSort(_10);
}else{
return;
}
}
dojo.stopEvent(e);
this._doSort(_10);
},_doSort:function(_11){
if(!this._sortData[_11]||!this._sortData[_11].order){
this.setSortData(_11,"order","asc");
}else{
if(this.isAsc(_11)){
this.setSortData(_11,"order","desc");
}else{
if(this.isDesc(_11)){
this.removeSortData(_11);
}
}
}
this._updateSortDef();
this.grid.sort();
this._initSort(true);
},setSortData:function(_12,_13,_14){
var sd=this._sortData[_12];
if(!sd){
sd=this._sortData[_12]={};
}
sd[_13]=_14;
},removeSortData:function(_15){
var d=this._sortData,i=d[_15].index,p;
delete d[_15];
for(p in d){
if(d[p].index>i){
d[p].index--;
}
}
},_prepareSingleSort:function(_16){
var d=this._sortData,p;
for(p in d){
delete d[p];
}
this.setSortData(_16,"index",0);
this.setSortData(_16,"order",this._currMainSort==="none"?null:this._currMainSort);
if(!this._sortData[_16]||!this._sortData[_16].order){
this._currMainSort="asc";
}else{
if(this.isAsc(_16)){
this._currMainSort="desc";
}else{
if(this.isDesc(_16)){
this._currMainSort="none";
}
}
}
},_prepareNestedSort:function(_17){
var i=this._sortData[_17]?this._sortData[_17].index:null;
if(i===0||!!i){
return;
}
this.setSortData(_17,"index",this._sortDef.length);
},_updateSortDef:function(){
this._sortDef.length=0;
var d=this._sortData,p;
for(p in d){
this._sortDef[d[p].index]={attribute:this.grid.layout.cells[p].field,descending:d[p].order==="desc"};
}
},_updateHeaderNodeUI:function(_18){
var _19=this._getCellByNode(_18);
var _1a=_19.index;
var _1b=this._sortData[_1a];
var _1c=dojo.query(".dojoxGridSortNode",_18)[0];
var _1d=dojo.query(".dojoxGridSortBtnSingle",_18)[0];
var _1e=dojo.query(".dojoxGridSortBtnNested",_18)[0];
dojo.toggleClass(_1d,"dojoxGridSortBtnAsc",this._currMainSort==="asc");
dojo.toggleClass(_1d,"dojoxGridSortBtnDesc",this._currMainSort==="desc");
var _1f=this;
function _20(){
var _21="Column "+(_19.index+1)+" "+_19.field;
var _22="none";
var _23="ascending";
if(_1b){
_22=_1b.order==="asc"?"ascending":"descending";
_23=_1b.order==="asc"?"descending":"none";
}
var _24=_21+" - is sorted by "+_22;
var _25=_21+" - is nested sorted by "+_22;
var _26=_21+" - choose to sort by "+_23;
var _27=_21+" - choose to nested sort by "+_23;
dijit.setWaiState(_1d,"label",_24);
dijit.setWaiState(_1e,"label",_25);
var _28=[_1f.connect(_1d,"onmouseover",function(){
dijit.setWaiState(_1d,"label",_26);
}),_1f.connect(_1d,"onmouseout",function(){
dijit.setWaiState(_1d,"label",_24);
}),_1f.connect(_1e,"onmouseover",function(){
dijit.setWaiState(_1e,"label",_27);
}),_1f.connect(_1e,"onmouseout",function(){
dijit.setWaiState(_1e,"label",_25);
})];
dojo.forEach(_28,function(_29){
_29._sort=true;
});
};
_20();
var _2a=dojo.hasClass(dojo.body(),"dijit_a11y");
if(!_1b){
_1e.innerHTML=this._sortDef.length+1;
return;
}
if(_1b.index||(_1b.index===0&&this._sortDef.length>1)){
_1e.innerHTML=_1b.index+1;
}
dojo.addClass(_1c,"dojoxGridSortNodeSorted");
if(this.isAsc(_1a)){
dojo.addClass(_1c,"dojoxGridSortNodeAsc");
if(_2a){
_1c.innerHTML=this._a11yText.dojoxGridAscendingTip;
}
}else{
if(this.isDesc(_1a)){
dojo.addClass(_1c,"dojoxGridSortNodeDesc");
if(_2a){
_1c.innerHTML=this._a11yText.dojoxGridDescendingTip;
}
}
}
dojo.addClass(_1c,(_1b.index===0?"dojoxGridSortNodeMain":"dojoxGridSortNodeSub"));
},isAsc:function(_2b){
return this._sortData[_2b].order==="asc";
},isDesc:function(_2c){
return this._sortData[_2c].order==="desc";
},_getCellByNode:function(_2d){
var i;
for(i=0;i<this._headerNodes.length;i++){
if(this._headerNodes[i]===_2d){
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
},_loadNestedSortingProps:function(_2e,_2f){
this._setGridSortIndex(_2e);
},_saveNestedSortingProps:function(_30){
return this.getSortProps();
},_initFocus:function(){
var f=this.focus=this.grid.focus;
this._focusRegions=this._getRegions();
if(!this._headerArea){
var _31=this._headerArea=f.getArea("header");
_31.onFocus=f.focusHeader=dojo.hitch(this,"_focusHeader");
_31.onBlur=f.blurHeader=f._blurHeader=dojo.hitch(this,"_blurHeader");
_31.onMove=dojo.hitch(this,"_onMove");
_31.onKeyDown=dojo.hitch(this,"_onKeyDown");
_31._regions=[];
_31.getRegions=null;
}
},_focusHeader:function(evt){
if(this._currRegionIdx===-1){
this._onMove(0,1,null);
}else{
this._focusRegion(this._getCurrentRegion());
}
return true;
},_blurHeader:function(evt){
this._blurRegion(this._getCurrentRegion());
return true;
},_onMove:function(_32,_33,evt){
var _34=this._currRegionIdx||0,_35=this._focusRegions;
var _36=_35[_34+_33];
if(!_36){
return;
}else{
if(dojo.style(_36,"display")==="none"||dojo.style(_36,"visibility")==="hidden"){
this._onMove(_32,_33+(_33>0?1:-1),evt);
return;
}
}
this._focusRegion(_36);
var _37=this._getRegionView(_36);
_37.scrollboxNode.scrollLeft=_37.headerNode.scrollLeft;
},_onKeyDown:function(e,_38){
if(_38){
switch(e.keyCode){
case dojo.keys.ENTER:
case dojo.keys.SPACE:
if(dojo.hasClass(e.target,"dojoxGridSortBtnSingle")||dojo.hasClass(e.target,"dojoxGridSortBtnNested")){
this._onSortBtnClick(e);
}
}
}
},_getRegionView:function(_39){
var _3a=_39;
while(_3a&&!dojo.hasClass(_3a,"dojoxGridHeader")){
_3a=_3a.parentNode;
}
if(_3a){
return dojo.filter(this.grid.views.views,function(_3b){
return _3b.headerNode===_3a;
})[0]||null;
}
return null;
},_getRegions:function(){
var _3c=[],_3d=this.grid.layout.cells;
this._headerNodes.forEach(function(n,i){
if(dojo.style(n,"display")==="none"){
return;
}
if(_3d[i]["isRowSelector"]){
_3c.push(n);
return;
}
dojo.query(".dojoxGridSortNode,.dojoxGridSortBtnNested,.dojoxGridSortBtnSingle",n).forEach(function(_3e){
dojo.attr(_3e,"tabindex",0);
_3c.push(_3e);
});
},this);
return _3c;
},_focusRegion:function(_3f){
if(!_3f){
return;
}
var _40=this._getCurrentRegion();
if(_40&&_3f!==_40){
this._blurRegion(_40);
}
var _41=this._getRegionHeader(_3f);
dojo.addClass(_41,"dojoxGridCellSortFocus");
if(dojo.hasClass(_3f,"dojoxGridSortNode")){
dojo.addClass(_3f,"dojoxGridSortNodeFocus");
}else{
if(dojo.hasClass(_3f,"dojoxGridSortBtn")){
dojo.addClass(_3f,"dojoxGridSortBtnFocus");
}
}
_3f.focus();
this.focus.currentArea("header");
this._currRegionIdx=dojo.indexOf(this._focusRegions,_3f);
},_blurRegion:function(_42){
if(!_42){
return;
}
var _43=this._getRegionHeader(_42);
dojo.removeClass(_43,"dojoxGridCellSortFocus");
if(dojo.hasClass(_42,"dojoxGridSortNode")){
dojo.removeClass(_42,"dojoxGridSortNodeFocus");
}else{
if(dojo.hasClass(_42,"dojoxGridSortBtn")){
dojo.removeClass(_42,"dojoxGridSortBtnFocus");
}
}
_42.blur();
},_getCurrentRegion:function(){
return this._focusRegions[this._currRegionIdx];
},_getRegionHeader:function(_44){
while(_44&&!dojo.hasClass(_44,"dojoxGridCell")){
_44=_44.parentNode;
}
return _44;
},destroy:function(){
this._sortDef=this._sortData=null;
this._headerNodes=this._focusRegions=null;
this.inherited(arguments);
}});
dojox.grid.EnhancedGrid.registerPlugin(dojox.grid.enhanced.plugins.NestedSorting);
}
