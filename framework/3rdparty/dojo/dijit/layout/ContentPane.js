/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dijit.layout.ContentPane"]){
dojo._hasResource["dijit.layout.ContentPane"]=true;
dojo.provide("dijit.layout.ContentPane");
dojo.require("dijit._Widget");
dojo.require("dijit.layout._LayoutWidget");
dojo.require("dijit.layout._ContentPaneResizeMixin");
dojo.require("dojo.string");
dojo.require("dojo.html");
dojo.requireLocalization("dijit","loading",null,"ROOT,ar,ca,cs,da,de,el,es,fi,fr,he,hu,it,ja,kk,ko,nb,nl,pl,pt,pt-pt,ro,ru,sk,sl,sv,th,tr,zh,zh-tw");
dojo.declare("dijit.layout.ContentPane",[dijit._Widget,dijit.layout._ContentPaneResizeMixin],{href:"",extractContent:false,parseOnLoad:true,parserScope:dojo._scopeName,preventCache:false,preload:false,refreshOnShow:false,loadingMessage:"<span class='dijitContentPaneLoading'>${loadingState}</span>",errorMessage:"<span class='dijitContentPaneError'>${errorState}</span>",isLoaded:false,baseClass:"dijitContentPane",ioArgs:{},isContainer:true,onLoadDeferred:null,attributeMap:dojo.delegate(dijit._Widget.prototype.attributeMap,{title:[]}),stopParser:true,template:false,create:function(_1,_2){
if((!_1||!_1.template)&&_2&&!("href" in _1)&&!("content" in _1)){
var df=dojo.doc.createDocumentFragment();
_2=dojo.byId(_2);
while(_2.firstChild){
df.appendChild(_2.firstChild);
}
_1=dojo.delegate(_1,{content:df});
}
this.inherited(arguments,[_1,_2]);
},postMixInProperties:function(){
this.inherited(arguments);
var _3=dojo.i18n.getLocalization("dijit","loading",this.lang);
this.loadingMessage=dojo.string.substitute(this.loadingMessage,_3);
this.errorMessage=dojo.string.substitute(this.errorMessage,_3);
},buildRendering:function(){
this.inherited(arguments);
if(!this.containerNode){
this.containerNode=this.domNode;
}
this.domNode.title="";
if(!dojo.attr(this.domNode,"role")){
dijit.setWaiRole(this.domNode,"group");
}
},startup:function(){
if(this._started){
return;
}
this.inherited(arguments);
if(this._isShown()||this.preload){
this._onShow();
}
},setHref:function(_4){
dojo.deprecated("dijit.layout.ContentPane.setHref() is deprecated. Use set('href', ...) instead.","","2.0");
return this.set("href",_4);
},_setHrefAttr:function(_5){
this.cancel();
this.onLoadDeferred=new dojo.Deferred(dojo.hitch(this,"cancel"));
this.onLoadDeferred.addCallback(dojo.hitch(this,"onLoad"));
this._set("href",_5);
if(this._created&&(this.preload||this._isShown())){
this._load();
}else{
this._hrefChanged=true;
}
return this.onLoadDeferred;
},setContent:function(_6){
dojo.deprecated("dijit.layout.ContentPane.setContent() is deprecated.  Use set('content', ...) instead.","","2.0");
this.set("content",_6);
},_setContentAttr:function(_7){
this._set("href","");
this.cancel();
this.onLoadDeferred=new dojo.Deferred(dojo.hitch(this,"cancel"));
if(this._created){
this.onLoadDeferred.addCallback(dojo.hitch(this,"onLoad"));
}
this._setContent(_7||"");
this._isDownloaded=false;
return this.onLoadDeferred;
},_getContentAttr:function(){
return this.containerNode.innerHTML;
},cancel:function(){
if(this._xhrDfd&&(this._xhrDfd.fired==-1)){
this._xhrDfd.cancel();
}
delete this._xhrDfd;
this.onLoadDeferred=null;
},uninitialize:function(){
if(this._beingDestroyed){
this.cancel();
}
this.inherited(arguments);
},destroyRecursive:function(_8){
if(this._beingDestroyed){
return;
}
this.inherited(arguments);
},resize:function(_9,_a){
if(!this._wasShown&&this.open!==false){
this._onShow();
}
this._resizeCalled=true;
this._scheduleLayout(_9,_a);
},_isShown:function(){
if(this._childOfLayoutWidget){
if(this._resizeCalled&&"open" in this){
return this.open;
}
return this._resizeCalled;
}else{
if("open" in this){
return this.open;
}else{
var _b=this.domNode;
return (_b.style.display!="none")&&(_b.style.visibility!="hidden")&&!dojo.hasClass(_b,"dijitHidden");
}
}
},_onShow:function(){
if(this.href){
if(!this._xhrDfd&&(!this.isLoaded||this._hrefChanged||this.refreshOnShow)){
var d=this.refresh();
}
}else{
if(this._needLayout){
this._layout(this._changeSize,this._resultSize);
}
}
this.inherited(arguments);
this._wasShown=true;
return d;
},refresh:function(){
this.cancel();
this.onLoadDeferred=new dojo.Deferred(dojo.hitch(this,"cancel"));
this.onLoadDeferred.addCallback(dojo.hitch(this,"onLoad"));
this._load();
return this.onLoadDeferred;
},_load:function(){
this._setContent(this.onDownloadStart(),true);
var _c=this;
var _d={preventCache:(this.preventCache||this.refreshOnShow),url:this.href,handleAs:"text"};
if(dojo.isObject(this.ioArgs)){
dojo.mixin(_d,this.ioArgs);
}
var _e=(this._xhrDfd=(this.ioMethod||dojo.xhrGet)(_d));
_e.addCallback(function(_f){
try{
_c._isDownloaded=true;
_c._setContent(_f,false);
_c.onDownloadEnd();
}
catch(err){
_c._onError("Content",err);
}
delete _c._xhrDfd;
return _f;
});
_e.addErrback(function(err){
if(!_e.canceled){
_c._onError("Download",err);
}
delete _c._xhrDfd;
return err;
});
delete this._hrefChanged;
},_onLoadHandler:function(_10){
this._set("isLoaded",true);
try{
this.onLoadDeferred.callback(_10);
}
catch(e){
console.error("Error "+this.widgetId+" running custom onLoad code: "+e.message);
}
},_onUnloadHandler:function(){
this._set("isLoaded",false);
try{
this.onUnload();
}
catch(e){
console.error("Error "+this.widgetId+" running custom onUnload code: "+e.message);
}
},destroyDescendants:function(){
if(this.isLoaded){
this._onUnloadHandler();
}
var _11=this._contentSetter;
dojo.forEach(this.getChildren(),function(_12){
if(_12.destroyRecursive){
_12.destroyRecursive();
}
});
if(_11){
dojo.forEach(_11.parseResults,function(_13){
if(_13.destroyRecursive&&_13.domNode&&_13.domNode.parentNode==dojo.body()){
_13.destroyRecursive();
}
});
delete _11.parseResults;
}
dojo.html._emptyNode(this.containerNode);
delete this._singleChild;
},_setContent:function(_14,_15){
this.destroyDescendants();
var _16=this._contentSetter;
if(!(_16&&_16 instanceof dojo.html._ContentSetter)){
_16=this._contentSetter=new dojo.html._ContentSetter({node:this.containerNode,_onError:dojo.hitch(this,this._onError),onContentError:dojo.hitch(this,function(e){
var _17=this.onContentError(e);
try{
this.containerNode.innerHTML=_17;
}
catch(e){
console.error("Fatal "+this.id+" could not change content due to "+e.message,e);
}
})});
}
var _18=dojo.mixin({cleanContent:this.cleanContent,extractContent:this.extractContent,parseContent:this.parseOnLoad,parserScope:this.parserScope,startup:false,dir:this.dir,lang:this.lang},this._contentSetterParams||{});
_16.set((dojo.isObject(_14)&&_14.domNode)?_14.domNode:_14,_18);
delete this._contentSetterParams;
if(this.doLayout){
this._checkIfSingleChild();
}
if(!_15){
if(this._started){
dojo.forEach(this.getChildren(),function(_19){
_19.startup();
},this);
this._scheduleLayout();
}
this._onLoadHandler(_14);
}
},_onError:function(_1a,err,_1b){
this.onLoadDeferred.errback(err);
var _1c=this["on"+_1a+"Error"].call(this,err);
if(_1b){
console.error(_1b,err);
}else{
if(_1c){
this._setContent(_1c,true);
}
}
},_scheduleLayout:function(_1d,_1e){
if(this._isShown()){
this._layout(_1d,_1e);
}else{
this._needLayout=true;
this._changeSize=_1d;
this._resultSize=_1e;
}
},onLoad:function(_1f){
},onUnload:function(){
},onDownloadStart:function(){
return this.loadingMessage;
},onContentError:function(_20){
},onDownloadError:function(_21){
return this.errorMessage;
},onDownloadEnd:function(){
}});
}
