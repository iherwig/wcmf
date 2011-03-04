/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dijit._Widget"]){
dojo._hasResource["dijit._Widget"]=true;
dojo.provide("dijit._Widget");
dojo.require("dijit._base");
dojo.require("dojo.Stateful");
dojo.require("dijit._base");
dojo.connect(dojo,"_connect",function(_1,_2){
if(_1&&dojo.isFunction(_1._onConnect)){
_1._onConnect(_2);
}
});
dijit._connectOnUseEventHandler=function(_3){
};
dijit._lastKeyDownNode=null;
if(dojo.isIE){
(function(){
var _4=function(_5){
dijit._lastKeyDownNode=_5.srcElement;
};
dojo.doc.attachEvent("onkeydown",_4);
dojo.addOnWindowUnload(function(){
dojo.doc.detachEvent("onkeydown",_4);
});
})();
}else{
dojo.doc.addEventListener("keydown",function(_6){
dijit._lastKeyDownNode=_6.target;
},true);
}
(function(){
dojo.declare("dijit._Widget",dojo.Stateful,{id:"",lang:"",dir:"","class":"",style:"",title:"",tooltip:"",baseClass:"",srcNodeRef:null,domNode:null,containerNode:null,focused:false,attributeMap:{id:"",dir:"",lang:"","class":"",style:"",title:""},_deferredConnects:{onClick:"",onDblClick:"",onKeyDown:"",onKeyPress:"",onKeyUp:"",onMouseMove:"",onMouseDown:"",onMouseOut:"",onMouseOver:"",onMouseLeave:"",onMouseEnter:"",onMouseUp:""},onClick:dijit._connectOnUseEventHandler,onDblClick:dijit._connectOnUseEventHandler,onKeyDown:dijit._connectOnUseEventHandler,onKeyPress:dijit._connectOnUseEventHandler,onKeyUp:dijit._connectOnUseEventHandler,onMouseDown:dijit._connectOnUseEventHandler,onMouseMove:dijit._connectOnUseEventHandler,onMouseOut:dijit._connectOnUseEventHandler,onMouseOver:dijit._connectOnUseEventHandler,onMouseLeave:dijit._connectOnUseEventHandler,onMouseEnter:dijit._connectOnUseEventHandler,onMouseUp:dijit._connectOnUseEventHandler,_blankGif:(dojo.config.blankGif||dojo.moduleUrl("dojo","resources/blank.gif")).toString(),postscript:function(_7,_8){
this.create(_7,_8);
},create:function(_9,_a){
this.srcNodeRef=dojo.byId(_a);
this._connects=[];
this._subscribes=[];
this._deferredConnects=dojo.clone(this._deferredConnects);
for(var _b in this.attributeMap){
delete this._deferredConnects[_b];
}
for(_b in this._deferredConnects){
if(this[_b]!==dijit._connectOnUseEventHandler){
delete this._deferredConnects[_b];
}
}
if(this.srcNodeRef&&(typeof this.srcNodeRef.id=="string")){
this.id=this.srcNodeRef.id;
}
if(_9){
this.params=_9;
dojo._mixin(this,_9);
}
this.postMixInProperties();
if(!this.id){
this.id=dijit.getUniqueId(this.declaredClass.replace(/\./g,"_"));
}
dijit.registry.add(this);
this.buildRendering();
if(this.domNode){
this._applyAttributes();
var _c=this.srcNodeRef;
if(_c&&_c.parentNode&&this.domNode!==_c){
_c.parentNode.replaceChild(this.domNode,_c);
}
for(_b in this.params){
this._onConnect(_b);
}
}
if(this.domNode){
this.domNode.setAttribute("widgetId",this.id);
}
this.postCreate();
if(this.srcNodeRef&&!this.srcNodeRef.parentNode){
delete this.srcNodeRef;
}
this._created=true;
},_applyAttributes:function(){
var _d=function(_e,_f){
if((_f.params&&_e in _f.params)||_f[_e]){
_f.set(_e,_f[_e]);
}
};
for(var _10 in this.attributeMap){
_d(_10,this);
}
dojo.forEach(this._getSetterAttributes(),function(a){
if(!(a in this.attributeMap)){
_d(a,this);
}
},this);
},_getSetterAttributes:function(){
var _11=this.constructor;
if(!_11._setterAttrs){
var r=(_11._setterAttrs=[]),_12,_13=_11.prototype;
for(var _14 in _13){
if(dojo.isFunction(_13[_14])&&(_12=_14.match(/^_set([a-zA-Z]*)Attr$/))&&_12[1]){
r.push(_12[1].charAt(0).toLowerCase()+_12[1].substr(1));
}
}
}
return _11._setterAttrs;
},postMixInProperties:function(){
},buildRendering:function(){
if(!this.domNode){
this.domNode=this.srcNodeRef||dojo.create("div");
}
if(this.baseClass){
var _15=this.baseClass.split(" ");
if(!this.isLeftToRight()){
_15=_15.concat(dojo.map(_15,function(_16){
return _16+"Rtl";
}));
}
dojo.addClass(this.domNode,_15);
}
},postCreate:function(){
},startup:function(){
this._started=true;
},destroyRecursive:function(_17){
this._beingDestroyed=true;
this.destroyDescendants(_17);
this.destroy(_17);
},destroy:function(_18){
this._beingDestroyed=true;
this.uninitialize();
var d=dojo,dfe=d.forEach,dun=d.unsubscribe;
dfe(this._connects,function(_19){
dfe(_19,d.disconnect);
});
dfe(this._subscribes,function(_1a){
dun(_1a);
});
dfe(this._supportingWidgets||[],function(w){
if(w.destroyRecursive){
w.destroyRecursive();
}else{
if(w.destroy){
w.destroy();
}
}
});
this.destroyRendering(_18);
dijit.registry.remove(this.id);
this._destroyed=true;
},destroyRendering:function(_1b){
if(this.bgIframe){
this.bgIframe.destroy(_1b);
delete this.bgIframe;
}
if(this.domNode){
if(_1b){
dojo.removeAttr(this.domNode,"widgetId");
}else{
dojo.destroy(this.domNode);
}
delete this.domNode;
}
if(this.srcNodeRef){
if(!_1b){
dojo.destroy(this.srcNodeRef);
}
delete this.srcNodeRef;
}
},destroyDescendants:function(_1c){
dojo.forEach(this.getChildren(),function(_1d){
if(_1d.destroyRecursive){
_1d.destroyRecursive(_1c);
}
});
},uninitialize:function(){
return false;
},onFocus:function(){
},onBlur:function(){
},_onFocus:function(e){
this.onFocus();
},_onBlur:function(){
this.onBlur();
},_onConnect:function(_1e){
if(_1e in this._deferredConnects){
var _1f=this[this._deferredConnects[_1e]||"domNode"];
this.connect(_1f,_1e.toLowerCase(),_1e);
delete this._deferredConnects[_1e];
}
},_setClassAttr:function(_20){
var _21=this[this.attributeMap["class"]||"domNode"];
dojo.replaceClass(_21,_20,this["class"]);
this._set("class",_20);
},_setStyleAttr:function(_22){
var _23=this[this.attributeMap.style||"domNode"];
if(dojo.isObject(_22)){
dojo.style(_23,_22);
}else{
if(_23.style.cssText){
_23.style.cssText+="; "+_22;
}else{
_23.style.cssText=_22;
}
}
this._set("style",_22);
},setAttribute:function(_24,_25){
dojo.deprecated(this.declaredClass+"::setAttribute(attr, value) is deprecated. Use set() instead.","","2.0");
this.set(_24,_25);
},_attrToDom:function(_26,_27){
var _28=this.attributeMap[_26];
dojo.forEach(dojo.isArray(_28)?_28:[_28],function(_29){
var _2a=this[_29.node||_29||"domNode"];
var _2b=_29.type||"attribute";
switch(_2b){
case "attribute":
if(dojo.isFunction(_27)){
_27=dojo.hitch(this,_27);
}
var _2c=_29.attribute?_29.attribute:(/^on[A-Z][a-zA-Z]*$/.test(_26)?_26.toLowerCase():_26);
dojo.attr(_2a,_2c,_27);
break;
case "innerText":
_2a.innerHTML="";
_2a.appendChild(dojo.doc.createTextNode(_27));
break;
case "innerHTML":
_2a.innerHTML=_27;
break;
case "class":
dojo.replaceClass(_2a,_27,this[_26]);
break;
}
},this);
},attr:function(_2d,_2e){
if(dojo.config.isDebug){
var _2f=arguments.callee._ach||(arguments.callee._ach={}),_30=(arguments.callee.caller||"unknown caller").toString();
if(!_2f[_30]){
dojo.deprecated(this.declaredClass+"::attr() is deprecated. Use get() or set() instead, called from "+_30,"","2.0");
_2f[_30]=true;
}
}
var _31=arguments.length;
if(_31>=2||typeof _2d==="object"){
return this.set.apply(this,arguments);
}else{
return this.get(_2d);
}
},get:function(_32){
var _33=this._getAttrNames(_32);
return this[_33.g]?this[_33.g]():this[_32];
},set:function(_34,_35){
if(typeof _34==="object"){
for(var x in _34){
this.set(x,_34[x]);
}
return this;
}
var _36=this._getAttrNames(_34);
if(this[_36.s]){
var _37=this[_36.s].apply(this,Array.prototype.slice.call(arguments,1));
}else{
if(_34 in this.attributeMap){
this._attrToDom(_34,_35);
}
this._set(_34,_35);
}
return _37||this;
},_attrPairNames:{},_getAttrNames:function(_38){
var apn=this._attrPairNames;
if(apn[_38]){
return apn[_38];
}
var uc=_38.charAt(0).toUpperCase()+_38.substr(1);
return (apn[_38]={n:_38+"Node",s:"_set"+uc+"Attr",g:"_get"+uc+"Attr"});
},_set:function(_39,_3a){
var _3b=this[_39];
this[_39]=_3a;
if(this._watchCallbacks&&this._created&&_3a!==_3b){
this._watchCallbacks(_39,_3b,_3a);
}
},toString:function(){
return "[Widget "+this.declaredClass+", "+(this.id||"NO ID")+"]";
},getDescendants:function(){
return this.containerNode?dojo.query("[widgetId]",this.containerNode).map(dijit.byNode):[];
},getChildren:function(){
return this.containerNode?dijit.findWidgets(this.containerNode):[];
},nodesWithKeyClick:["input","button"],connect:function(obj,_3c,_3d){
var d=dojo,dc=d._connect,_3e=[];
if(_3c=="ondijitclick"){
if(d.indexOf(this.nodesWithKeyClick,obj.nodeName.toLowerCase())==-1){
var m=d.hitch(this,_3d);
_3e.push(dc(obj,"onkeydown",this,function(e){
if((e.keyCode==d.keys.ENTER||e.keyCode==d.keys.SPACE)&&!e.ctrlKey&&!e.shiftKey&&!e.altKey&&!e.metaKey){
dijit._lastKeyDownNode=e.target;
if(!("openDropDown" in this&&obj==this._buttonNode)){
e.preventDefault();
}
}
}),dc(obj,"onkeyup",this,function(e){
if((e.keyCode==d.keys.ENTER||e.keyCode==d.keys.SPACE)&&e.target==dijit._lastKeyDownNode&&!e.ctrlKey&&!e.shiftKey&&!e.altKey&&!e.metaKey){
dijit._lastKeyDownNode=null;
return m(e);
}
}));
}
_3c="onclick";
}
_3e.push(dc(obj,_3c,this,_3d));
this._connects.push(_3e);
return _3e;
},disconnect:function(_3f){
for(var i=0;i<this._connects.length;i++){
if(this._connects[i]==_3f){
dojo.forEach(_3f,dojo.disconnect);
this._connects.splice(i,1);
return;
}
}
},subscribe:function(_40,_41){
var _42=dojo.subscribe(_40,this,_41);
this._subscribes.push(_42);
return _42;
},unsubscribe:function(_43){
for(var i=0;i<this._subscribes.length;i++){
if(this._subscribes[i]==_43){
dojo.unsubscribe(_43);
this._subscribes.splice(i,1);
return;
}
}
},isLeftToRight:function(){
return this.dir?(this.dir=="ltr"):dojo._isBodyLtr();
},isFocusable:function(){
return this.focus&&(dojo.style(this.domNode,"display")!="none");
},placeAt:function(_44,_45){
if(_44.declaredClass&&_44.addChild){
_44.addChild(this,_45);
}else{
dojo.place(this.domNode,_44,_45);
}
return this;
},_onShow:function(){
this.onShow();
},onShow:function(){
},onHide:function(){
},onClose:function(){
return true;
}});
})();
}
