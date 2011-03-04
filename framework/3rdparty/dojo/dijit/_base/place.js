/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dijit._base.place"]){
dojo._hasResource["dijit._base.place"]=true;
dojo.provide("dijit._base.place");
dojo.require("dojo.window");
dojo.require("dojo.AdapterRegistry");
dijit.getViewport=function(){
return dojo.window.getBox();
};
dijit.placeOnScreen=function(_1,_2,_3,_4){
var _5=dojo.map(_3,function(_6){
var c={corner:_6,pos:{x:_2.x,y:_2.y}};
if(_4){
c.pos.x+=_6.charAt(1)=="L"?_4.x:-_4.x;
c.pos.y+=_6.charAt(0)=="T"?_4.y:-_4.y;
}
return c;
});
return dijit._place(_1,_5);
};
dijit._place=function(_7,_8,_9,_a){
var _b=dojo.window.getBox();
if(!_7.parentNode||String(_7.parentNode.tagName).toLowerCase()!="body"){
dojo.body().appendChild(_7);
}
var _c=null;
dojo.some(_8,function(_d){
var _e=_d.corner;
var _f=_d.pos;
var _10=0;
var _11={w:_e.charAt(1)=="L"?(_b.l+_b.w)-_f.x:_f.x-_b.l,h:_e.charAt(1)=="T"?(_b.t+_b.h)-_f.y:_f.y-_b.t};
if(_9){
var res=_9(_7,_d.aroundCorner,_e,_11,_a);
_10=typeof res=="undefined"?0:res;
}
var _12=_7.style;
var _13=_12.display;
var _14=_12.visibility;
_12.visibility="hidden";
_12.display="";
var mb=dojo.marginBox(_7);
_12.display=_13;
_12.visibility=_14;
var _15=Math.max(_b.l,_e.charAt(1)=="L"?_f.x:(_f.x-mb.w)),_16=Math.max(_b.t,_e.charAt(0)=="T"?_f.y:(_f.y-mb.h)),_17=Math.min(_b.l+_b.w,_e.charAt(1)=="L"?(_15+mb.w):_f.x),_18=Math.min(_b.t+_b.h,_e.charAt(0)=="T"?(_16+mb.h):_f.y),_19=_17-_15,_1a=_18-_16;
_10+=(mb.w-_19)+(mb.h-_1a);
if(_c==null||_10<_c.overflow){
_c={corner:_e,aroundCorner:_d.aroundCorner,x:_15,y:_16,w:_19,h:_1a,overflow:_10,spaceAvailable:_11};
}
return !_10;
});
if(_c.overflow&&_9){
_9(_7,_c.aroundCorner,_c.corner,_c.spaceAvailable,_a);
}
_7.style.left=_c.x+"px";
_7.style.top=_c.y+"px";
return _c;
};
dijit.placeOnScreenAroundNode=function(_1b,_1c,_1d,_1e){
_1c=dojo.byId(_1c);
var _1f=_1c.style.display;
_1c.style.display="";
var _20=dojo.position(_1c,true);
_1c.style.display=_1f;
return dijit._placeOnScreenAroundRect(_1b,_20.x,_20.y,_20.w,_20.h,_1d,_1e);
};
dijit.placeOnScreenAroundRectangle=function(_21,_22,_23,_24){
return dijit._placeOnScreenAroundRect(_21,_22.x,_22.y,_22.width,_22.height,_23,_24);
};
dijit._placeOnScreenAroundRect=function(_25,x,y,_26,_27,_28,_29){
var _2a=[];
for(var _2b in _28){
_2a.push({aroundCorner:_2b,corner:_28[_2b],pos:{x:x+(_2b.charAt(1)=="L"?0:_26),y:y+(_2b.charAt(0)=="T"?0:_27)}});
}
return dijit._place(_25,_2a,_29,{w:_26,h:_27});
};
dijit.placementRegistry=new dojo.AdapterRegistry();
dijit.placementRegistry.register("node",function(n,x){
return typeof x=="object"&&typeof x.offsetWidth!="undefined"&&typeof x.offsetHeight!="undefined";
},dijit.placeOnScreenAroundNode);
dijit.placementRegistry.register("rect",function(n,x){
return typeof x=="object"&&"x" in x&&"y" in x&&"width" in x&&"height" in x;
},dijit.placeOnScreenAroundRectangle);
dijit.placeOnScreenAroundElement=function(_2c,_2d,_2e,_2f){
return dijit.placementRegistry.match.apply(dijit.placementRegistry,arguments);
};
dijit.getPopupAroundAlignment=function(_30,_31){
var _32={};
dojo.forEach(_30,function(pos){
switch(pos){
case "after":
_32[_31?"BR":"BL"]=_31?"BL":"BR";
break;
case "before":
_32[_31?"BL":"BR"]=_31?"BR":"BL";
break;
case "below-alt":
_31=!_31;
case "below":
_32[_31?"BL":"BR"]=_31?"TL":"TR";
_32[_31?"BR":"BL"]=_31?"TR":"TL";
break;
case "above-alt":
_31=!_31;
case "above":
default:
_32[_31?"TL":"TR"]=_31?"BL":"BR";
_32[_31?"TR":"TL"]=_31?"BR":"BL";
break;
}
});
return _32;
};
}
