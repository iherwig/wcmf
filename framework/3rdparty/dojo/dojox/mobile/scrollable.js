/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.mobile.scrollable"]){
dojo._hasResource["dojox.mobile.scrollable"]=true;
if(typeof dojo!="undefined"&&dojo.provide){
dojo.provide("dojox.mobile.scrollable");
}else{
dojo={doc:document,global:window,isWebKit:navigator.userAgent.indexOf("WebKit")!=-1};
dojox={mobile:{}};
}
dojox.mobile.scrollable=function(){
this.fixedHeaderHeight=0;
this.fixedFooterHeight=0;
this.isLocalFooter=false;
this.scrollBar=true;
this.scrollDir="v";
this.weight=0.6;
this.fadeScrollBar=true;
this.disableFlashScrollBar=false;
this.init=function(_1){
if(_1){
for(var p in _1){
if(_1.hasOwnProperty(p)){
this[p]=((p=="domNode"||p=="containerNode")&&typeof _1[p]=="string")?dojo.doc.getElementById(_1[p]):_1[p];
}
}
}
this._v=(this.scrollDir.indexOf("v")!=-1);
this._h=(this.scrollDir.indexOf("h")!=-1);
this._f=(this.scrollDir=="f");
this._ch=[];
this._ch.push(dojo.connect(this.containerNode,dojox.mobile.hasTouch?"touchstart":"onmousedown",this,"onTouchStart"));
if(dojo.isWebKit){
this._ch.push(dojo.connect(this.containerNode,"webkitAnimationEnd",this,"onFlickAnimationEnd"));
this._ch.push(dojo.connect(this.containerNode,"webkitAnimationStart",this,"onFlickAnimationStart"));
}
this.containerNode.style.paddingTop=this.fixedHeaderHeight+"px";
if(this.isLocalFooter){
this.containerNode.style.paddingBottom=this.fixedFooterHeight+"px";
}
if(dojo.global.onorientationchange!==undefined){
this._ch.push(dojo.connect(dojo.global,"onorientationchange",this,"resizeView"));
}else{
this._ch.push(dojo.connect(dojo.global,"onresize",this,"resizeView"));
}
this.resizeView();
var _2=this;
setTimeout(function(){
_2.flashScrollBar();
},600);
};
this.cleanup=function(){
for(var i=0;i<this._ch.length;i++){
dojo.disconnect(this._ch[i]);
}
this._ch=null;
};
this.resizeView=function(e){
var c=0;
var h=this.isLocalFooter?0:this.fixedFooterHeight;
var _3=this;
var id=setInterval(function(){
_3.domNode.style.height=(dojo.global.innerHeight||dojo.doc.documentElement.clientHeight)-h+"px";
if(c++>=4){
clearInterval(id);
}
},300);
};
this.onFlickAnimationStart=function(e){
dojo.stopEvent(e);
};
this.onFlickAnimationEnd=function(e){
if(e&&e.srcElement){
dojo.stopEvent(e);
}
this.containerNode.className=this.containerNode.className.replace(/\s*mblScrollableScrollTo/,"");
if(this._bounce){
var _4=this;
var _5=_4._bounce;
setTimeout(function(){
_4.slideTo(_5,0.3,"ease-out");
},0);
_4._bounce=undefined;
}else{
this.stopScrollBar();
this.removeCover();
}
};
this.onTouchStart=function(e){
if(this.containerNode.className.indexOf("mblScrollableScrollTo")!=-1){
this.scrollTo(this.getPos());
this.containerNode.className=this.containerNode.className.replace(/\s*mblScrollableScrollTo/,"");
this._aborted=true;
}else{
this._aborted=false;
}
this.touchStartX=e.touches?e.touches[0].pageX:e.clientX;
this.touchStartY=e.touches?e.touches[0].pageY:e.clientY;
this.startTime=(new Date()).getTime();
this.startPos=this.getPos();
this._dim=this.getDim();
this._time=[0];
this._posX=[this.touchStartX];
this._posY=[this.touchStartY];
this._conn=[];
this._conn.push(dojo.connect(dojo.doc,dojox.mobile.hasTouch?"touchmove":"onmousemove",this,"onTouchMove"));
this._conn.push(dojo.connect(dojo.doc,dojox.mobile.hasTouch?"touchend":"onmouseup",this,"onTouchEnd"));
if(e.target.nodeType!=1||(e.target.tagName!="SELECT"&&e.target.tagName!="INPUT")){
dojo.stopEvent(e);
}
};
this.onTouchMove=function(e){
var x=e.touches?e.touches[0].pageX:e.clientX;
var y=e.touches?e.touches[0].pageY:e.clientY;
var dx=x-this.touchStartX;
var dy=y-this.touchStartY;
var to={x:this.startPos.x+dx,y:this.startPos.y+dy};
var _6=this._dim;
this.addCover();
this.showScrollBar();
this.updateScrollBar(to);
var _7=this.weight;
if(this._v){
if(to.y>0){
to.y=Math.round(to.y*_7);
}else{
if(to.y<-_6.o.h){
if(_6.c.h<_6.v.h){
to.y=Math.round(to.y*_7);
}else{
to.y=-_6.o.h-Math.round((-_6.o.h-to.y)*_7);
}
}
}
}
if(this._h||this._f){
if(to.x>0){
to.x=Math.round(to.x*_7);
}else{
if(to.x<-_6.o.w){
if(_6.c.w<_6.v.w){
to.x=Math.round(to.x*_7);
}else{
to.x=-_6.o.w-Math.round((-_6.o.w-to.x)*_7);
}
}
}
}
this.scrollTo(to);
var _8=10;
if(this._time.length==_8){
this._time.shift();
}
this._time.push((new Date()).getTime()-this.startTime);
if(this._posX.length==_8){
this._posX.shift();
}
this._posX.push(x);
if(this._posY.length==_8){
this._posY.shift();
}
this._posY.push(y);
};
this.onTouchEnd=function(e){
for(var i=0;i<this._conn.length;i++){
dojo.disconnect(this._conn[i]);
}
var n=!this._time?0:this._time.length;
var _9=false;
if(!this._aborted){
if(n<=1){
_9=true;
}else{
if(n==2&&Math.abs(this._posY[1]-this._posY[0])<4){
_9=true;
}
}
}
if(_9){
this.stopScrollBar();
this.removeCover();
if(dojox.mobile.hasTouch){
var _a=e.target;
if(_a.nodeType!=1){
_a=_a.parentNode;
}
var ev=dojo.doc.createEvent("MouseEvents");
ev.initEvent("click",true,true);
_a.dispatchEvent(ev);
}
return;
}
var _b={x:0,y:0};
if(n<2||(new Date()).getTime()-this.startTime-this._time[n-1]>500){
}else{
var dy=this._posY[n-(n>2?2:1)]-this._posY[(n-6)>=0?n-6:0];
var dx=this._posX[n-(n>2?2:1)]-this._posX[(n-6)>=0?n-6:0];
var dt=this._time[n-(n>2?2:1)]-this._time[(n-6)>=0?n-6:0];
_b.y=this.calcSpeed(dy,dt);
_b.x=this.calcSpeed(dx,dt);
}
var _c=this.getPos();
var to={};
var _d=this._dim;
if(this._v){
to.y=_c.y+_b.y;
}
if(this._h||this._f){
to.x=_c.x+_b.x;
}
if(this.scrollDir=="v"&&_d.c.h<=_d.v.h){
this.slideTo({y:0},0.3,"ease-out");
return;
}else{
if(this.scrollDir=="h"&&_d.c.w<=_d.v.w){
this.slideTo({x:0},0.3,"ease-out");
return;
}else{
if(this._v&&this._h&&_d.c.h<=_d.v.h&&_d.c.w<=_d.v.w){
this.slideTo({x:0,y:0},0.3,"ease-out");
return;
}
}
}
var _e,_f="ease-out";
var _10={};
if(this._v){
if(to.y>0){
if(_c.y>0){
_e=0.3;
to.y=0;
}else{
to.y=Math.min(to.y,20);
_f="linear";
_10.y=0;
}
}else{
if(-_b.y>_d.o.h-(-_c.y)){
if(_c.y<-_d.o.h){
_e=0.3;
to.y=_d.c.h<=_d.v.h?0:-_d.o.h;
}else{
to.y=Math.max(to.y,-_d.o.h-20);
_f="linear";
_10.y=-_d.o.h;
}
}
}
}
if(this._h||this._f){
if(to.x>0){
if(_c.x>0){
_e=0.3;
to.x=0;
}else{
to.x=Math.min(to.x,20);
_f="linear";
_10.x=0;
}
}else{
if(-_b.x>_d.o.w-(-_c.x)){
if(_c.x<-_d.o.w){
_e=0.3;
to.x=_d.c.w<=_d.v.w?0:-_d.o.w;
}else{
to.x=Math.max(to.x,-_d.o.w-20);
_f="linear";
_10.x=-_d.o.w;
}
}
}
}
this._bounce=(_10.x!==undefined||_10.y!==undefined)?_10:undefined;
if(_e===undefined){
var _11,_12;
if(this._v&&this._h){
_12=Math.sqrt(_b.x+_b.x+_b.y*_b.y);
_11=Math.sqrt(Math.pow(to.y-_c.y,2)+Math.pow(to.x-_c.x,2));
}else{
if(this._v){
_12=_b.y;
_11=to.y-_c.y;
}else{
if(this._h){
_12=_b.x;
_11=to.x-_c.x;
}
}
}
_e=_12!==0?Math.abs(_11/_12):0.01;
}
this.slideTo(to,_e,_f);
this.startScrollBar();
};
this.calcSpeed=function(d,t){
return Math.round(d/t*100)*4;
};
this.scrollTo=function(to){
if(dojo.isWebKit){
this.containerNode.style.webkitTransform=this.makeTranslateStr(to);
}else{
if(this._v){
this.containerNode.style.top=to.y+"px";
}
if(this._h||this._f){
this.containerNode.style.left=to.x+"px";
}
}
};
this.slideTo=function(to,_13,_14){
if(dojo.isWebKit){
this.setKeyframes(this.getPos(),to);
this.containerNode.style.webkitAnimationDuration=_13+"s";
this.containerNode.style.webkitAnimationTimingFunction=_14;
this.containerNode.className+=" mblScrollableScrollTo";
this.scrollTo(to);
}else{
if(dojo.fx&&dojo.fx.easing){
var s=dojo.fx.slideTo({node:this.containerNode,duration:_13*1000,left:to.x,top:to.y,easing:(_14=="ease-out")?dojo.fx.easing.quadOut:dojo.fx.easing.linear}).play();
dojo.connect(s,"onEnd",this,"onFlickAnimationEnd");
}else{
if(typeof to.x=="number"){
this.containerNode.style.left=to.x+"px";
}
if(typeof to.y=="number"){
this.containerNode.style.top=to.y+"px";
}
this.onFlickAnimationEnd();
}
}
};
this.makeTranslateStr=function(to){
var y=this._v&&typeof to.y=="number"?to.y+"px":"0px";
var x=(this._h||this._f)&&typeof to.x=="number"?to.x+"px":"0px";
return dojox.mobile.hasTranslate3d?"translate3d("+x+","+y+",0px)":"translate("+x+","+y+")";
};
this.getPos=function(){
if(dojo.isWebKit){
var m=dojo.doc.defaultView.getComputedStyle(this.containerNode,"")["-webkit-transform"];
if(m&&m.indexOf("matrix")===0){
var arr=m.split(/[,\s\)]+/);
return {y:arr[5]-0,x:arr[4]-0};
}
return {x:0,y:0};
}else{
return {y:this.containerNode.offsetTop,x:this.containerNode.offsetLeft};
}
};
this.getDim=function(){
var d={};
d.c={h:this.containerNode.offsetHeight,w:this.containerNode.offsetWidth};
d.v={h:this.domNode.offsetHeight,w:this.domNode.offsetWidth};
d.d={h:d.v.h-this.fixedHeaderHeight-(this.isLocalFooter?this.fixedFooterHeight:0),w:d.v.w};
d.o={h:d.c.h-d.v.h,w:d.c.w-d.v.w};
return d;
};
this.showScrollBar=function(){
if(!this.scrollBar){
return;
}
var dim=this._dim;
if(this.scrollDir=="v"&&dim.c.h<=dim.v.h){
return;
}
if(this.scrollDir=="h"&&dim.c.w<=dim.v.w){
return;
}
if(this._v&&this._h&&dim.c.h<=dim.v.h&&dim.c.w<=dim.v.w){
return;
}
var _15={opacity:0.6,position:"absolute",backgroundColor:"#606060",fontSize:"1px",webkitBorderRadius:"2px",mozBorderRadius:"2px",webkitTransformOrigin:"0 0",zIndex:2147483647};
if(this._v&&!this._scrollBarV){
if(!this._scrollBarNodeV){
this._scrollBarNodeV=dojo.create("div",null,this.domNode);
dojo.style(this._scrollBarNodeV,_15);
dojo.style(this._scrollBarNodeV,{top:"0px",right:"2px",width:"5px"});
}
this._scrollBarV=this._scrollBarNodeV;
this._scrollBarV.className="";
dojo.style(this._scrollBarV,{"opacity":0.6});
}
if(this._h&&!this._scrollBarH){
if(!this._scrollBarNodeH){
this._scrollBarNodeH=dojo.create("div",null,this.domNode);
dojo.style(this._scrollBarNodeH,_15);
dojo.style(this._scrollBarNodeH,{left:"0px",bottom:(this.isLocalFooter?this.fixedFooterHeight:0)+2+"px",height:"5px"});
}
this._scrollBarH=this._scrollBarNodeH;
this._scrollBarH.className="";
dojo.style(this._scrollBarH,{"opacity":0.6});
}
};
this.hideScrollBar=function(){
var _16;
if(this.fadeScrollBar&&dojo.isWebKit){
if(!dojox.mobile._fadeRule){
var _17=dojo.create("style",null,dojo.doc.getElementsByTagName("head")[0]);
_17.textContent=".mblScrollableFadeOutScrollBar{"+"  -webkit-animation-duration: 1s;"+"  -webkit-animation-name: scrollableViewFadeOutScrollBar;}"+"@-webkit-keyframes scrollableViewFadeOutScrollBar{"+"  from { opacity: 0.6; }"+"  50% { opacity: 0.6; }"+"  to { opacity: 0; }}";
dojox.mobile._fadeRule=_17.sheet.cssRules[1];
}
_16=dojox.mobile._fadeRule;
}
if(!this.scrollBar){
return;
}
if(this._scrollBarV){
dojo.style(this._scrollBarV,{"opacity":0});
this._scrollBarV.className="mblScrollableFadeOutScrollBar";
this._scrollBarV=null;
}
if(this._scrollBarH){
dojo.style(this._scrollBarH,{"opacity":0});
this._scrollBarH.className="mblScrollableFadeOutScrollBar";
this._scrollBarH=null;
}
};
this.startScrollBar=function(){
if(!this.scrollBar){
return;
}
if(!this._scrollBarV&&!this._scrollBarH){
return;
}
if(!this._scrollTimer){
var _18=this;
this._scrollTimer=setInterval(function(){
_18.updateScrollBar(_18.getPos());
},20);
}
};
this.stopScrollBar=function(){
if(!this.scrollBar){
return;
}
if(!this._scrollBarV&&!this._scrollBarH){
return;
}
this.hideScrollBar();
clearInterval(this._scrollTimer);
this._scrollTimer=null;
};
this.updateScrollBar=function(to){
if(!this.scrollBar){
return;
}
if(!this._scrollBarV&&!this._scrollBarH){
return;
}
var dim=this._dim;
if(this._v){
var ch=dim.c.h-this.fixedHeaderHeight;
var _19=Math.round(dim.d.h*dim.d.h/ch);
var top=Math.round((dim.d.h-_19)/(dim.d.h-ch)*to.y);
if(top<0){
_19+=top;
top=0;
}else{
if(top+_19>dim.d.h){
_19-=top+_19-dim.d.h;
}
}
var t=top+this.fixedHeaderHeight+4;
var h=Math.max(_19-8,5);
if(h!=this._scrollBarV._h){
this._scrollBarV.style.height=h+"px";
this._scrollBarV._h=h;
}
if(dojo.isWebKit){
this._scrollBarV.style.webkitTransform=this.makeTranslateStr({y:t});
}else{
this._scrollBarV.style.top=t+"px";
}
}
if(this._h){
var cw=dim.c.w;
var _1a=Math.round(dim.d.w*dim.d.w/cw);
var _1b=Math.round((dim.d.w-_1a)/(dim.d.w-cw)*to.x);
if(_1b<0){
_1a+=_1b;
_1b=0;
}else{
if(_1b+_1a>dim.d.w){
_1a-=_1b+_1a-dim.d.w;
}
}
var l=_1b+4;
var w=Math.max(_1a-8,5);
if(w!=this._scrollBarH._w){
this._scrollBarH.style.width=w+"px";
this._scrollBarH._w=w;
}
if(dojo.isWebKit){
this._scrollBarH.style.webkitTransform=this.makeTranslateStr({x:l});
}else{
this._scrollBarH.style.left=l+"px";
}
}
};
this.flashScrollBar=function(){
if(this.disableFlashScrollBar){
return;
}
this._dim=this.getDim();
if(this._dim.d.h<=0){
return;
}
this.showScrollBar();
this.updateScrollBar(this.getPos());
var _1c=this;
setTimeout(function(){
_1c.hideScrollBar();
},0);
};
this.addCover=function(){
if(!dojox.mobile.hasTouch&&!this.noCover){
if(!this._cover){
this._cover=dojo.create("div",null,dojo.doc.body);
dojo.style(this._cover,{backgroundColor:"#ffff00",opacity:0,position:"absolute",top:"0px",left:"0px",width:"100%",height:"100%",zIndex:2147483647});
this._ch.push(dojo.connect(this._cover,dojox.mobile.hasTouch?"touchstart":"onmousedown",this,"onTouchEnd"));
}else{
this._cover.style.display="";
}
}
this.setSelectable(this.domNode,false);
var sel;
if(dojo.global.getSelection){
sel=dojo.global.getSelection();
sel.collapse(dojo.doc.body,0);
}else{
sel=dojo.doc.selection.createRange();
sel.setEndPoint("EndToStart",sel);
sel.select();
}
};
this.removeCover=function(){
if(!dojox.mobile.hasTouch&&this._cover){
this._cover.style.display="none";
}
this.setSelectable(this.domNode,true);
};
this.setKeyframes=function(_1d,to){
if(!dojox.mobile._rule){
var _1e=dojo.create("style",null,dojo.doc.getElementsByTagName("head")[0]);
_1e.textContent=".mblScrollableScrollTo{-webkit-animation-name: scrollableViewScroll;}"+"@-webkit-keyframes scrollableViewScroll{}";
dojox.mobile._rule=_1e.sheet.cssRules[1];
}
var _1f=dojox.mobile._rule;
if(_1f){
if(_1d){
_1f.deleteRule("from");
_1f.insertRule("from { -webkit-transform: "+this.makeTranslateStr(_1d)+"; }");
}
if(to){
if(to.x===undefined){
to.x=_1d.x;
}
if(to.y===undefined){
to.y=_1d.y;
}
_1f.deleteRule("to");
_1f.insertRule("to { -webkit-transform: "+this.makeTranslateStr(to)+"; }");
}
}
};
this.setSelectable=function(_20,_21){
_20.style.KhtmlUserSelect=_21?"auto":"none";
_20.style.MozUserSelect=_21?"":"none";
_20.onselectstart=_21?null:function(){
return false;
};
_20.unselectable=_21?"":"on";
};
};
(function(){
if(dojo.isWebKit){
var _22=dojo.doc.createElement("div");
_22.style.webkitTransform="translate3d(0px,1px,0px)";
dojo.doc.documentElement.appendChild(_22);
var v=dojo.doc.defaultView.getComputedStyle(_22,"")["-webkit-transform"];
dojox.mobile.hasTranslate3d=v&&v.indexOf("matrix")===0;
dojo.doc.documentElement.removeChild(_22);
dojox.mobile.hasTouch=(typeof dojo.doc.documentElement.ontouchstart!="undefined"&&navigator.appVersion.indexOf("Mobile")!=-1);
}
})();
}
