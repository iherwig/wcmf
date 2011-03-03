/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojo.store.JsonRest"]){
dojo._hasResource["dojo.store.JsonRest"]=true;
dojo.provide("dojo.store.JsonRest");
dojo.require("dojo.store.util.QueryResults");
dojo.store.JsonRest=function(_1){
var _2={target:"",idProperty:"id",get:function(id,_3){
var _4=_3||{};
_4.Accept="application/javascript, application/json";
return dojo.xhrGet({url:this.target+id,handleAs:"json",headers:_4});
},getIdentity:function(_5){
return _5[this.idProperty];
},put:function(_6,_7){
_7=_7||{};
var id=("id" in _7)?_7.id:this.getIdentity(_6);
var _8=typeof id!="undefined";
return dojo.xhr(_8&&!_7.incremental?"PUT":"POST",{url:_8?this.target+id:this.target,postData:dojo.toJson(_6),handleAs:"json",headers:{"Content-Type":"application/json","If-Match":_7.overwrite===true?"*":null,"If-None-Match":_7.overwrite===false?"*":null,}});
},add:function(_9,_a){
_a=_a||{};
_a.overwrite=false;
return this.put(_9,_a);
},remove:function(id){
return dojo.xhrDelete({url:this.target+id});
},query:function(_b,_c){
var _d={Accept:"application/javascript, application/json"};
_c=_c||{};
if(_c.start>=0||_c.count>=0){
_d.Range="items="+(_c.start||"0")+"-"+(("count" in _c&&_c.count!=Infinity)?(_c.count+(_c.start||0)-1):"");
}
if(dojo.isObject(_b)){
_b=dojo.objectToQuery(_b);
_b=_b?"?"+_b:"";
}
if(_c&&_c.sort&&!_c.queryStr){
_b+=(_b?"&":"?")+"sort(";
for(var i=0;i<_c.sort.length;i++){
var _e=_c.sort[i];
_b+=(i>0?",":"")+(_e.descending?"-":"+")+encodeURIComponent(_e.attribute);
}
_b+=")";
}
var _f=dojo.xhrGet({url:this.target+_b,handleAs:"json",headers:_d});
_f.total=_f.then(function(){
var _10=_f.ioArgs.xhr.getResponseHeader("Content-Range");
return _10&&(_10=_10.match(/\/(.*)/))&&parseInt(_10[1]);
});
return dojo.store.util.QueryResults(_f);
}};
dojo.mixin(_2,_1);
return _2;
};
}
