/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dijit._HasDropDown"]){
dojo._hasResource["dijit._HasDropDown"]=true;
dojo.provide("dijit._HasDropDown");
dojo.require("dijit._base.place");
dojo.require("dijit._Widget");
dojo.declare("dijit._HasDropDown",null,{_buttonNode:null,_arrowWrapperNode:null,_popupStateNode:null,_aroundNode:null,dropDown:null,autoWidth:true,forceWidth:false,maxHeight:0,dropDownPosition:["below","above"],_stopClickEvents:true,_onDropDownMouseDown:function(e){
if(this.disabled||this.readOnly){
return;
}
this._docHandler=this.connect(dojo.doc,"onmouseup","_onDropDownMouseUp");
this.toggleDropDown();
},_onDropDownMouseUp:function(e){
if(e&&this._docHandler){
this.disconnect(this._docHandler);
}
var _1=this.dropDown,_2=false;
if(e&&this._opened){
var c=dojo.position(this._buttonNode,true);
if(!(e.pageX>=c.x&&e.pageX<=c.x+c.w)||!(e.pageY>=c.y&&e.pageY<=c.y+c.h)){
var t=e.target;
while(t&&!_2){
if(dojo.hasClass(t,"dijitPopup")){
_2=true;
}else{
t=t.parentNode;
}
}
if(_2){
t=e.target;
if(_1.onItemClick){
var _3;
while(t&&!(_3=dijit.byNode(t))){
t=t.parentNode;
}
if(_3&&_3.onClick&&_3.getParent){
_3.getParent().onItemClick(_3,e);
}
}
return;
}
}
}
if(this._opened&&_1.focus&&_1.autoFocus!==false){
window.setTimeout(dojo.hitch(_1,"focus"),1);
}
},_onDropDownClick:function(e){
if(this._stopClickEvents){
dojo.stopEvent(e);
}
},buildRendering:function(){
this.inherited(arguments);
this._buttonNode=this._buttonNode||this.focusNode||this.domNode;
this._popupStateNode=this._popupStateNode||this.focusNode||this._buttonNode;
var _4={"after":this.isLeftToRight()?"Right":"Left","before":this.isLeftToRight()?"Left":"Right","above":"Up","below":"Down","left":"Left","right":"Right"}[this.dropDownPosition[0]]||this.dropDownPosition[0]||"Down";
dojo.addClass(this._arrowWrapperNode||this._buttonNode,"dijit"+_4+"ArrowButton");
},postCreate:function(){
this.inherited(arguments);
this.connect(this._buttonNode,"onmousedown","_onDropDownMouseDown");
this.connect(this._buttonNode,"onclick","_onDropDownClick");
this.connect(this.focusNode,"onkeypress","_onKey");
},destroy:function(){
if(this.dropDown){
if(!this.dropDown._destroyed){
this.dropDown.destroyRecursive();
}
delete this.dropDown;
}
this.inherited(arguments);
},_onKey:function(e){
if(this.disabled||this.readOnly){
return;
}
var d=this.dropDown,_5=e.target;
if(d&&this._opened&&d.handleKey){
if(d.handleKey(e)===false){
dojo.stopEvent(e);
return;
}
}
if(d&&this._opened&&e.charOrCode==dojo.keys.ESCAPE){
this.closeDropDown();
dojo.stopEvent(e);
}else{
if(!this._opened&&(e.charOrCode==dojo.keys.DOWN_ARROW||((e.charOrCode==dojo.keys.ENTER||e.charOrCode==" ")&&((_5.tagName||"").toLowerCase()!=="input"||(_5.type&&_5.type.toLowerCase()!=="text"))))){
this.toggleDropDown();
d=this.dropDown;
if(d&&d.focus){
setTimeout(dojo.hitch(d,"focus"),1);
}
dojo.stopEvent(e);
}
}
},_onBlur:function(){
this.closeDropDown();
this.inherited(arguments);
},isLoaded:function(){
return true;
},loadDropDown:function(_6){
_6();
},toggleDropDown:function(){
if(this.disabled||this.readOnly){
return;
}
if(!this._opened){
if(!this.isLoaded()){
this.loadDropDown(dojo.hitch(this,"openDropDown"));
return;
}else{
this.openDropDown();
}
}else{
this.closeDropDown();
}
},openDropDown:function(){
var _7=this.dropDown,_8=_7.domNode,_9=this._aroundNode||this.domNode,_a=this;
if(!this._preparedNode){
this._preparedNode=true;
if(_8.style.width){
this._explicitDDWidth=true;
}
if(_8.style.height){
this._explicitDDHeight=true;
}
}
if(this.maxHeight||this.forceWidth||this.autoWidth){
var _b={display:"",visibility:"hidden"};
if(!this._explicitDDWidth){
_b.width="";
}
if(!this._explicitDDHeight){
_b.height="";
}
dojo.style(_8,_b);
var _c=this.maxHeight;
if(_c==-1){
var _d=dojo.window.getBox(),_e=dojo.position(_9,false);
_c=Math.floor(Math.max(_e.y,_d.h-(_e.y+_e.h)));
}
dijit.popup.moveOffScreen(_7);
var mb=dojo._getMarginSize(_8);
var _f=(_c&&mb.h>_c);
dojo.style(_8,{overflowX:"hidden",overflowY:_f?"auto":"hidden"});
if(_f){
mb.h=_c;
if("w" in mb){
mb.w+=16;
}
}else{
delete mb.h;
}
if(this.forceWidth){
mb.w=_9.offsetWidth;
}else{
if(this.autoWidth){
mb.w=Math.max(mb.w,_9.offsetWidth);
}else{
delete mb.w;
}
}
if(dojo.isFunction(_7.resize)){
_7.resize(mb);
}else{
dojo.marginBox(_8,mb);
}
}
var _10=dijit.popup.open({parent:this,popup:_7,around:_9,orient:dijit.getPopupAroundAlignment((this.dropDownPosition&&this.dropDownPosition.length)?this.dropDownPosition:["below"],this.isLeftToRight()),onExecute:function(){
_a.closeDropDown(true);
},onCancel:function(){
_a.closeDropDown(true);
},onClose:function(){
dojo.attr(_a._popupStateNode,"popupActive",false);
dojo.removeClass(_a._popupStateNode,"dijitHasDropDownOpen");
_a._opened=false;
}});
dojo.attr(this._popupStateNode,"popupActive","true");
dojo.addClass(_a._popupStateNode,"dijitHasDropDownOpen");
this._opened=true;
return _10;
},closeDropDown:function(_11){
if(this._opened){
if(_11){
this.focus();
}
dijit.popup.close(this.dropDown);
this._opened=false;
}
}});
}
