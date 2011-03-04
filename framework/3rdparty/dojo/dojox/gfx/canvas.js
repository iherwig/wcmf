/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.gfx.canvas"]){
dojo._hasResource["dojox.gfx.canvas"]=true;
dojo.provide("dojox.gfx.canvas");
dojo.require("dojox.gfx._base");
dojo.require("dojox.gfx.shape");
dojo.require("dojox.gfx.path");
dojo.require("dojox.gfx.arc");
dojo.require("dojox.gfx.decompose");
dojo.experimental("dojox.gfx.canvas");
(function(){
var d=dojo,g=dojox.gfx,gs=g.shape,ga=g.arc,m=g.matrix,mp=m.multiplyPoint,pi=Math.PI,_1=2*pi,_2=pi/2,_3=null;
d.extend(g.Shape,{_render:function(_4){
_4.save();
this._renderTransform(_4);
this._renderShape(_4);
this._renderFill(_4,true);
this._renderStroke(_4,true);
_4.restore();
},_renderTransform:function(_5){
if("canvasTransform" in this){
var t=this.canvasTransform;
_5.translate(t.dx,t.dy);
_5.rotate(t.angle2);
_5.scale(t.sx,t.sy);
_5.rotate(t.angle1);
}
},_renderShape:function(_6){
},_renderFill:function(_7,_8){
if("canvasFill" in this){
var fs=this.fillStyle;
if("canvasFillImage" in this){
var w=fs.width,h=fs.height,iw=this.canvasFillImage.width,ih=this.canvasFillImage.height,sx=w==iw?1:w/iw,sy=h==ih?1:h/ih,s=Math.min(sx,sy),dx=(w-s*iw)/2,dy=(h-s*ih)/2;
_3.width=w;
_3.height=h;
var _9=_3.getContext("2d");
_9.clearRect(0,0,w,h);
_9.drawImage(this.canvasFillImage,0,0,iw,ih,dx,dy,s*iw,s*ih);
this.canvasFill=_7.createPattern(_3,"repeat");
delete this.canvasFillImage;
}
_7.fillStyle=this.canvasFill;
if(_8){
if(fs.type==="pattern"&&(fs.x!==0||fs.y!==0)){
_7.translate(fs.x,fs.y);
}
_7.fill();
}
}else{
_7.fillStyle="rgba(0,0,0,0.0)";
}
},_renderStroke:function(_a,_b){
var s=this.strokeStyle;
if(s){
_a.strokeStyle=s.color.toString();
_a.lineWidth=s.width;
_a.lineCap=s.cap;
if(typeof s.join=="number"){
_a.lineJoin="miter";
_a.miterLimit=s.join;
}else{
_a.lineJoin=s.join;
}
if(_b){
_a.stroke();
}
}else{
if(!_b){
_a.strokeStyle="rgba(0,0,0,0.0)";
}
}
},getEventSource:function(){
return null;
},connect:function(){
},disconnect:function(){
}});
var _c=function(_d,_e,_f){
var old=_d.prototype[_e];
_d.prototype[_e]=_f?function(){
this.surface.makeDirty();
old.apply(this,arguments);
_f.call(this);
return this;
}:function(){
this.surface.makeDirty();
return old.apply(this,arguments);
};
};
_c(g.Shape,"setTransform",function(){
if(this.matrix){
this.canvasTransform=g.decompose(this.matrix);
}else{
delete this.canvasTransform;
}
});
_c(g.Shape,"setFill",function(){
var fs=this.fillStyle,f;
if(fs){
if(typeof (fs)=="object"&&"type" in fs){
var ctx=this.surface.rawNode.getContext("2d");
switch(fs.type){
case "linear":
case "radial":
f=fs.type=="linear"?ctx.createLinearGradient(fs.x1,fs.y1,fs.x2,fs.y2):ctx.createRadialGradient(fs.cx,fs.cy,0,fs.cx,fs.cy,fs.r);
d.forEach(fs.colors,function(_10){
f.addColorStop(_10.offset,g.normalizeColor(_10.color).toString());
});
break;
case "pattern":
if(!_3){
_3=document.createElement("canvas");
}
var img=new Image();
this.surface.downloadImage(img,fs.src);
this.canvasFillImage=img;
}
}else{
f=fs.toString();
}
this.canvasFill=f;
}else{
delete this.canvasFill;
}
});
_c(g.Shape,"setStroke");
_c(g.Shape,"setShape");
dojo.declare("dojox.gfx.Group",g.Shape,{constructor:function(){
gs.Container._init.call(this);
},_render:function(ctx){
ctx.save();
this._renderTransform(ctx);
for(var i=0;i<this.children.length;++i){
this.children[i]._render(ctx);
}
ctx.restore();
}});
dojo.declare("dojox.gfx.Rect",gs.Rect,{_renderShape:function(ctx){
var s=this.shape,r=Math.min(s.r,s.height/2,s.width/2),xl=s.x,xr=xl+s.width,yt=s.y,yb=yt+s.height,xl2=xl+r,xr2=xr-r,yt2=yt+r,yb2=yb-r;
ctx.beginPath();
ctx.moveTo(xl2,yt);
if(r){
ctx.arc(xr2,yt2,r,-_2,0,false);
ctx.arc(xr2,yb2,r,0,_2,false);
ctx.arc(xl2,yb2,r,_2,pi,false);
ctx.arc(xl2,yt2,r,pi,pi+_2,false);
}else{
ctx.lineTo(xr2,yt);
ctx.lineTo(xr,yb2);
ctx.lineTo(xl2,yb);
ctx.lineTo(xl,yt2);
}
ctx.closePath();
}});
var _11=[];
(function(){
var u=ga.curvePI4;
_11.push(u.s,u.c1,u.c2,u.e);
for(var a=45;a<360;a+=45){
var r=m.rotateg(a);
_11.push(mp(r,u.c1),mp(r,u.c2),mp(r,u.e));
}
})();
dojo.declare("dojox.gfx.Ellipse",gs.Ellipse,{setShape:function(){
g.Ellipse.superclass.setShape.apply(this,arguments);
var s=this.shape,t,c1,c2,r=[],M=m.normalize([m.translate(s.cx,s.cy),m.scale(s.rx,s.ry)]);
t=mp(M,_11[0]);
r.push([t.x,t.y]);
for(var i=1;i<_11.length;i+=3){
c1=mp(M,_11[i]);
c2=mp(M,_11[i+1]);
t=mp(M,_11[i+2]);
r.push([c1.x,c1.y,c2.x,c2.y,t.x,t.y]);
}
this.canvasEllipse=r;
return this;
},_renderShape:function(ctx){
var r=this.canvasEllipse;
ctx.beginPath();
ctx.moveTo.apply(ctx,r[0]);
for(var i=1;i<r.length;++i){
ctx.bezierCurveTo.apply(ctx,r[i]);
}
ctx.closePath();
}});
dojo.declare("dojox.gfx.Circle",gs.Circle,{_renderShape:function(ctx){
var s=this.shape;
ctx.beginPath();
ctx.arc(s.cx,s.cy,s.r,0,_1,1);
}});
dojo.declare("dojox.gfx.Line",gs.Line,{_renderShape:function(ctx){
var s=this.shape;
ctx.beginPath();
ctx.moveTo(s.x1,s.y1);
ctx.lineTo(s.x2,s.y2);
}});
dojo.declare("dojox.gfx.Polyline",gs.Polyline,{setShape:function(){
g.Polyline.superclass.setShape.apply(this,arguments);
var p=this.shape.points,f=p[0],r=[],c,i;
if(p.length){
if(typeof f=="number"){
r.push(f,p[1]);
i=2;
}else{
r.push(f.x,f.y);
i=1;
}
for(;i<p.length;++i){
c=p[i];
if(typeof c=="number"){
r.push(c,p[++i]);
}else{
r.push(c.x,c.y);
}
}
}
this.canvasPolyline=r;
return this;
},_renderShape:function(ctx){
var p=this.canvasPolyline;
if(p.length){
ctx.beginPath();
ctx.moveTo(p[0],p[1]);
for(var i=2;i<p.length;i+=2){
ctx.lineTo(p[i],p[i+1]);
}
}
}});
dojo.declare("dojox.gfx.Image",gs.Image,{setShape:function(){
g.Image.superclass.setShape.apply(this,arguments);
var img=new Image();
this.surface.downloadImage(img,this.shape.src);
this.canvasImage=img;
return this;
},_renderShape:function(ctx){
var s=this.shape;
ctx.drawImage(this.canvasImage,s.x,s.y,s.width,s.height);
}});
var _12=typeof (document.createElement("canvas").getContext("2d").fillText)=="function";
dojo.declare("dojox.gfx.Text",gs.Text,{_setFont:function(){
if(this.fontStyle){
this.canvasFont=dojox.gfx.makeFontString(this.fontStyle);
}else{
delete this.canvasFont;
}
},getTextWidth:function(){
if(!_12){
return 0;
}
var s=this.shape,w=0,ctx;
if(s.text&&s.text.length>0){
ctx=this.surface.rawNode.getContext("2d");
ctx.save();
this._renderTransform(ctx);
this._renderFill(ctx,false);
this._renderStroke(ctx,false);
if(this.canvasFont){
ctx.font=this.canvasFont;
}
w=ctx.measureText(s.text).width;
ctx.restore();
}
return w;
},_render:function(ctx){
ctx.save();
this._renderTransform(ctx);
this._renderFill(ctx,false);
this._renderStroke(ctx,false);
this._renderShape(ctx);
ctx.restore();
},_renderShape:function(ctx){
if(!_12){
return;
}
var ta,s=this.shape;
if(!s.text||s.text.length==0){
return;
}
ta=s.align==="middle"?"center":s.align;
ctx.textAlign=ta;
if(this.canvasFont){
ctx.font=this.canvasFont;
}
if(this.canvasFill){
ctx.fillText(s.text,s.x,s.y);
}
if(this.strokeStyle){
ctx.beginPath();
ctx.strokeText(s.text,s.x,s.y);
ctx.closePath();
}
}});
_c(g.Text,"setFont");
var _13={M:"_moveToA",m:"_moveToR",L:"_lineToA",l:"_lineToR",H:"_hLineToA",h:"_hLineToR",V:"_vLineToA",v:"_vLineToR",C:"_curveToA",c:"_curveToR",S:"_smoothCurveToA",s:"_smoothCurveToR",Q:"_qCurveToA",q:"_qCurveToR",T:"_qSmoothCurveToA",t:"_qSmoothCurveToR",A:"_arcTo",a:"_arcTo",Z:"_closePath",z:"_closePath"};
dojo.declare("dojox.gfx.Path",g.path.Path,{constructor:function(){
this.lastControl={};
},setShape:function(){
this.canvasPath=[];
return g.Path.superclass.setShape.apply(this,arguments);
},_updateWithSegment:function(_14){
var _15=d.clone(this.last);
this[_13[_14.action]](this.canvasPath,_14.action,_14.args);
this.last=_15;
g.Path.superclass._updateWithSegment.apply(this,arguments);
},_renderShape:function(ctx){
var r=this.canvasPath;
ctx.beginPath();
for(var i=0;i<r.length;i+=2){
ctx[r[i]].apply(ctx,r[i+1]);
}
},_moveToA:function(_16,_17,_18){
_16.push("moveTo",[_18[0],_18[1]]);
for(var i=2;i<_18.length;i+=2){
_16.push("lineTo",[_18[i],_18[i+1]]);
}
this.last.x=_18[_18.length-2];
this.last.y=_18[_18.length-1];
this.lastControl={};
},_moveToR:function(_19,_1a,_1b){
if("x" in this.last){
_19.push("moveTo",[this.last.x+=_1b[0],this.last.y+=_1b[1]]);
}else{
_19.push("moveTo",[this.last.x=_1b[0],this.last.y=_1b[1]]);
}
for(var i=2;i<_1b.length;i+=2){
_19.push("lineTo",[this.last.x+=_1b[i],this.last.y+=_1b[i+1]]);
}
this.lastControl={};
},_lineToA:function(_1c,_1d,_1e){
for(var i=0;i<_1e.length;i+=2){
_1c.push("lineTo",[_1e[i],_1e[i+1]]);
}
this.last.x=_1e[_1e.length-2];
this.last.y=_1e[_1e.length-1];
this.lastControl={};
},_lineToR:function(_1f,_20,_21){
for(var i=0;i<_21.length;i+=2){
_1f.push("lineTo",[this.last.x+=_21[i],this.last.y+=_21[i+1]]);
}
this.lastControl={};
},_hLineToA:function(_22,_23,_24){
for(var i=0;i<_24.length;++i){
_22.push("lineTo",[_24[i],this.last.y]);
}
this.last.x=_24[_24.length-1];
this.lastControl={};
},_hLineToR:function(_25,_26,_27){
for(var i=0;i<_27.length;++i){
_25.push("lineTo",[this.last.x+=_27[i],this.last.y]);
}
this.lastControl={};
},_vLineToA:function(_28,_29,_2a){
for(var i=0;i<_2a.length;++i){
_28.push("lineTo",[this.last.x,_2a[i]]);
}
this.last.y=_2a[_2a.length-1];
this.lastControl={};
},_vLineToR:function(_2b,_2c,_2d){
for(var i=0;i<_2d.length;++i){
_2b.push("lineTo",[this.last.x,this.last.y+=_2d[i]]);
}
this.lastControl={};
},_curveToA:function(_2e,_2f,_30){
for(var i=0;i<_30.length;i+=6){
_2e.push("bezierCurveTo",_30.slice(i,i+6));
}
this.last.x=_30[_30.length-2];
this.last.y=_30[_30.length-1];
this.lastControl.x=_30[_30.length-4];
this.lastControl.y=_30[_30.length-3];
this.lastControl.type="C";
},_curveToR:function(_31,_32,_33){
for(var i=0;i<_33.length;i+=6){
_31.push("bezierCurveTo",[this.last.x+_33[i],this.last.y+_33[i+1],this.lastControl.x=this.last.x+_33[i+2],this.lastControl.y=this.last.y+_33[i+3],this.last.x+_33[i+4],this.last.y+_33[i+5]]);
this.last.x+=_33[i+4];
this.last.y+=_33[i+5];
}
this.lastControl.type="C";
},_smoothCurveToA:function(_34,_35,_36){
for(var i=0;i<_36.length;i+=4){
var _37=this.lastControl.type=="C";
_34.push("bezierCurveTo",[_37?2*this.last.x-this.lastControl.x:this.last.x,_37?2*this.last.y-this.lastControl.y:this.last.y,_36[i],_36[i+1],_36[i+2],_36[i+3]]);
this.lastControl.x=_36[i];
this.lastControl.y=_36[i+1];
this.lastControl.type="C";
}
this.last.x=_36[_36.length-2];
this.last.y=_36[_36.length-1];
},_smoothCurveToR:function(_38,_39,_3a){
for(var i=0;i<_3a.length;i+=4){
var _3b=this.lastControl.type=="C";
_38.push("bezierCurveTo",[_3b?2*this.last.x-this.lastControl.x:this.last.x,_3b?2*this.last.y-this.lastControl.y:this.last.y,this.last.x+_3a[i],this.last.y+_3a[i+1],this.last.x+_3a[i+2],this.last.y+_3a[i+3]]);
this.lastControl.x=this.last.x+_3a[i];
this.lastControl.y=this.last.y+_3a[i+1];
this.lastControl.type="C";
this.last.x+=_3a[i+2];
this.last.y+=_3a[i+3];
}
},_qCurveToA:function(_3c,_3d,_3e){
for(var i=0;i<_3e.length;i+=4){
_3c.push("quadraticCurveTo",_3e.slice(i,i+4));
}
this.last.x=_3e[_3e.length-2];
this.last.y=_3e[_3e.length-1];
this.lastControl.x=_3e[_3e.length-4];
this.lastControl.y=_3e[_3e.length-3];
this.lastControl.type="Q";
},_qCurveToR:function(_3f,_40,_41){
for(var i=0;i<_41.length;i+=4){
_3f.push("quadraticCurveTo",[this.lastControl.x=this.last.x+_41[i],this.lastControl.y=this.last.y+_41[i+1],this.last.x+_41[i+2],this.last.y+_41[i+3]]);
this.last.x+=_41[i+2];
this.last.y+=_41[i+3];
}
this.lastControl.type="Q";
},_qSmoothCurveToA:function(_42,_43,_44){
for(var i=0;i<_44.length;i+=2){
var _45=this.lastControl.type=="Q";
_42.push("quadraticCurveTo",[this.lastControl.x=_45?2*this.last.x-this.lastControl.x:this.last.x,this.lastControl.y=_45?2*this.last.y-this.lastControl.y:this.last.y,_44[i],_44[i+1]]);
this.lastControl.type="Q";
}
this.last.x=_44[_44.length-2];
this.last.y=_44[_44.length-1];
},_qSmoothCurveToR:function(_46,_47,_48){
for(var i=0;i<_48.length;i+=2){
var _49=this.lastControl.type=="Q";
_46.push("quadraticCurveTo",[this.lastControl.x=_49?2*this.last.x-this.lastControl.x:this.last.x,this.lastControl.y=_49?2*this.last.y-this.lastControl.y:this.last.y,this.last.x+_48[i],this.last.y+_48[i+1]]);
this.lastControl.type="Q";
this.last.x+=_48[i];
this.last.y+=_48[i+1];
}
},_arcTo:function(_4a,_4b,_4c){
var _4d=_4b=="a";
for(var i=0;i<_4c.length;i+=7){
var x1=_4c[i+5],y1=_4c[i+6];
if(_4d){
x1+=this.last.x;
y1+=this.last.y;
}
var _4e=ga.arcAsBezier(this.last,_4c[i],_4c[i+1],_4c[i+2],_4c[i+3]?1:0,_4c[i+4]?1:0,x1,y1);
d.forEach(_4e,function(p){
_4a.push("bezierCurveTo",p);
});
this.last.x=x1;
this.last.y=y1;
}
this.lastControl={};
},_closePath:function(_4f,_50,_51){
_4f.push("closePath",[]);
this.lastControl={};
}});
d.forEach(["moveTo","lineTo","hLineTo","vLineTo","curveTo","smoothCurveTo","qCurveTo","qSmoothCurveTo","arcTo","closePath"],function(_52){
_c(g.Path,_52);
});
dojo.declare("dojox.gfx.TextPath",g.path.TextPath,{_renderShape:function(ctx){
var s=this.shape;
}});
dojo.declare("dojox.gfx.Surface",gs.Surface,{constructor:function(){
gs.Container._init.call(this);
this.pendingImageCount=0;
this.makeDirty();
},setDimensions:function(_53,_54){
this.width=g.normalizedLength(_53);
this.height=g.normalizedLength(_54);
if(!this.rawNode){
return this;
}
this.rawNode.width=_53;
this.rawNode.height=_54;
this.makeDirty();
return this;
},getDimensions:function(){
return this.rawNode?{width:this.rawNode.width,height:this.rawNode.height}:null;
},_render:function(){
if(this.pendingImageCount){
return;
}
var ctx=this.rawNode.getContext("2d");
ctx.save();
ctx.clearRect(0,0,this.rawNode.width,this.rawNode.height);
for(var i=0;i<this.children.length;++i){
this.children[i]._render(ctx);
}
ctx.restore();
if("pendingRender" in this){
clearTimeout(this.pendingRender);
delete this.pendingRender;
}
},makeDirty:function(){
if(!this.pendingImagesCount&&!("pendingRender" in this)){
this.pendingRender=setTimeout(d.hitch(this,this._render),0);
}
},downloadImage:function(img,url){
var _55=d.hitch(this,this.onImageLoad);
if(!this.pendingImageCount++&&"pendingRender" in this){
clearTimeout(this.pendingRender);
delete this.pendingRender;
}
img.onload=_55;
img.onerror=_55;
img.onabort=_55;
img.src=url;
},onImageLoad:function(){
if(!--this.pendingImageCount){
this._render();
}
},getEventSource:function(){
return null;
},connect:function(){
},disconnect:function(){
}});
g.createSurface=function(_56,_57,_58){
if(!_57&&!_58){
var pos=d.position(_56);
_57=_57||pos.w;
_58=_58||pos.h;
}
if(typeof _57=="number"){
_57=_57+"px";
}
if(typeof _58=="number"){
_58=_58+"px";
}
var s=new g.Surface(),p=d.byId(_56),c=p.ownerDocument.createElement("canvas");
c.width=dojox.gfx.normalizedLength(_57);
c.height=dojox.gfx.normalizedLength(_58);
p.appendChild(c);
s.rawNode=c;
s._parent=p;
s.surface=s;
return s;
};
var C=gs.Container,_59={add:function(_5a){
this.surface.makeDirty();
return C.add.apply(this,arguments);
},remove:function(_5b,_5c){
this.surface.makeDirty();
return C.remove.apply(this,arguments);
},clear:function(){
this.surface.makeDirty();
return C.clear.apply(this,arguments);
},_moveChildToFront:function(_5d){
this.surface.makeDirty();
return C._moveChildToFront.apply(this,arguments);
},_moveChildToBack:function(_5e){
this.surface.makeDirty();
return C._moveChildToBack.apply(this,arguments);
}};
d.mixin(gs.Creator,{createObject:function(_5f,_60){
var _61=new _5f();
_61.surface=this.surface;
_61.setShape(_60);
this.add(_61);
return _61;
}});
d.extend(g.Group,_59);
d.extend(g.Group,gs.Creator);
d.extend(g.Surface,_59);
d.extend(g.Surface,gs.Creator);
})();
}
