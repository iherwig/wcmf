/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.mobile.ScrollableView"]){
dojo._hasResource["dojox.mobile.ScrollableView"]=true;
dojo.provide("dojox.mobile.ScrollableView");
dojo.require("dijit._Widget");
dojo.require("dojox.mobile");
dojo.require("dojox.mobile._ScrollableMixin");
dojo.declare("dojox.mobile.ScrollableView",[dojox.mobile.View,dojox.mobile._ScrollableMixin],{flippable:false,buildRendering:function(){
var i,_1,_2,c;
this.inherited(arguments);
dojo.addClass(this.domNode,"mblScrollableView");
this.domNode.style.overflow="hidden";
this.domNode.style.top="0px";
this.domNode.style.height="100%";
this.containerNode=dojo.doc.createElement("DIV");
dojo.addClass(this.containerNode,"mblScrollableViewContainer");
this.containerNode.style.position="absolute";
if(this.scrollDir=="v"||this.flippable){
this.containerNode.style.width="100%";
}
for(i=0,_1=0,_2=this.srcNodeRef.childNodes.length;i<_2;i++){
c=this.srcNodeRef.childNodes[_1];
if(this._checkFixedBar(c,true)){
_1++;
continue;
}
this.containerNode.appendChild(this.srcNodeRef.removeChild(c));
}
if(this.fixedFooter){
this.domNode.insertBefore(this.containerNode,this.fixedFooter);
}else{
this.domNode.appendChild(this.containerNode);
}
for(i=0,_2=dojo.body().childNodes.length;i<_2;i++){
c=dojo.body().childNodes[i];
this._checkFixedBar(c,false);
}
for(i=0,_2=this.domNode.parentNode.childNodes.length;i<_2;i++){
c=this.domNode.parentNode.childNodes[i];
this._checkFixedBar(c,false);
}
},_checkFixedBar:function(_3){
if(_3.nodeType==1){
var _4=_3.getAttribute("fixed");
if(_4){
dojo.style(_3,{position:"absolute",width:"100%",zIndex:1});
}
if(_4=="top"){
_3.style.top="0px";
this.fixedHeader=_3;
return _4;
}else{
if(_4=="bottom"){
this.fixedFooter=_3;
return _4;
}
}
}
return null;
},onAfterTransitionIn:function(_5,_6,_7,_8,_9){
this.flashScrollBar();
}});
}
