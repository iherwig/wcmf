/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.grid.enhanced.plugins.IndirectSelection"]){
dojo._hasResource["dojox.grid.enhanced.plugins.IndirectSelection"]=true;
dojo.provide("dojox.grid.enhanced.plugins.IndirectSelection");
dojo.require("dojo.string");
dojo.require("dojox.grid.cells.dijit");
dojo.require("dojox.grid.enhanced._Plugin");
dojo.declare("dojox.grid.enhanced.plugins.IndirectSelection",dojox.grid.enhanced._Plugin,{name:"indirectSelection",constructor:function(){
var _1=this.grid.layout;
this.connect(_1,"setStructure",dojo.hitch(_1,this.addRowSelectCell,this.option));
},addRowSelectCell:function(_2){
if(!this.grid.indirectSelection||this.grid.selectionMode=="none"){
return;
}
var _3=false,_4=["get","formatter","field","fields"],_5={type:dojox.grid.cells.MultipleRowSelector,name:"",width:"30px",styles:"text-align: center;"};
if(_2.headerSelector){
_2.name="";
}
if(this.grid.rowSelectCell){
this.grid.rowSelectCell.destroy();
}
dojo.forEach(this.structure,function(_6){
var _7=_6.cells;
if(_7&&_7.length>0&&!_3){
var _8=_7[0];
if(_8[0]&&_8[0].isRowSelector){
_3=true;
return;
}
var _9,_a=this.grid.selectionMode=="single"?dojox.grid.cells.SingleRowSelector:dojox.grid.cells.MultipleRowSelector;
_9=dojo.mixin(_5,_2,{type:_a,editable:false,notselectable:true,filterable:false,navigatable:true,noSort:true});
dojo.forEach(_4,function(_b){
if(_b in _9){
delete _9[_b];
}
});
if(_7.length>1){
_9.rowSpan=_7.length;
}
dojo.forEach(this.cells,function(_c,i){
if(_c.index>=0){
_c.index+=1;
}else{
}
});
var _d=this.addCellDef(0,0,_9);
_d.index=0;
_8.unshift(_d);
this.cells.unshift(_d);
this.grid.rowSelectCell=_d;
_3=true;
}
},this);
this.cellCount=this.cells.length;
},destroy:function(){
this.grid.rowSelectCell.destroy();
delete this.grid.rowSelectCell;
this.inherited(arguments);
}});
dojo.declare("dojox.grid.cells.RowSelector",dojox.grid.cells._Widget,{inputType:"",map:null,disabledMap:null,isRowSelector:true,_connects:null,_subscribes:null,checkedText:"&#8730;",unCheckedText:"O",constructor:function(){
this.map={};
this.disabledMap={};
this._connects=[];
this._subscribes=[];
this.inA11YMode=dojo.hasClass(dojo.body(),"dijit_a11y");
this.baseClass="dojoxGridRowSelector dijitReset dijitInline dijit"+this.inputType;
this.checkedClass=" dijit"+this.inputType+"Checked";
this.disabledClass=" dijit"+this.inputType+"Disabled";
this.checkedDisabledClass=" dijit"+this.inputType+"CheckedDisabled";
this.statusTextClass=" dojoxGridRowSelectorStatusText";
this._connects.push(dojo.connect(this.grid,"dokeyup",this,"_dokeyup"));
this._connects.push(dojo.connect(this.grid.selection,"onSelected",this,"_onSelected"));
this._connects.push(dojo.connect(this.grid.selection,"onDeselected",this,"_onDeselected"));
this._connects.push(dojo.connect(this.grid.scroller,"invalidatePageNode",this,"_pageDestroyed"));
this._connects.push(dojo.connect(this.grid,"onCellClick",this,"_onClick"));
this._connects.push(dojo.connect(this.grid,"updateRow",this,"_onUpdateRow"));
},formatter:function(_e,_f){
var _10=this.baseClass;
var _11=this.getValue(_f);
var _12=!!this.disabledMap[_f];
if(_11){
_10+=this.checkedClass;
if(_12){
_10+=this.checkedDisabledClass;
}
}else{
if(_12){
_10+=this.disabledClass;
}
}
return ["<div tabindex = -1 ","id = '"+this.grid.id+"_rowSelector_"+_f+"' ","name = '"+this.grid.id+"_rowSelector' class = '"+_10+"' ","role = 'presentation' aria-pressed = '"+_11+"' aria-disabled = '"+_12+"' aria-label = '"+dojo.string.substitute(this.grid._nls["indirectSelection"+this.inputType],[_f+1])+"'>","<span class = '"+this.statusTextClass+"'>"+(_11?this.checkedText:this.unCheckedText)+"</span>","</div>"].join("");
},setValue:function(_13,_14){
},getValue:function(_15){
return this.grid.selection.isSelected(_15);
},toggleRow:function(_16,_17){
this._nativeSelect(_16,_17);
},setDisabled:function(_18,_19){
this._toggleDisabledStyle(_18,_19);
},_onClick:function(e){
if(e.cell===this){
this._selectRow(e);
}
},_dokeyup:function(e){
if(e.cellIndex==this.index&&e.rowIndex>=0&&e.keyCode==dojo.keys.SPACE){
this._selectRow(e);
}
},focus:function(_1a){
var _1b=this.map[_1a];
if(_1b){
_1b.focus();
}
},_focusEndingCell:function(_1c,_1d){
var _1e=this.grid.getCell(_1d);
this.grid.focus.setFocusCell(_1e,_1c);
},_nativeSelect:function(_1f,_20){
this.grid.selection[_20?"select":"deselect"](_1f);
},_onSelected:function(_21){
this._toggleCheckedStyle(_21,true);
},_onDeselected:function(_22){
this._toggleCheckedStyle(_22,false);
},_onUpdateRow:function(_23){
delete this.map[_23];
},_toggleCheckedStyle:function(_24,_25){
var _26=this._getSelector(_24);
if(_26){
dojo.toggleClass(_26,this.checkedClass,_25);
if(this.disabledMap[_24]){
dojo.toggleClass(_26,this.checkedDisabledClass,_25);
}
dijit.setWaiState(_26,"pressed",_25);
if(this.inA11YMode){
dojo.attr(_26.firstChild,"innerHTML",_25?this.checkedText:this.unCheckedText);
}
}
},_toggleDisabledStyle:function(_27,_28){
var _29=this._getSelector(_27);
if(_29){
dojo.toggleClass(_29,this.disabledClass,_28);
if(this.getValue(_27)){
dojo.toggleClass(_29,this.checkedDisabledClass,_28);
}
dijit.setWaiState(_29,"disabled",_28);
}
this.disabledMap[_27]=_28;
},_getSelector:function(_2a){
var _2b=this.map[_2a];
if(!_2b){
var _2c=this.view.rowNodes[_2a];
if(_2c){
_2b=dojo.query(".dojoxGridRowSelector",_2c)[0];
if(_2b){
this.map[_2a]=_2b;
}
}
}
return _2b;
},_pageDestroyed:function(_2d){
var _2e=this.grid.scroller.rowsPerPage;
var _2f=_2d*_2e,end=_2f+_2e-1;
for(var i=_2f;i<=end;i++){
dojo.destroy(this.map[i]);
delete this.map[i];
}
},destroy:function(){
for(var i in this.map){
dojo.destroy(this.map[i]);
delete this.map[i];
}
for(i in this.disabledMap){
delete this.disabledMap[i];
}
dojo.forEach(this._connects,dojo.disconnect);
dojo.forEach(this._subscribes,dojo.unsubscribe);
delete this._connects;
delete this._subscribes;
}});
dojo.declare("dojox.grid.cells.SingleRowSelector",dojox.grid.cells.RowSelector,{inputType:"Radio",_selectRow:function(e){
var _30=e.rowIndex;
if(this.disabledMap[_30]){
return;
}
this._focusEndingCell(_30,0);
this._nativeSelect(_30,!this.grid.selection.selected[_30]);
}});
dojo.declare("dojox.grid.cells.MultipleRowSelector",dojox.grid.cells.RowSelector,{inputType:"CheckBox",swipeStartRowIndex:-1,swipeMinRowIndex:-1,swipeMaxRowIndex:-1,toSelect:false,lastClickRowIdx:-1,toggleAllTrigerred:false,unCheckedText:"&#9633;",constructor:function(){
this._connects.push(dojo.connect(dojo.doc,"onmouseup",this,"_domouseup"));
this._connects.push(dojo.connect(this.grid,"onRowMouseOver",this,"_onRowMouseOver"));
this._connects.push(dojo.connect(this.grid.focus,"move",this,"_swipeByKey"));
this._connects.push(dojo.connect(this.grid,"onCellMouseDown",this,"_onMouseDown"));
if(this.headerSelector){
this._connects.push(dojo.connect(this.grid.views,"render",this,"_addHeaderSelector"));
this._connects.push(dojo.connect(this.grid,"onSelectionChanged",this,"_onSelectionChanged"));
this._connects.push(dojo.connect(this.grid,"onKeyDown",this,function(e){
if(e.rowIndex==-1&&e.cellIndex==this.index&&e.keyCode==dojo.keys.SPACE){
this._toggletHeader();
}
}));
}
},toggleAllSelection:function(_31){
var _32=this.grid,_33=_32.selection;
if(_31){
_33.selectRange(0,_32.rowCount-1);
}else{
_33.deselectAll();
}
this.toggleAllTrigerred=true;
},_onMouseDown:function(e){
if(e.cell==this){
this._startSelection(e.rowIndex);
dojo.stopEvent(e);
}
},_onRowMouseOver:function(e){
this._updateSelection(e,0);
},_domouseup:function(e){
if(dojo.isIE){
this.view.content.decorateEvent(e);
}
var _34=e.cellIndex>=0&&this.inSwipeSelection()&&!this.grid.edit.isEditRow(e.rowIndex);
if(_34){
this._focusEndingCell(e.rowIndex,e.cellIndex);
}
this._finishSelect();
},_dokeyup:function(e){
this.inherited(arguments);
if(!e.shiftKey){
this._finishSelect();
}
},_startSelection:function(_35){
this.swipeStartRowIndex=this.swipeMinRowIndex=this.swipeMaxRowIndex=_35;
this.toSelect=!this.getValue(_35);
},_updateSelection:function(e,_36){
if(!this.inSwipeSelection()){
return;
}
var _37=_36!==0;
var _38=e.rowIndex,_39=_38-this.swipeStartRowIndex+_36;
if(_39>0&&this.swipeMaxRowIndex<_38+_36){
this.swipeMaxRowIndex=_38+_36;
}
if(_39<0&&this.swipeMinRowIndex>_38+_36){
this.swipeMinRowIndex=_38+_36;
}
var min=_39>0?this.swipeStartRowIndex:_38+_36;
var max=_39>0?_38+_36:this.swipeStartRowIndex;
for(var i=this.swipeMinRowIndex;i<=this.swipeMaxRowIndex;i++){
if(this.disabledMap[i]||i<0){
continue;
}
if(i>=min&&i<=max){
this._nativeSelect(i,this.toSelect);
}else{
if(!_37){
this._nativeSelect(i,!this.toSelect);
}
}
}
},_swipeByKey:function(_3a,_3b,e){
if(!e||_3a===0||!e.shiftKey||e.cellIndex!=this.index||this.grid.focus.rowIndex<0){
return;
}
var _3c=e.rowIndex;
if(this.swipeStartRowIndex<0){
this.swipeStartRowIndex=_3c;
if(_3a>0){
this.swipeMaxRowIndex=_3c+_3a;
this.swipeMinRowIndex=_3c;
}else{
this.swipeMinRowIndex=_3c+_3a;
this.swipeMaxRowIndex=_3c;
}
this.toSelect=this.getValue(_3c);
}
this._updateSelection(e,_3a);
},_finishSelect:function(){
this.swipeStartRowIndex=-1;
this.swipeMinRowIndex=-1;
this.swipeMaxRowIndex=-1;
this.toSelect=false;
},inSwipeSelection:function(){
return this.swipeStartRowIndex>=0;
},_nativeSelect:function(_3d,_3e){
this.grid.selection[_3e?"addToSelection":"deselect"](_3d);
},_selectRow:function(e){
var _3f=e.rowIndex;
if(this.disabledMap[_3f]){
return;
}
dojo.stopEvent(e);
this._focusEndingCell(_3f,0);
var _40=_3f-this.lastClickRowIdx;
var _41=!this.grid.selection.selected[_3f];
if(this.lastClickRowIdx>=0&&!e.ctrlKey&&!e.altKey&&e.shiftKey){
var min=_40>0?this.lastClickRowIdx:_3f;
var max=_40>0?_3f:this.lastClickRowIdx;
for(var i=min;i>=0&&i<=max;i++){
this._nativeSelect(i,_41);
}
}else{
this._nativeSelect(_3f,_41);
}
this.lastClickRowIdx=_3f;
},_addHeaderSelector:function(){
var _42=this.view.getHeaderCellNode(this.index);
if(!_42){
return;
}
dojo.empty(_42);
var g=this.grid;
var _43=_42.appendChild(dojo.create("div",{"tabindex":-1,"id":g.id+"_rowSelector_-1","class":this.baseClass,"role":"presentation","innerHTML":"<span class = '"+this.statusTextClass+"'></span><span style='height: 0; width: 0; overflow: hidden; display: block;'>"+g._nls["selectAll"]+"</span>"}));
this.map[-1]=_43;
var idx=this._headerSelectorConnectIdx;
if(idx!==undefined){
dojo.disconnect(this._connects[idx]);
this._connects.splice(idx,1);
}
this._headerSelectorConnectIdx=this._connects.length;
this._connects.push(dojo.connect(_43,"onclick",this,"_toggletHeader"));
this._onSelectionChanged();
},_toggletHeader:function(){
this.grid._selectingRange=true;
var _44=this.grid.rowCount>0&&this.grid.rowCount<=this.grid.selection.getSelectedCount();
this.toggleAllSelection(!_44);
this._onSelectionChanged();
this.grid._selectingRange=false;
},_onSelectionChanged:function(){
if(!this.map[-1]||this.grid._selectingRange){
return;
}
var _45=this.grid,_46=this.map[-1];
var _47=_45.rowCount>0&&_45.rowCount<=_45.selection.getSelectedCount();
dojo.toggleClass(_46,this.checkedClass,_47);
dijit.setWaiState(_46,"pressed",_47);
if(this.inA11YMode){
dojo.attr(_46.firstChild,"innerHTML",_47?this.checkedText:this.unCheckedText);
}
}});
dojox.grid.EnhancedGrid.registerPlugin(dojox.grid.enhanced.plugins.IndirectSelection,{"preInit":true});
}
