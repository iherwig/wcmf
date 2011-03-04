/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojo.store.Observable"]){
dojo._hasResource["dojo.store.Observable"]=true;
dojo.provide("dojo.store.Observable");
dojo.store.Observable=function(_1){
var _2=[],_3=0;
var _4=_1.notify=function(_5,_6){
_3++;
var _7=_2.slice();
for(var i=0,l=_7.length;i<l;i++){
_7[i](_5,_6);
}
};
var _8=_1.query;
_1.query=function(_9,_a){
_a=_a||{};
var _b=_8.apply(this,arguments);
if(_b&&_b.forEach){
var _c=dojo.mixin({},_a);
delete _c.start;
delete _c.count;
var _d=_1.queryEngine&&_1.queryEngine(_9,_c);
var _e=_3;
var _f=[],_10;
_b.observe=function(_11,_12){
if(_f.push(_11)==1){
_2.push(_10=function(_13,_14){
dojo.when(_b,function(_15){
var _16=_15.length!=_a.count;
var i;
if(++_e!=_3){
throw new Error("Query is out of date, you must observe() the query prior to any data modifications");
}
var _17,_18,_19;
if(_14){
for(i=0,l=_15.length;i<l;i++){
var _1a=_15[i];
if(_1.getIdentity(_1a)==_14){
_17=_1a;
_18=i;
_15.splice(i,1);
break;
}
}
}
if(_d){
if(_13&&(_d.matches?_d.matches(_13):_d([_13]).length)){
if(_18>-1){
_15.splice(_18,0,_13);
}else{
_15.push(_13);
}
_19=_d(_15).indexOf(_13);
if((_a.start&&_19==0)||(!_16&&_19==_15.length-1)){
_19=-1;
}
}
}else{
if(_13){
_19=_18>=0?_18:-1;
}
}
if((_18>-1||_19>-2)&&(_12||!_d||(_18!=_19))){
var _1b=_f.slice();
for(i=0;_11=_1b[i];i++){
_11(_13||_17,_18,_19);
}
}
});
});
}
return {cancel:function(){
_f.splice(dojo.indexOf(_f,_11),1);
if(!_f.length){
_2.splice(dojo.indexOf(_2,_10),1);
}
}};
};
}
return _b;
};
var _1c;
function _1d(_1e,_1f){
var _20=_1[_1e];
if(_20){
_1[_1e]=function(_21){
if(_1c){
return _20.apply(this,arguments);
}
_1c=true;
try{
return dojo.when(_20.apply(this,arguments),function(_22){
_1f((typeof _22=="object"&&_22)||_21);
return _22;
});
}
finally{
_1c=false;
}
};
}
};
_1d("put",function(_23){
_4(_23,_1.getIdentity(_23));
});
_1d("add",_4);
_1d("remove",function(id){
_4(undefined,id);
});
return _1;
};
}
