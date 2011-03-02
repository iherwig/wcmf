/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


(function(){
if(typeof this["loadFirebugConsole"]=="function"){
this["loadFirebugConsole"]();
}else{
this.console=this.console||{};
var cn=["assert","count","debug","dir","dirxml","error","group","groupEnd","info","profile","profileEnd","time","timeEnd","trace","warn","log"];
var i=0,tn;
while((tn=cn[i++])){
if(!console[tn]){
(function(){
var _1=tn+"";
console[_1]=("log" in console)?function(){
var a=Array.apply({},arguments);
a.unshift(_1+":");
console["log"](a.join(" "));
}:function(){
};
console[_1]._fake=true;
})();
}
}
}
if(typeof dojo=="undefined"){
if(typeof require=="function"){
dojo=require("dojo");
define("dojo/_base/_loader/bootstrap",[],dojo);
}else{
define=function(id,_2,_3){
_3(dojo);
};
define.dojo=true;
dojo={};
}
dojo._scopeName="dojo";
dojo._scopePrefix="";
dojo._scopePrefixArgs="";
dojo._scopeSuffix="";
dojo._scopeMap={};
dojo._scopeMapRev={};
}
var d=dojo;
if(typeof dijit=="undefined"){
dijit={_scopeName:"dijit"};
}
if(typeof dojox=="undefined"){
dojox={_scopeName:"dojox"};
}
if(!d._scopeArgs){
d._scopeArgs=[dojo,dijit,dojox];
}
d.global=this;
d.config={isDebug:false,debugAtAllCosts:false};
var _4=typeof djConfig!="undefined"?djConfig:typeof dojoConfig!="undefined"?dojoConfig:null;
if(_4){
for(var c in _4){
d.config[c]=_4[c];
}
}
dojo.locale=d.config.locale;
var _5="$Rev: 23552 $".match(/\d+/);
dojo.version={major:1,minor:6,patch:0,flag:"b1",revision:_5?+_5[0]:NaN,toString:function(){
with(d.version){
return major+"."+minor+"."+patch+flag+" ("+revision+")";
}
}};
if(typeof OpenAjax!="undefined"){
OpenAjax.hub.registerLibrary(dojo._scopeName,"http://dojotoolkit.org",d.version.toString());
}
var _6,_7,_8={};
for(var i in {toString:1}){
_6=[];
break;
}
dojo._extraNames=_6=_6||["hasOwnProperty","valueOf","isPrototypeOf","propertyIsEnumerable","toLocaleString","toString","constructor"];
_7=_6.length;
dojo._mixin=function(_9,_a){
var _b,s,i;
for(_b in _a){
s=_a[_b];
if(!(_b in _9)||(_9[_b]!==s&&(!(_b in _8)||_8[_b]!==s))){
_9[_b]=s;
}
}
if(_7&&_a){
for(i=0;i<_7;++i){
_b=_6[i];
s=_a[_b];
if(!(_b in _9)||(_9[_b]!==s&&(!(_b in _8)||_8[_b]!==s))){
_9[_b]=s;
}
}
}
return _9;
};
dojo.mixin=function(_c,_d){
if(!_c){
_c={};
}
for(var i=1,l=arguments.length;i<l;i++){
d._mixin(_c,arguments[i]);
}
return _c;
};
dojo._getProp=function(_e,_f,_10){
var obj=_10||d.global;
for(var i=0,p;obj&&(p=_e[i]);i++){
if(i==0&&d._scopeMap[p]){
p=d._scopeMap[p];
}
obj=(p in obj?obj[p]:(_f?obj[p]={}:undefined));
}
return obj;
};
dojo.setObject=function(_11,_12,_13){
var _14=_11.split("."),p=_14.pop(),obj=d._getProp(_14,true,_13);
return obj&&p?(obj[p]=_12):undefined;
};
dojo.getObject=function(_15,_16,_17){
return d._getProp(_15.split("."),_16,_17);
};
dojo.exists=function(_18,obj){
return d.getObject(_18,false,obj)!==undefined;
};
dojo["eval"]=function(_19){
return d.global.eval?d.global.eval(_19):eval(_19);
};
d.deprecated=d.experimental=function(){
};
})();
