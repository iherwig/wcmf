/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.grid.enhanced.plugins.Cookie"]){
dojo._hasResource["dojox.grid.enhanced.plugins.Cookie"]=true;
dojo.provide("dojox.grid.enhanced.plugins.Cookie");
dojo.require("dojox.grid.enhanced._Plugin");
dojo.require("dojo.cookie");
dojo.require("dojox.grid._RowSelector");
dojo.require("dojox.grid.cells._base");
(function(){
var _1=function(_2){
return window.location+"/"+_2.id;
};
var _3=function(_4){
var _5=[];
if(!dojo.isArray(_4)){
_4=[_4];
}
dojo.forEach(_4,function(_6){
if(dojo.isArray(_6)){
_6={"cells":_6};
}
var _7=_6.rows||_6.cells;
if(dojo.isArray(_7)){
if(!dojo.isArray(_7[0])){
_7=[_7];
}
dojo.forEach(_7,function(_8){
if(dojo.isArray(_8)){
dojo.forEach(_8,function(_9){
_5.push(_9);
});
}
});
}
});
return _5;
};
var _a=function(_b,_c){
if(dojo.isArray(_b)){
var _d=_c._setStructureAttr;
_c._setStructureAttr=function(_e){
if(!_c._colWidthLoaded){
_c._colWidthLoaded=true;
var _f=_3(_e);
for(var i=_f.length-1;i>=0;--i){
if(typeof _b[i]=="number"){
_f[i].width=_b[i]+"px";
}
}
}
_d.call(_c,_e);
_c._setStructureAttr=_d;
};
}
};
var _10=function(_11){
return dojo.map(dojo.filter(_11.layout.cells,function(_12){
return !(_12.isRowSelector||_12 instanceof dojox.grid.cells.RowIndex);
}),function(_13){
return dojo[dojo.isWebKit?"marginBox":"contentBox"](_13.getHeaderNode()).w;
});
};
var _14=function(_15,_16){
if(_15&&dojo.every(_15,function(_17){
return dojo.isArray(_17)&&dojo.every(_17,function(_18){
return dojo.isArray(_18)&&_18.length>0;
});
})){
var _19=_16._setStructureAttr;
_16._setStructureAttr=function(_1a){
if(!_16._colOrderLoaded){
_16._colOrderLoaded=true;
_16._setStructureAttr=_19;
_1a=dojo.clone(_1a);
var _1b=_3(_1a);
dojo.forEach(dojo.isArray(_1a)?_1a:[_1a],function(_1c,_1d){
var _1e=_1c;
if(dojo.isArray(_1c)){
_1c.splice(0,_1c.length);
}else{
delete _1c.rows;
_1e=_1c.cells=[];
}
dojo.forEach(_15[_1d],function(_1f){
dojo.forEach(_1f,function(_20){
var i,_21;
for(i=0;i<_1b.length;++i){
_21=_1b[i];
if(dojo.toJson({"name":_21.name,"field":_21.field})==dojo.toJson(_20)){
break;
}
}
if(i<_1b.length){
_1e.push(_21);
}
});
});
});
}
_19.call(_16,_1a);
};
}
};
var _22=function(_23){
var _24=dojo.map(dojo.filter(_23.views.views,function(_25){
return !(_25 instanceof dojox.grid._RowSelector);
}),function(_26){
return dojo.map(_26.structure.cells,function(_27){
return dojo.map(dojo.filter(_27,function(_28){
return !(_28.isRowSelector||_28 instanceof dojox.grid.cells.RowIndex);
}),function(_29){
return {"name":_29.name,"field":_29.field};
});
});
});
return _24;
};
var _2a=function(_2b,_2c){
try{
if(dojo.isObject(_2b)){
_2c.setSortIndex(_2b.idx,_2b.asc);
}
}
catch(e){
}
};
var _2d=function(_2e){
return {idx:_2e.getSortIndex(),asc:_2e.getSortAsc()};
};
if(!dojo.isIE){
dojo.addOnWindowUnload(function(){
dojo.forEach(dijit.findWidgets(dojo.body()),function(_2f){
if(_2f instanceof dojox.grid.EnhancedGrid&&!_2f._destroyed){
_2f.destroyRecursive();
}
});
});
}
dojo.declare("dojox.grid.enhanced.plugins.Cookie",dojox.grid.enhanced._Plugin,{name:"cookie",_cookieEnabled:true,constructor:function(_30,_31){
this.grid=_30;
_31=(_31&&dojo.isObject(_31))?_31:{};
this.cookieProps=_31.cookieProps;
this._cookieHandlers=[];
this._mixinGrid();
this.addCookieHandler({name:"columnWidth",onLoad:_a,onSave:_10});
this.addCookieHandler({name:"columnOrder",onLoad:_14,onSave:_22});
this.addCookieHandler({name:"sortOrder",onLoad:_2a,onSave:_2d});
dojo.forEach(this._cookieHandlers,function(_32){
if(_31[_32.name]===false){
_32.enable=false;
}
},this);
},destroy:function(){
this._saveCookie();
this._cookieHandlers=null;
this.inherited(arguments);
},_mixinGrid:function(){
var g=this.grid;
g.addCookieHandler=dojo.hitch(this,"addCookieHandler");
g.removeCookie=dojo.hitch(this,"removeCookie");
g.setCookieEnabled=dojo.hitch(this,"setCookieEnabled");
g.getCookieEnabled=dojo.hitch(this,"getCookieEnabled");
},_saveCookie:function(){
if(this.getCookieEnabled()){
var _33={},chs=this._cookieHandlers,_34=this.cookieProps,_35=_1(this.grid);
for(var i=chs.length-1;i>=0;--i){
if(chs[i].enabled){
_33[chs[i].name]=chs[i].onSave(this.grid);
}
}
_34=dojo.isObject(this.cookieProps)?this.cookieProps:{};
dojo.cookie(_35,dojo.toJson(_33),_34);
}else{
this.removeCookie();
}
},onPreInit:function(){
var _36=this.grid,chs=this._cookieHandlers,_37=_1(_36),_38=dojo.cookie(_37);
if(_38){
_38=dojo.fromJson(_38);
for(var i=0;i<chs.length;++i){
if(chs[i].name in _38&&chs[i].enabled){
chs[i].onLoad(_38[chs[i].name],_36);
}
}
}
this._cookie=_38||{};
this._cookieStartedup=true;
},addCookieHandler:function(_39){
if(_39.name){
var _3a=function(){
};
_39.onLoad=_39.onLoad||_3a;
_39.onSave=_39.onSave||_3a;
if(!("enabled" in _39)){
_39.enabled=true;
}
this._cookieHandlers.push(_39);
if(this._cookieStartedup&&_39.name in this._cookie){
_39.onLoad(this._cookie[_39.name],this.grid);
}
}
},removeCookie:function(){
var key=_1(this.grid);
dojo.cookie(key,null,{expires:-1});
},setCookieEnabled:function(_3b,_3c){
if(arguments.length==2){
var chs=this._cookieHandlers;
for(var i=chs.length-1;i>=0;--i){
if(chs[i].name===_3b){
chs[i].enabled=!!_3c;
}
}
}else{
this._cookieEnabled=!!_3b;
if(!this._cookieEnabled){
this.removeCookie();
}
}
},getCookieEnabled:function(_3d){
if(dojo.isString(_3d)){
var chs=this._cookieHandlers;
for(var i=chs.length-1;i>=0;--i){
if(chs[i].name==_3d){
return chs[i].enabled;
}
}
return false;
}
return this._cookieEnabled;
}});
dojox.grid.EnhancedGrid.registerPlugin(dojox.grid.enhanced.plugins.Cookie,{"preInit":true});
})();
}
