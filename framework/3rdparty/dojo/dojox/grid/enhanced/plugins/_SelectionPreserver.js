/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.grid.enhanced.plugins._SelectionPreserver"]){
dojo._hasResource["dojox.grid.enhanced.plugins._SelectionPreserver"]=true;
dojo.provide("dojox.grid.enhanced.plugins._SelectionPreserver");
dojo.declare("dojox.grid.enhanced.plugins._SelectionPreserver",null,{_connects:[],constructor:function(_1){
this.selection=_1;
var _2=this.grid=_1.grid;
_2.onSelectedById=this.onSelectedById;
this.reset();
var _3=_2._clearData;
var _4=this;
_2._clearData=function(){
_4._updateMapping(true);
_4._trustSelection=[];
_3.apply(_2,arguments);
};
this.connect(_2,"_setStore","reset");
this.connect(_2,"_addItem","_reSelectById");
this.connect(_1,"addToSelection",dojo.hitch(this,"_selectById",true));
this.connect(_1,"deselect",dojo.hitch(this,"_selectById",false));
this.connect(_1,"_range",dojo.hitch(this,"_updateMapping",true,false));
this.connect(_1,"deselectAll",dojo.hitch(this,"_updateMapping",true,true));
},destroy:function(){
this.reset();
dojo.forEach(this._connects,dojo.disconnect);
delete this._connects;
},connect:function(_5,_6,_7){
var _8=dojo.connect(_5,_6,this,_7);
this._connects.push(_8);
return _8;
},reset:function(){
this._idMap=[];
this._selectedById={};
this._trustSelection=[];
this._defaultSelected=false;
},_reSelectById:function(_9,_a){
var s=this.selection,g=this.grid;
if(_9&&g._hasIdentity){
var id=g.store.getIdentity(_9);
if(this._selectedById[id]===undefined){
if(!this._trustSelection[_a]){
s.selected[_a]=this._defaultSelected;
}
}else{
s.selected[_a]=this._selectedById[id];
}
this._idMap.push(id);
g.onSelectedById(id,_a,s.selected[_a]);
}
},_selectById:function(_b,_c){
if(this.selection.mode=="none"||!this.grid._hasIdentity){
return;
}
var _d=_c;
if(typeof _c=="number"||typeof _c=="string"){
var _e=this.grid._by_idx[_c];
_d=_e&&_e.item;
}
if(_d){
var id=this.grid.store.getIdentity(_d);
this._selectedById[id]=!!_b;
}else{
this._trustSelection[_c]=true;
}
},onSelectedById:function(id,_f,_10){
},_updateMapping:function(_11,_12){
var s=this.selection,g=this.grid,_13=0,_14=0,i,id;
for(i=g.rowCount-1;i>=0;--i){
if(!g._by_idx[i]){
++_14;
_13+=s.selected[i]?1:-1;
}else{
id=g._by_idx[i].idty;
if(id&&(_11||this._selectedById[id]===undefined)){
this._selectedById[id]=!!s.selected[i];
}
}
}
if(_14){
this._defaultSelected=_13>0;
}
if(_12&&!g.usingPagination){
for(i=this._idMap.length;i>=0;--i){
this._selectedById[this._idMap[i]]=false;
}
}
}});
}
