/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


define(["dojo","dojo/cache"],function(_1){
var _2={},_3=function(_4,_5){
define("text!"+_4,0,_5);
_2[_4]=_5;
_1.cache({toString:function(){
return _4;
}},_5);
},_6=function(_7){
if(_7){
_7=_7.replace(/^\s*<\?xml(\s)+version=[\'\"](\d)*.(\d)*[\'\"](\s)*\?>/im,"");
var _8=_7.match(/<body[^>]*>\s*([\s\S]+)\s*<\/body>/im);
if(_8){
_7=_8[1];
}
}else{
_7="";
}
return _7;
};
return {load:function(_9,id,_a){
var _b=id.split("!"),_c=_9.nameToUrl(_b[0]),_d="text!"+_c;
if(_c in _2){
_a(_b[1]&&_b[1]=="strip"?_6(_2[_c]):_2[_c]);
}else{
_1.xhrGet({url:_c,load:function(_e){
_3(_c,_e);
_a(_b[1]&&_b[1]=="strip"?_6(_e):_e);
}});
}
},cache:_3};
});
