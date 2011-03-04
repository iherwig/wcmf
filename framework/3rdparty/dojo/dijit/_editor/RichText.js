/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dijit._editor.RichText"]){
dojo._hasResource["dijit._editor.RichText"]=true;
dojo.provide("dijit._editor.RichText");
dojo.require("dijit._Widget");
dojo.require("dijit._CssStateMixin");
dojo.require("dijit._editor.selection");
dojo.require("dijit._editor.range");
dojo.require("dijit._editor.html");
if(!dojo.config["useXDomain"]||dojo.config["allowXdRichTextSave"]){
if(dojo._postLoad){
(function(){
var _1=dojo.doc.createElement("textarea");
_1.id=dijit._scopeName+"._editor.RichText.value";
dojo.style(_1,{display:"none",position:"absolute",top:"-100px",height:"3px",width:"3px"});
dojo.body().appendChild(_1);
})();
}else{
try{
dojo.doc.write("<textarea id=\""+dijit._scopeName+"._editor.RichText.value\" "+"style=\"display:none;position:absolute;top:-100px;left:-100px;height:3px;width:3px;overflow:hidden;\"></textarea>");
}
catch(e){
}
}
}
dojo.declare("dijit._editor.RichText",[dijit._Widget,dijit._CssStateMixin],{constructor:function(_2){
this.contentPreFilters=[];
this.contentPostFilters=[];
this.contentDomPreFilters=[];
this.contentDomPostFilters=[];
this.editingAreaStyleSheets=[];
this.events=[].concat(this.events);
this._keyHandlers={};
if(_2&&dojo.isString(_2.value)){
this.value=_2.value;
}
this.onLoadDeferred=new dojo.Deferred();
},baseClass:"dijitEditor",inheritWidth:false,focusOnLoad:false,name:"",styleSheets:"",height:"300px",minHeight:"1em",isClosed:true,isLoaded:false,_SEPARATOR:"@@**%%__RICHTEXTBOUNDRY__%%**@@",_NAME_CONTENT_SEP:"@@**%%:%%**@@",onLoadDeferred:null,isTabIndent:false,disableSpellCheck:false,postCreate:function(){
if("textarea"==this.domNode.tagName.toLowerCase()){
console.warn("RichText should not be used with the TEXTAREA tag.  See dijit._editor.RichText docs.");
}
this.contentPreFilters=[dojo.hitch(this,"_preFixUrlAttributes")].concat(this.contentPreFilters);
if(dojo.isMoz){
this.contentPreFilters=[this._normalizeFontStyle].concat(this.contentPreFilters);
this.contentPostFilters=[this._removeMozBogus].concat(this.contentPostFilters);
}
if(dojo.isWebKit){
this.contentPreFilters=[this._removeWebkitBogus].concat(this.contentPreFilters);
this.contentPostFilters=[this._removeWebkitBogus].concat(this.contentPostFilters);
}
if(dojo.isIE){
this.contentPostFilters=[this._normalizeFontStyle].concat(this.contentPostFilters);
}
this.inherited(arguments);
dojo.publish(dijit._scopeName+"._editor.RichText::init",[this]);
this.open();
this.setupDefaultShortcuts();
},setupDefaultShortcuts:function(){
var _3=dojo.hitch(this,function(_4,_5){
return function(){
return !this.execCommand(_4,_5);
};
});
var _6={b:_3("bold"),i:_3("italic"),u:_3("underline"),a:_3("selectall"),s:function(){
this.save(true);
},m:function(){
this.isTabIndent=!this.isTabIndent;
},"1":_3("formatblock","h1"),"2":_3("formatblock","h2"),"3":_3("formatblock","h3"),"4":_3("formatblock","h4"),"\\":_3("insertunorderedlist")};
if(!dojo.isIE){
_6.Z=_3("redo");
}
for(var _7 in _6){
this.addKeyHandler(_7,true,false,_6[_7]);
}
},events:["onKeyPress","onKeyDown","onKeyUp"],captureEvents:[],_editorCommandsLocalized:false,_localizeEditorCommands:function(){
if(this._editorCommandsLocalized){
return;
}
this._editorCommandsLocalized=true;
var _8=["div","p","pre","h1","h2","h3","h4","h5","h6","ol","ul","address"];
var _9="",_a,i=0;
while((_a=_8[i++])){
if(_a.charAt(1)!="l"){
_9+="<"+_a+"><span>content</span></"+_a+"><br/>";
}else{
_9+="<"+_a+"><li>content</li></"+_a+"><br/>";
}
}
var _b=dojo.doc.createElement("div");
dojo.style(_b,{position:"absolute",top:"-2000px"});
dojo.doc.body.appendChild(_b);
_b.innerHTML=_9;
var _c=_b.firstChild;
while(_c){
dijit._editor.selection.selectElement(_c.firstChild);
dojo.withGlobal(this.window,"selectElement",dijit._editor.selection,[_c.firstChild]);
var _d=_c.tagName.toLowerCase();
this._local2NativeFormatNames[_d]=document.queryCommandValue("formatblock");
this._native2LocalFormatNames[this._local2NativeFormatNames[_d]]=_d;
_c=_c.nextSibling.nextSibling;
}
dojo.body().removeChild(_b);
},open:function(_e){
if(!this.onLoadDeferred||this.onLoadDeferred.fired>=0){
this.onLoadDeferred=new dojo.Deferred();
}
if(!this.isClosed){
this.close();
}
dojo.publish(dijit._scopeName+"._editor.RichText::open",[this]);
if(arguments.length==1&&_e.nodeName){
this.domNode=_e;
}
var dn=this.domNode;
var _f;
if(dojo.isString(this.value)){
_f=this.value;
delete this.value;
dn.innerHTML="";
}else{
if(dn.nodeName&&dn.nodeName.toLowerCase()=="textarea"){
var ta=(this.textarea=dn);
this.name=ta.name;
_f=ta.value;
dn=this.domNode=dojo.doc.createElement("div");
dn.setAttribute("widgetId",this.id);
ta.removeAttribute("widgetId");
dn.cssText=ta.cssText;
dn.className+=" "+ta.className;
dojo.place(dn,ta,"before");
var _10=dojo.hitch(this,function(){
dojo.style(ta,{display:"block",position:"absolute",top:"-1000px"});
if(dojo.isIE){
var s=ta.style;
this.__overflow=s.overflow;
s.overflow="hidden";
}
});
if(dojo.isIE){
setTimeout(_10,10);
}else{
_10();
}
if(ta.form){
var _11=ta.value;
this.reset=function(){
var _12=this.getValue();
if(_12!=_11){
this.replaceValue(_11);
}
};
dojo.connect(ta.form,"onsubmit",this,function(){
dojo.attr(ta,"disabled",this.disabled);
ta.value=this.getValue();
});
}
}else{
_f=dijit._editor.getChildrenHtml(dn);
dn.innerHTML="";
}
}
var _13=dojo.contentBox(dn);
this._oldHeight=_13.h;
this._oldWidth=_13.w;
this.value=_f;
if(dn.nodeName&&dn.nodeName=="LI"){
dn.innerHTML=" <br>";
}
this.header=dn.ownerDocument.createElement("div");
dn.appendChild(this.header);
this.editingArea=dn.ownerDocument.createElement("div");
dn.appendChild(this.editingArea);
this.footer=dn.ownerDocument.createElement("div");
dn.appendChild(this.footer);
if(!this.name){
this.name=this.id+"_AUTOGEN";
}
if(this.name!==""&&(!dojo.config["useXDomain"]||dojo.config["allowXdRichTextSave"])){
var _14=dojo.byId(dijit._scopeName+"._editor.RichText.value");
if(_14&&_14.value!==""){
var _15=_14.value.split(this._SEPARATOR),i=0,dat;
while((dat=_15[i++])){
var _16=dat.split(this._NAME_CONTENT_SEP);
if(_16[0]==this.name){
_f=_16[1];
_15=_15.splice(i,1);
_14.value=_15.join(this._SEPARATOR);
break;
}
}
}
if(!dijit._editor._globalSaveHandler){
dijit._editor._globalSaveHandler={};
dojo.addOnUnload(function(){
var id;
for(id in dijit._editor._globalSaveHandler){
var f=dijit._editor._globalSaveHandler[id];
if(dojo.isFunction(f)){
f();
}
}
});
}
dijit._editor._globalSaveHandler[this.id]=dojo.hitch(this,"_saveContent");
}
this.isClosed=false;
var ifr=(this.editorObject=this.iframe=dojo.doc.createElement("iframe"));
ifr.id=this.id+"_iframe";
this._iframeSrc=this._getIframeDocTxt();
ifr.style.border="none";
ifr.style.width="100%";
if(this._layoutMode){
ifr.style.height="100%";
}else{
if(dojo.isIE>=7){
if(this.height){
ifr.style.height=this.height;
}
if(this.minHeight){
ifr.style.minHeight=this.minHeight;
}
}else{
ifr.style.height=this.height?this.height:this.minHeight;
}
}
ifr.frameBorder=0;
ifr._loadFunc=dojo.hitch(this,function(win){
this.window=win;
this.document=this.window.document;
if(dojo.isIE){
this._localizeEditorCommands();
}
this.onLoad(_f);
});
var s="javascript:parent."+dijit._scopeName+".byId(\""+this.id+"\")._iframeSrc";
ifr.setAttribute("src",s);
this.editingArea.appendChild(ifr);
if(dojo.isSafari<=4){
var src=ifr.getAttribute("src");
if(!src||src.indexOf("javascript")==-1){
setTimeout(function(){
ifr.setAttribute("src",s);
},0);
}
}
if(dn.nodeName=="LI"){
dn.lastChild.style.marginTop="-1.2em";
}
dojo.addClass(this.domNode,this.baseClass);
},_local2NativeFormatNames:{},_native2LocalFormatNames:{},_getIframeDocTxt:function(){
var _17=dojo.getComputedStyle(this.domNode);
var _18="";
var _19=true;
if(dojo.isIE||dojo.isWebKit||(!this.height&&!dojo.isMoz)){
_18="<div id='dijitEditorBody'></div>";
_19=false;
}else{
if(dojo.isMoz){
this._cursorToStart=true;
_18="&nbsp;";
}
}
var _1a=[_17.fontWeight,_17.fontSize,_17.fontFamily].join(" ");
var _1b=_17.lineHeight;
if(_1b.indexOf("px")>=0){
_1b=parseFloat(_1b)/parseFloat(_17.fontSize);
}else{
if(_1b.indexOf("em")>=0){
_1b=parseFloat(_1b);
}else{
_1b="normal";
}
}
var _1c="";
var _1d=this;
this.style.replace(/(^|;)\s*(line-|font-?)[^;]+/ig,function(_1e){
_1e=_1e.replace(/^;/ig,"")+";";
var s=_1e.split(":")[0];
if(s){
s=dojo.trim(s);
s=s.toLowerCase();
var i;
var sC="";
for(i=0;i<s.length;i++){
var c=s.charAt(i);
switch(c){
case "-":
i++;
c=s.charAt(i).toUpperCase();
default:
sC+=c;
}
}
dojo.style(_1d.domNode,sC,"");
}
_1c+=_1e+";";
});
var _1f=dojo.query("label[for=\""+this.id+"\"]");
return [this.isLeftToRight()?"<html>\n<head>\n":"<html dir='rtl'>\n<head>\n",(dojo.isMoz&&_1f.length?"<title>"+_1f[0].innerHTML+"</title>\n":""),"<meta http-equiv='Content-Type' content='text/html'>\n","<style>\n","\tbody,html {\n","\t\tbackground:transparent;\n","\t\tpadding: 1px 0 0 0;\n","\t\tmargin: -1px 0 0 0;\n",((dojo.isWebKit)?"\t\twidth: 100%;\n":""),((dojo.isWebKit)?"\t\theight: 100%;\n":""),"\t}\n","\tbody{\n","\t\ttop:0px;\n","\t\tleft:0px;\n","\t\tright:0px;\n","\t\tfont:",_1a,";\n",((this.height||dojo.isOpera)?"":"\t\tposition: fixed;\n"),"\t\tmin-height:",this.minHeight,";\n","\t\tline-height:",_1b,";\n","\t}\n","\tp{ margin: 1em 0; }\n",(!_19&&!this.height?"\tbody,html {overflow-y: hidden;}\n":""),"\t#dijitEditorBody{overflow-x: auto; overflow-y:"+(this.height?"auto;":"hidden;")+" outline: 0px;}\n","\tli > ul:-moz-first-node, li > ol:-moz-first-node{ padding-top: 1.2em; }\n","\tli{ min-height:1.2em; }\n","</style>\n",this._applyEditingAreaStyleSheets(),"\n","</head>\n<body ",(_19?"id='dijitEditorBody' ":""),"onload='frameElement._loadFunc(window,document)' style='"+_1c+"'>",_18,"</body>\n</html>"].join("");
},_applyEditingAreaStyleSheets:function(){
var _20=[];
if(this.styleSheets){
_20=this.styleSheets.split(";");
this.styleSheets="";
}
_20=_20.concat(this.editingAreaStyleSheets);
this.editingAreaStyleSheets=[];
var _21="",i=0,url;
while((url=_20[i++])){
var _22=(new dojo._Url(dojo.global.location,url)).toString();
this.editingAreaStyleSheets.push(_22);
_21+="<link rel=\"stylesheet\" type=\"text/css\" href=\""+_22+"\"/>";
}
return _21;
},addStyleSheet:function(uri){
var url=uri.toString();
if(url.charAt(0)=="."||(url.charAt(0)!="/"&&!uri.host)){
url=(new dojo._Url(dojo.global.location,url)).toString();
}
if(dojo.indexOf(this.editingAreaStyleSheets,url)>-1){
return;
}
this.editingAreaStyleSheets.push(url);
this.onLoadDeferred.addCallback(dojo.hitch(this,function(){
if(this.document.createStyleSheet){
this.document.createStyleSheet(url);
}else{
var _23=this.document.getElementsByTagName("head")[0];
var _24=this.document.createElement("link");
_24.rel="stylesheet";
_24.type="text/css";
_24.href=url;
_23.appendChild(_24);
}
}));
},removeStyleSheet:function(uri){
var url=uri.toString();
if(url.charAt(0)=="."||(url.charAt(0)!="/"&&!uri.host)){
url=(new dojo._Url(dojo.global.location,url)).toString();
}
var _25=dojo.indexOf(this.editingAreaStyleSheets,url);
if(_25==-1){
return;
}
delete this.editingAreaStyleSheets[_25];
dojo.withGlobal(this.window,"query",dojo,["link:[href=\""+url+"\"]"]).orphan();
},disabled:false,_mozSettingProps:{"styleWithCSS":false},_setDisabledAttr:function(_26){
_26=!!_26;
this._set("disabled",_26);
if(!this.isLoaded){
return;
}
if(dojo.isIE||dojo.isWebKit||dojo.isOpera){
var _27=dojo.isIE&&(this.isLoaded||!this.focusOnLoad);
if(_27){
this.editNode.unselectable="on";
}
this.editNode.contentEditable=!_26;
if(_27){
var _28=this;
setTimeout(function(){
_28.editNode.unselectable="off";
},0);
}
}else{
try{
this.document.designMode=(_26?"off":"on");
}
catch(e){
return;
}
if(!_26&&this._mozSettingProps){
var ps=this._mozSettingProps;
for(var n in ps){
if(ps.hasOwnProperty(n)){
try{
this.document.execCommand(n,false,ps[n]);
}
catch(e2){
}
}
}
}
}
this._disabledOK=true;
},onLoad:function(_29){
if(!this.window.__registeredWindow){
this.window.__registeredWindow=true;
this._iframeRegHandle=dijit.registerIframe(this.iframe);
}
if(!dojo.isIE&&!dojo.isWebKit&&(this.height||dojo.isMoz)){
this.editNode=this.document.body;
}else{
this.editNode=this.document.body.firstChild;
var _2a=this;
if(dojo.isIE){
var _2b=(this.tabStop=dojo.doc.createElement("<div tabIndex=-1>"));
this.editingArea.appendChild(_2b);
this.iframe.onfocus=function(){
_2a.editNode.setActive();
};
}
}
this.focusNode=this.editNode;
var _2c=this.events.concat(this.captureEvents);
var ap=this.iframe?this.document:this.editNode;
dojo.forEach(_2c,function(_2d){
this.connect(ap,_2d.toLowerCase(),_2d);
},this);
this.connect(ap,"onmouseup","onClick");
if(dojo.isIE){
this.connect(this.document,"onmousedown","_onIEMouseDown");
this.editNode.style.zoom=1;
}else{
this.connect(this.document,"onmousedown",function(){
delete this._cursorToStart;
});
}
if(dojo.isWebKit){
this._webkitListener=this.connect(this.document,"onmouseup","onDisplayChanged");
this.connect(this.document,"onmousedown",function(e){
var t=e.target;
if(t&&(t===this.document.body||t===this.document)){
setTimeout(dojo.hitch(this,"placeCursorAtEnd"),0);
}
});
}
if(dojo.isIE){
try{
this.document.execCommand("RespectVisibilityInDesign",true,null);
}
catch(e){
}
}
this.isLoaded=true;
this.set("disabled",this.disabled);
var _2e=dojo.hitch(this,function(){
this.setValue(_29);
if(this.onLoadDeferred){
this.onLoadDeferred.callback(true);
}
this.onDisplayChanged();
if(this.focusOnLoad){
dojo.addOnLoad(dojo.hitch(this,function(){
setTimeout(dojo.hitch(this,"focus"),this.updateInterval);
}));
}
this.value=this.getValue(true);
});
if(this.setValueDeferred){
this.setValueDeferred.addCallback(_2e);
}else{
_2e();
}
},onKeyDown:function(e){
if(e.keyCode===dojo.keys.TAB&&this.isTabIndent){
dojo.stopEvent(e);
if(this.queryCommandEnabled((e.shiftKey?"outdent":"indent"))){
this.execCommand((e.shiftKey?"outdent":"indent"));
}
}
if(dojo.isIE){
if(e.keyCode==dojo.keys.TAB&&!this.isTabIndent){
if(e.shiftKey&&!e.ctrlKey&&!e.altKey){
this.iframe.focus();
}else{
if(!e.shiftKey&&!e.ctrlKey&&!e.altKey){
this.tabStop.focus();
}
}
}else{
if(e.keyCode===dojo.keys.BACKSPACE&&this.document.selection.type==="Control"){
dojo.stopEvent(e);
this.execCommand("delete");
}else{
if((65<=e.keyCode&&e.keyCode<=90)||(e.keyCode>=37&&e.keyCode<=40)){
e.charCode=e.keyCode;
this.onKeyPress(e);
}
}
}
}
return true;
},onKeyUp:function(e){
return;
},setDisabled:function(_2f){
dojo.deprecated("dijit.Editor::setDisabled is deprecated","use dijit.Editor::attr(\"disabled\",boolean) instead",2);
this.set("disabled",_2f);
},_setValueAttr:function(_30){
this.setValue(_30);
},_setDisableSpellCheckAttr:function(_31){
if(this.document){
dojo.attr(this.document.body,"spellcheck",!_31);
}else{
this.onLoadDeferred.addCallback(dojo.hitch(this,function(){
dojo.attr(this.document.body,"spellcheck",!_31);
}));
}
this._set("disableSpellCheck",_31);
},onKeyPress:function(e){
var c=(e.keyChar&&e.keyChar.toLowerCase())||e.keyCode,_32=this._keyHandlers[c],_33=arguments;
if(_32&&!e.altKey){
dojo.some(_32,function(h){
if(!(h.shift^e.shiftKey)&&!(h.ctrl^(e.ctrlKey||e.metaKey))){
if(!h.handler.apply(this,_33)){
e.preventDefault();
}
return true;
}
},this);
}
if(!this._onKeyHitch){
this._onKeyHitch=dojo.hitch(this,"onKeyPressed");
}
setTimeout(this._onKeyHitch,1);
return true;
},addKeyHandler:function(key,_34,_35,_36){
if(!dojo.isArray(this._keyHandlers[key])){
this._keyHandlers[key]=[];
}
this._keyHandlers[key].push({shift:_35||false,ctrl:_34||false,handler:_36});
},onKeyPressed:function(){
this.onDisplayChanged();
},onClick:function(e){
this.onDisplayChanged(e);
},_onIEMouseDown:function(e){
if(!this._focused&&!this.disabled){
this.focus();
}
},_onBlur:function(e){
this.inherited(arguments);
var _37=this.getValue(true);
if(_37!=this.value){
this.onChange(_37);
}
this._set("value",_37);
},_onFocus:function(e){
if(!this.disabled){
if(!this._disabledOK){
this.set("disabled",false);
}
this.inherited(arguments);
}
},blur:function(){
if(!dojo.isIE&&this.window.document.documentElement&&this.window.document.documentElement.focus){
this.window.document.documentElement.focus();
}else{
if(dojo.doc.body.focus){
dojo.doc.body.focus();
}
}
},focus:function(){
if(!this.isLoaded){
this.focusOnLoad=true;
return;
}
if(this._cursorToStart){
delete this._cursorToStart;
if(this.editNode.childNodes){
this.placeCursorAtStart();
return;
}
}
if(!dojo.isIE){
dijit.focus(this.iframe);
}else{
if(this.editNode&&this.editNode.focus){
this.iframe.fireEvent("onfocus",document.createEventObject());
}
}
},updateInterval:200,_updateTimer:null,onDisplayChanged:function(e){
if(this._updateTimer){
clearTimeout(this._updateTimer);
}
if(!this._updateHandler){
this._updateHandler=dojo.hitch(this,"onNormalizedDisplayChanged");
}
this._updateTimer=setTimeout(this._updateHandler,this.updateInterval);
},onNormalizedDisplayChanged:function(){
delete this._updateTimer;
},onChange:function(_38){
},_normalizeCommand:function(cmd,_39){
var _3a=cmd.toLowerCase();
if(_3a=="formatblock"){
if(dojo.isSafari&&_39===undefined){
_3a="heading";
}
}else{
if(_3a=="hilitecolor"&&!dojo.isMoz){
_3a="backcolor";
}
}
return _3a;
},_qcaCache:{},queryCommandAvailable:function(_3b){
var ca=this._qcaCache[_3b];
if(ca!==undefined){
return ca;
}
return (this._qcaCache[_3b]=this._queryCommandAvailable(_3b));
},_queryCommandAvailable:function(_3c){
var ie=1;
var _3d=1<<1;
var _3e=1<<2;
var _3f=1<<3;
function _40(_41){
return {ie:Boolean(_41&ie),mozilla:Boolean(_41&_3d),webkit:Boolean(_41&_3e),opera:Boolean(_41&_3f)};
};
var _42=null;
switch(_3c.toLowerCase()){
case "bold":
case "italic":
case "underline":
case "subscript":
case "superscript":
case "fontname":
case "fontsize":
case "forecolor":
case "hilitecolor":
case "justifycenter":
case "justifyfull":
case "justifyleft":
case "justifyright":
case "delete":
case "selectall":
case "toggledir":
_42=_40(_3d|ie|_3e|_3f);
break;
case "createlink":
case "unlink":
case "removeformat":
case "inserthorizontalrule":
case "insertimage":
case "insertorderedlist":
case "insertunorderedlist":
case "indent":
case "outdent":
case "formatblock":
case "inserthtml":
case "undo":
case "redo":
case "strikethrough":
case "tabindent":
_42=_40(_3d|ie|_3f|_3e);
break;
case "blockdirltr":
case "blockdirrtl":
case "dirltr":
case "dirrtl":
case "inlinedirltr":
case "inlinedirrtl":
_42=_40(ie);
break;
case "cut":
case "copy":
case "paste":
_42=_40(ie|_3d|_3e);
break;
case "inserttable":
_42=_40(_3d|ie);
break;
case "insertcell":
case "insertcol":
case "insertrow":
case "deletecells":
case "deletecols":
case "deleterows":
case "mergecells":
case "splitcell":
_42=_40(ie|_3d);
break;
default:
return false;
}
return (dojo.isIE&&_42.ie)||(dojo.isMoz&&_42.mozilla)||(dojo.isWebKit&&_42.webkit)||(dojo.isOpera&&_42.opera);
},execCommand:function(_43,_44){
var _45;
this.focus();
_43=this._normalizeCommand(_43,_44);
if(_44!==undefined){
if(_43=="heading"){
throw new Error("unimplemented");
}else{
if((_43=="formatblock")&&dojo.isIE){
_44="<"+_44+">";
}
}
}
var _46="_"+_43+"Impl";
if(this[_46]){
_45=this[_46](_44);
}else{
_44=arguments.length>1?_44:null;
if(_44||_43!="createlink"){
_45=this.document.execCommand(_43,false,_44);
}
}
this.onDisplayChanged();
return _45;
},queryCommandEnabled:function(_47){
if(this.disabled||!this._disabledOK){
return false;
}
_47=this._normalizeCommand(_47);
if(dojo.isMoz||dojo.isWebKit){
if(_47=="unlink"){
return this._sCall("hasAncestorElement",["a"]);
}else{
if(_47=="inserttable"){
return true;
}
}
}
if(dojo.isWebKit){
if(_47=="cut"||_47=="copy"){
var sel=this.window.getSelection();
if(sel){
sel=sel.toString();
}
return !!sel;
}else{
if(_47=="paste"){
return true;
}
}
}
var _48=dojo.isIE?this.document.selection.createRange():this.document;
try{
return _48.queryCommandEnabled(_47);
}
catch(e){
return false;
}
},queryCommandState:function(_49){
if(this.disabled||!this._disabledOK){
return false;
}
_49=this._normalizeCommand(_49);
try{
return this.document.queryCommandState(_49);
}
catch(e){
return false;
}
},queryCommandValue:function(_4a){
if(this.disabled||!this._disabledOK){
return false;
}
var r;
_4a=this._normalizeCommand(_4a);
if(dojo.isIE&&_4a=="formatblock"){
r=this._native2LocalFormatNames[this.document.queryCommandValue(_4a)];
}else{
if(dojo.isMoz&&_4a==="hilitecolor"){
var _4b;
try{
_4b=this.document.queryCommandValue("styleWithCSS");
}
catch(e){
_4b=false;
}
this.document.execCommand("styleWithCSS",false,true);
r=this.document.queryCommandValue(_4a);
this.document.execCommand("styleWithCSS",false,_4b);
}else{
r=this.document.queryCommandValue(_4a);
}
}
return r;
},_sCall:function(_4c,_4d){
return dojo.withGlobal(this.window,_4c,dijit._editor.selection,_4d);
},placeCursorAtStart:function(){
this.focus();
var _4e=false;
if(dojo.isMoz){
var _4f=this.editNode.firstChild;
while(_4f){
if(_4f.nodeType==3){
if(_4f.nodeValue.replace(/^\s+|\s+$/g,"").length>0){
_4e=true;
this._sCall("selectElement",[_4f]);
break;
}
}else{
if(_4f.nodeType==1){
_4e=true;
var tg=_4f.tagName?_4f.tagName.toLowerCase():"";
if(/br|input|img|base|meta|area|basefont|hr|link/.test(tg)){
this._sCall("selectElement",[_4f]);
}else{
this._sCall("selectElementChildren",[_4f]);
}
break;
}
}
_4f=_4f.nextSibling;
}
}else{
_4e=true;
this._sCall("selectElementChildren",[this.editNode]);
}
if(_4e){
this._sCall("collapse",[true]);
}
},placeCursorAtEnd:function(){
this.focus();
var _50=false;
if(dojo.isMoz){
var _51=this.editNode.lastChild;
while(_51){
if(_51.nodeType==3){
if(_51.nodeValue.replace(/^\s+|\s+$/g,"").length>0){
_50=true;
this._sCall("selectElement",[_51]);
break;
}
}else{
if(_51.nodeType==1){
_50=true;
if(_51.lastChild){
this._sCall("selectElement",[_51.lastChild]);
}else{
this._sCall("selectElement",[_51]);
}
break;
}
}
_51=_51.previousSibling;
}
}else{
_50=true;
this._sCall("selectElementChildren",[this.editNode]);
}
if(_50){
this._sCall("collapse",[false]);
}
},getValue:function(_52){
if(this.textarea){
if(this.isClosed||!this.isLoaded){
return this.textarea.value;
}
}
return this._postFilterContent(null,_52);
},_getValueAttr:function(){
return this.getValue(true);
},setValue:function(_53){
if(!this.isLoaded){
this.onLoadDeferred.addCallback(dojo.hitch(this,function(){
this.setValue(_53);
}));
return;
}
this._cursorToStart=true;
if(this.textarea&&(this.isClosed||!this.isLoaded)){
this.textarea.value=_53;
}else{
_53=this._preFilterContent(_53);
var _54=this.isClosed?this.domNode:this.editNode;
if(_53&&dojo.isMoz&&_53.toLowerCase()=="<p></p>"){
_53="<p>&nbsp;</p>";
}
if(!_53&&dojo.isWebKit){
_53="&nbsp;";
}
_54.innerHTML=_53;
this._preDomFilterContent(_54);
}
this.onDisplayChanged();
this._set("value",this.getValue(true));
},replaceValue:function(_55){
if(this.isClosed){
this.setValue(_55);
}else{
if(this.window&&this.window.getSelection&&!dojo.isMoz){
this.setValue(_55);
}else{
if(this.window&&this.window.getSelection){
_55=this._preFilterContent(_55);
this.execCommand("selectall");
if(!_55){
this._cursorToStart=true;
_55="&nbsp;";
}
this.execCommand("inserthtml",_55);
this._preDomFilterContent(this.editNode);
}else{
if(this.document&&this.document.selection){
this.setValue(_55);
}
}
}
}
this._set("value",this.getValue(true));
},_preFilterContent:function(_56){
var ec=_56;
dojo.forEach(this.contentPreFilters,function(ef){
if(ef){
ec=ef(ec);
}
});
return ec;
},_preDomFilterContent:function(dom){
dom=dom||this.editNode;
dojo.forEach(this.contentDomPreFilters,function(ef){
if(ef&&dojo.isFunction(ef)){
ef(dom);
}
},this);
},_postFilterContent:function(dom,_57){
var ec;
if(!dojo.isString(dom)){
dom=dom||this.editNode;
if(this.contentDomPostFilters.length){
if(_57){
dom=dojo.clone(dom);
}
dojo.forEach(this.contentDomPostFilters,function(ef){
dom=ef(dom);
});
}
ec=dijit._editor.getChildrenHtml(dom);
}else{
ec=dom;
}
if(!dojo.trim(ec.replace(/^\xA0\xA0*/,"").replace(/\xA0\xA0*$/,"")).length){
ec="";
}
dojo.forEach(this.contentPostFilters,function(ef){
ec=ef(ec);
});
return ec;
},_saveContent:function(e){
var _58=dojo.byId(dijit._scopeName+"._editor.RichText.value");
if(_58.value){
_58.value+=this._SEPARATOR;
}
_58.value+=this.name+this._NAME_CONTENT_SEP+this.getValue(true);
},escapeXml:function(str,_59){
str=str.replace(/&/gm,"&amp;").replace(/</gm,"&lt;").replace(/>/gm,"&gt;").replace(/"/gm,"&quot;");
if(!_59){
str=str.replace(/'/gm,"&#39;");
}
return str;
},getNodeHtml:function(_5a){
dojo.deprecated("dijit.Editor::getNodeHtml is deprecated","use dijit._editor.getNodeHtml instead",2);
return dijit._editor.getNodeHtml(_5a);
},getNodeChildrenHtml:function(dom){
dojo.deprecated("dijit.Editor::getNodeChildrenHtml is deprecated","use dijit._editor.getChildrenHtml instead",2);
return dijit._editor.getChildrenHtml(dom);
},close:function(_5b){
if(this.isClosed){
return;
}
if(!arguments.length){
_5b=true;
}
if(_5b){
this._set("value",this.getValue(true));
}
if(this.interval){
clearInterval(this.interval);
}
if(this._webkitListener){
this.disconnect(this._webkitListener);
delete this._webkitListener;
}
if(dojo.isIE){
this.iframe.onfocus=null;
}
this.iframe._loadFunc=null;
if(this._iframeRegHandle){
dijit.unregisterIframe(this._iframeRegHandle);
delete this._iframeRegHandle;
}
if(this.textarea){
var s=this.textarea.style;
s.position="";
s.left=s.top="";
if(dojo.isIE){
s.overflow=this.__overflow;
this.__overflow=null;
}
this.textarea.value=this.value;
dojo.destroy(this.domNode);
this.domNode=this.textarea;
}else{
this.domNode.innerHTML=this.value;
}
delete this.iframe;
dojo.removeClass(this.domNode,this.baseClass);
this.isClosed=true;
this.isLoaded=false;
delete this.editNode;
delete this.focusNode;
if(this.window&&this.window._frameElement){
this.window._frameElement=null;
}
this.window=null;
this.document=null;
this.editingArea=null;
this.editorObject=null;
},destroy:function(){
if(!this.isClosed){
this.close(false);
}
this.inherited(arguments);
if(dijit._editor._globalSaveHandler){
delete dijit._editor._globalSaveHandler[this.id];
}
},_removeMozBogus:function(_5c){
return _5c.replace(/\stype="_moz"/gi,"").replace(/\s_moz_dirty=""/gi,"").replace(/_moz_resizing="(true|false)"/gi,"");
},_removeWebkitBogus:function(_5d){
_5d=_5d.replace(/\sclass="webkit-block-placeholder"/gi,"");
_5d=_5d.replace(/\sclass="apple-style-span"/gi,"");
_5d=_5d.replace(/<meta charset=\"utf-8\" \/>/gi,"");
return _5d;
},_normalizeFontStyle:function(_5e){
return _5e.replace(/<(\/)?strong([ \>])/gi,"<$1b$2").replace(/<(\/)?em([ \>])/gi,"<$1i$2");
},_preFixUrlAttributes:function(_5f){
return _5f.replace(/(?:(<a(?=\s).*?\shref=)("|')(.*?)\2)|(?:(<a\s.*?href=)([^"'][^ >]+))/gi,"$1$4$2$3$5$2 _djrealurl=$2$3$5$2").replace(/(?:(<img(?=\s).*?\ssrc=)("|')(.*?)\2)|(?:(<img\s.*?src=)([^"'][^ >]+))/gi,"$1$4$2$3$5$2 _djrealurl=$2$3$5$2");
},_inserthorizontalruleImpl:function(_60){
if(dojo.isIE){
return this._inserthtmlImpl("<hr>");
}
return this.document.execCommand("inserthorizontalrule",false,_60);
},_unlinkImpl:function(_61){
if((this.queryCommandEnabled("unlink"))&&(dojo.isMoz||dojo.isWebKit)){
var a=this._sCall("getAncestorElement",["a"]);
this._sCall("selectElement",[a]);
return this.document.execCommand("unlink",false,null);
}
return this.document.execCommand("unlink",false,_61);
},_hilitecolorImpl:function(_62){
var _63;
if(dojo.isMoz){
this.document.execCommand("styleWithCSS",false,true);
_63=this.document.execCommand("hilitecolor",false,_62);
this.document.execCommand("styleWithCSS",false,false);
}else{
_63=this.document.execCommand("hilitecolor",false,_62);
}
return _63;
},_backcolorImpl:function(_64){
if(dojo.isIE){
_64=_64?_64:null;
}
return this.document.execCommand("backcolor",false,_64);
},_forecolorImpl:function(_65){
if(dojo.isIE){
_65=_65?_65:null;
}
return this.document.execCommand("forecolor",false,_65);
},_inserthtmlImpl:function(_66){
_66=this._preFilterContent(_66);
var rv=true;
if(dojo.isIE){
var _67=this.document.selection.createRange();
if(this.document.selection.type.toUpperCase()=="CONTROL"){
var n=_67.item(0);
while(_67.length){
_67.remove(_67.item(0));
}
n.outerHTML=_66;
}else{
_67.pasteHTML(_66);
}
_67.select();
}else{
if(dojo.isMoz&&!_66.length){
this._sCall("remove");
}else{
rv=this.document.execCommand("inserthtml",false,_66);
}
}
return rv;
},_boldImpl:function(_68){
if(dojo.isIE){
this._adaptIESelection();
}
return this.document.execCommand("bold",false,_68);
},_italicImpl:function(_69){
if(dojo.isIE){
this._adaptIESelection();
}
return this.document.execCommand("italic",false,_69);
},_underlineImpl:function(_6a){
if(dojo.isIE){
this._adaptIESelection();
}
return this.document.execCommand("underline",false,_6a);
},_strikethroughImpl:function(_6b){
if(dojo.isIE){
this._adaptIESelection();
}
return this.document.execCommand("strikethrough",false,_6b);
},getHeaderHeight:function(){
return this._getNodeChildrenHeight(this.header);
},getFooterHeight:function(){
return this._getNodeChildrenHeight(this.footer);
},_getNodeChildrenHeight:function(_6c){
var h=0;
if(_6c&&_6c.childNodes){
var i;
for(i=0;i<_6c.childNodes.length;i++){
var _6d=dojo.position(_6c.childNodes[i]);
h+=_6d.h;
}
}
return h;
},_isNodeEmpty:function(_6e,_6f){
if(_6e.nodeType==1){
if(_6e.childNodes.length>0){
return this._isNodeEmpty(_6e.childNodes[0],_6f);
}
return true;
}else{
if(_6e.nodeType==3){
return (_6e.nodeValue.substring(_6f)=="");
}
}
return false;
},_removeStartingRangeFromRange:function(_70,_71){
if(_70.nextSibling){
_71.setStart(_70.nextSibling,0);
}else{
var _72=_70.parentNode;
while(_72&&_72.nextSibling==null){
_72=_72.parentNode;
}
if(_72){
_71.setStart(_72.nextSibling,0);
}
}
return _71;
},_adaptIESelection:function(){
var _73=dijit.range.getSelection(this.window);
if(_73&&_73.rangeCount){
var _74=_73.getRangeAt(0);
var _75=_74.startContainer;
var _76=_74.startOffset;
while(_75.nodeType==3&&_76>=_75.length&&_75.nextSibling){
_76=_76-_75.length;
_75=_75.nextSibling;
}
var _77=null;
while(this._isNodeEmpty(_75,_76)&&_75!=_77){
_77=_75;
_74=this._removeStartingRangeFromRange(_75,_74);
_75=_74.startContainer;
_76=0;
}
_73.removeAllRanges();
_73.addRange(_74);
}
}});
}
