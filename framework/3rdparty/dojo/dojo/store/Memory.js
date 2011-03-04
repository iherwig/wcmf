/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojo.store.Memory"]){
dojo._hasResource["dojo.store.Memory"]=true;
dojo.provide("dojo.store.Memory");
dojo.require("dojo.store.util.QueryResults");
dojo.require("dojo.store.util.SimpleQueryEngine");
dojo.store.Memory=function(_1){
var _2={data:[],idProperty:"id",index:{},queryEngine:dojo.store.util.SimpleQueryEngine,get:function(id){
return this.index[id];
},getIdentity:function(_3){
return _3[this.idProperty];
},put:function(_4,_5){
var id=_5&&_5.id||_4[this.idProperty]||Math.random();
this.index[id]=_4;
var _6=this.data,_7=this.idProperty;
for(var i=0,l=_6.length;i<l;i++){
if(_6[i][_7]==id){
_6[i]=_4;
return id;
}
}
this.data.push(_4);
return id;
},add:function(_8,_9){
if(this.index[_9&&_9.id||_8[this.idProperty]]){
throw new Error("Object already exists");
}
return this.put(_8,_9);
},remove:function(id){
delete this.index[id];
var _a=this.data,_b=this.idProperty;
for(var i=0,l=_a.length;i<l;i++){
if(_a[i][_b]==id){
_a.splice(i,1);
return;
}
}
},query:function(_c,_d){
return dojo.store.util.QueryResults(this.queryEngine(_c,_d)(this.data));
},setData:function(_e){
if(_e.items){
this.idProperty=_e.identifier;
_e=this.data=_e.items;
}else{
this.data=_e;
}
for(var i=0,l=_e.length;i<l;i++){
var _f=_e[i];
this.index[_f[this.idProperty]]=_f;
}
}};
dojo.mixin(_2,_1);
_2.setData(_2.data);
return _2;
};
}
