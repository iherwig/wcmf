/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojo.store.util.SimpleQueryEngine"]){
dojo._hasResource["dojo.store.util.SimpleQueryEngine"]=true;
dojo.provide("dojo.store.util.SimpleQueryEngine");
dojo.getObject("store.util",true,dojo);
dojo.store.util.SimpleQueryEngine=function(_1,_2){
switch(typeof _1){
default:
throw new Error("Can not query with a "+typeof _1);
case "object":
case "undefined":
var _3=_1;
_1=function(_4){
for(var _5 in _3){
if(_3[_5]!=_4[_5]){
return false;
}
}
return true;
};
break;
case "string":
if(!this[_1]){
throw new Error("No filter function "+_1+" was found in store");
}
_1=this[_1];
case "function":
}
function _6(_7){
var _8=dojo.filter(_7,_1);
if(_2&&_2.sort){
_8.sort(function(a,b){
for(var _9,i=0;_9=_2.sort[i];i++){
var _a=a[_9.attribute];
var _b=b[_9.attribute];
if(_a!=_b){
return !!_9.descending==_a>_b?-1:1;
}
}
return 0;
});
}
if(_2&&(_2.start||_2.count)){
var _c=_8.length;
_8=_8.slice(_2.start||0,(_2.start||0)+(_2.count||Infinity));
_8.total=_c;
}
return _8;
};
_6.matches=_1;
return _6;
};
}
