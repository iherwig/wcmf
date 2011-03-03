/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.mobile.FlippableView"]){
dojo._hasResource["dojox.mobile.FlippableView"]=true;
dojo.provide("dojox.mobile.FlippableView");
dojo.require("dijit._Widget");
dojo.require("dojox.mobile");
dojo.require("dojox.mobile._ScrollableMixin");
dojo.declare("dojox.mobile.FlippableView",[dojox.mobile.View,dojox.mobile._ScrollableMixin],{scrollDir:"f",weight:1.2,buildRendering:function(){
this.inherited(arguments);
dojo.addClass(this.domNode,"mblFlippableView");
this.containerNode=this.domNode;
this.containerNode.style.position="absolute";
},_nextView:function(_1){
for(var n=_1.nextSibling;n;n=n.nextSibling){
if(n.nodeType==1){
return dijit.byNode(n);
}
}
return null;
},_previousView:function(_2){
for(var n=_2.previousSibling;n;n=n.previousSibling){
if(n.nodeType==1){
return dijit.byNode(n);
}
}
return null;
},scrollTo:function(to){
if(!this._beingFlipped){
var _3,x;
if(to.x<0){
_3=this._nextView(this.domNode);
x=to.x+this.domNode.offsetWidth;
}else{
_3=this._previousView(this.domNode);
x=to.x-this.domNode.offsetWidth;
}
if(_3){
_3.domNode.style.display="";
_3._beingFlipped=true;
_3.scrollTo({x:x});
_3._beingFlipped=false;
}
}
this.inherited(arguments);
},slideTo:function(to,_4,_5){
if(!this._beingFlipped){
var w=this.domNode.offsetWidth;
var _6=this.getPos();
var _7,_8;
if(_6.x<0){
_7=this._nextView(this.domNode);
if(_6.x<-w/4){
if(_7){
to.x=-w;
_8=0;
}
}else{
if(_7){
_8=w;
}
}
}else{
_7=this._previousView(this.domNode);
if(_6.x>w/4){
if(_7){
to.x=w;
_8=0;
}
}else{
if(_7){
_8=-w;
}
}
}
if(_7){
_7._beingFlipped=true;
_7.slideTo({x:_8},_4,_5);
_7._beingFlipped=false;
if(_8===0){
dojox.mobile.currentView=_7;
}
}
}
this.inherited(arguments);
},onFlickAnimationEnd:function(e){
var _9=this.domNode.parentNode.childNodes;
for(var i=0;i<_9.length;i++){
var c=_9[i];
if(c.nodeType==1&&c!=dojox.mobile.currentView.domNode){
c.style.display="none";
}
}
this.inherited(arguments);
}});
}
