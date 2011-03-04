/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojo.data.ObjectStore"]){
dojo._hasResource["dojo.data.ObjectStore"]=true;
dojo.provide("dojo.data.ObjectStore");
dojo.declare("dojo.data.ObjectStore",null,{objectStore:null,constructor:function(_1){
dojo.mixin(this,_1);
},labelProperty:"label",getValue:function(_2,_3,_4){
return typeof _2.get==="function"?_2.get(_3):_3 in _2?_2[_3]:_4;
},getValues:function(_5,_6){
var _7=this.getValue(_5,_6);
return _7 instanceof Array?_7:_7===undefined?[]:[_7];
},getAttributes:function(_8){
var _9=[];
for(var i in _8){
if(_8.hasOwnProperty(i)&&!(i.charAt(0)=="_"&&i.charAt(1)=="_")){
_9.push(i);
}
}
return _9;
},hasAttribute:function(_a,_b){
return _b in _a;
},containsValue:function(_c,_d,_e){
return dojo.indexOf(this.getValues(_c,_d),_e)>-1;
},isItem:function(_f){
return (typeof _f=="object")&&_f&&!(_f instanceof Date);
},isItemLoaded:function(_10){
return _10&&typeof _10.load!=="function";
},loadItem:function(_11){
var _12;
if(typeof _11.item.load==="function"){
dojo.when(_11.item.load(),function(_13){
_12=_13;
var _14=_13 instanceof Error?_11.onError:_11.onItem;
if(_14){
_14.call(_11.scope,_13);
}
});
}else{
if(_11.onItem){
_11.onItem.call(_11.scope,_11.item);
}
}
return _12;
},close:function(_15){
return _15&&_15.abort&&_15.abort();
},fetch:function(_16){
_16=_16||{};
var _17=this;
var _18=_16.scope||_17;
var _19=this.objectStore.query(_16.query,_16);
dojo.when(_19.total,function(_1a){
dojo.when(_19,function(_1b){
if(_16.onBegin){
_16.onBegin.call(_18,_1a||_1b.length,_16);
}
if(_16.onItem){
for(var i=0;i<_1b.length;i++){
_16.onItem.call(_18,_1b[i],_16);
}
}
if(_16.onComplete){
_16.onComplete.call(_18,_16.onItem?null:_1b,_16);
}
return _1b;
},_16.onError&&dojo.hitch(_18,_16.onError));
},_16.onError&&dojo.hitch(_18,_16.onError));
_16.abort=function(){
defResult.ioArgs.xhr.abort();
};
_16.store=this;
return _16;
},getFeatures:function(){
return {"dojo.data.api.Read":!!this.objectStore.get,"dojo.data.api.Identity":true,"dojo.data.api.Write":!!this.objectStore.put,"dojo.data.api.Notification":!!this.objectStore.subscribe};
},getLabel:function(_1c){
return this.getValue(_1c,this.labelProperty);
},getLabelAttributes:function(_1d){
return [this.labelProperty];
},getIdentity:function(_1e){
return _1e.getId?_1e.getId():_1e[this.objectStore.idProperty||"id"];
},getIdentityAttributes:function(_1f){
return [this.objectStore.idProperty];
},fetchItemByIdentity:function(_20){
var _21;
dojo.when(this.objectStore.get(_20.identity),function(_22){
_21=_22;
_20.onItem.call(_20.scope,_22);
},function(_23){
_20.onError.call(_20.scope,_23);
});
return _21;
},newItem:function(_24,_25){
_24=new this._constructor(_24);
if(_25){
var _26=this.getValue(_25.parent,_25.attribute,[]);
_26=_26.concat([_24]);
_24.__parent=_26;
this.setValue(_25.parent,_25.attribute,_26);
}
this._dirtyObjects.push({object:_24,save:true});
return _24;
},deleteItem:function(_27){
this.changing(_27,true);
this.onDelete(_27);
},setValue:function(_28,_29,_2a){
var old=_28[_29];
this.changing(_28);
_28[_29]=_2a;
this.onSet(_28,_29,old,_2a);
},setValues:function(_2b,_2c,_2d){
if(!dojo.isArray(_2d)){
throw new Error("setValues expects to be passed an Array object as its value");
}
this.setValue(_2b,_2c,_2d);
},unsetAttribute:function(_2e,_2f){
this.changing(_2e);
var old=_2e[_2f];
delete _2e[_2f];
this.onSet(_2e,_2f,old,undefined);
},_dirtyObjects:[],changing:function(_30,_31){
_30.__isDirty=true;
for(var i=0;i<this._dirtyObjects.length;i++){
var _32=this._dirtyObjects[i];
if(_30==_32.object){
if(_31){
_32.object=false;
if(!this._saveNotNeeded){
_32.save=true;
}
}
return;
}
}
var old=_30 instanceof Array?[]:{};
for(i in _30){
if(_30.hasOwnProperty(i)){
old[i]=_30[i];
}
}
this._dirtyObjects.push({object:!_31&&_30,old:old,save:!this._saveNotNeeded});
},save:function(_33){
_33=_33||{};
var _34,_35=[];
var _36={};
var _37=[];
var _38;
var _39=this._dirtyObjects;
var _3a=_39.length;
try{
dojo.connect(_33,"onError",function(){
if(_33.revertOnError!==false){
var _3b=_39;
_39=_37;
var _3c=0;
jr.revert();
_38._dirtyObjects=_3b;
}else{
_38._dirtyObjects=dirtyObject.concat(_37);
}
});
if(this.objectStore.transaction){
var _3d=this.objectStore.transaction();
}
for(var i=0;i<_39.length;i++){
var _3e=_39[i];
var _3f=_3e.object;
var old=_3e.old;
delete _3f.__isDirty;
if(_3f){
_34=this.objectStore.put(_3f,{overwrite:!!old});
}else{
_34=this.objectStore.remove(this.getIdentity(old));
}
_37.push(_3e);
_39.splice(i--,1);
dojo.when(_34,function(_40){
if(!(--_3a)){
if(_33.onComplete){
_33.onComplete.call(_33.scope,_35);
}
}
},function(_41){
_3a=-1;
_33.onError.call(_33.scope,_41);
});
}
if(_3d){
_3d.commit();
}
}
catch(e){
_33.onError.call(_33.scope,value);
}
},revert:function(_42){
var _43=this._dirtyObjects;
for(var i=_43.length;i>0;){
i--;
var _44=_43[i];
var _45=_44.object;
var old=_44.old;
if(_45&&old){
for(var j in old){
if(old.hasOwnProperty(j)&&_45[j]!==old[j]){
this.onSet(_45,j,_45[j],old[j]);
_45[j]=old[j];
}
}
for(j in _45){
if(!old.hasOwnProperty(j)){
this.onSet(_45,j,_45[j]);
delete _45[j];
}
}
}else{
if(!old){
this.onDelete(_45);
}else{
this.onNew(old);
}
}
delete (_45||old).__isDirty;
_43.splice(i,1);
}
},isDirty:function(_46){
if(!_46){
return !!this._dirtyObjects.length;
}
return _46.__isDirty;
},onSet:function(){
},onNew:function(){
},onDelete:function(){
}});
}
