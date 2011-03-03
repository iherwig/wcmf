/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.charting.Chart"]){
dojo._hasResource["dojox.charting.Chart"]=true;
dojo.provide("dojox.charting.Chart");
dojo.require("dojox.gfx");
dojo.require("dojox.lang.functional");
dojo.require("dojox.lang.functional.fold");
dojo.require("dojox.lang.functional.reversed");
dojo.require("dojox.charting.Theme");
dojo.require("dojox.charting.Series");
dojo.require("dojox.charting.axis2d.common");
(function(){
var df=dojox.lang.functional,dc=dojox.charting,g=dojox.gfx,_1=df.lambda("item.clear()"),_2=df.lambda("item.purgeGroup()"),_3=df.lambda("item.destroy()"),_4=df.lambda("item.dirty = false"),_5=df.lambda("item.dirty = true"),_6=df.lambda("item.name");
dojo.declare("dojox.charting.Chart",null,{constructor:function(_7,_8){
if(!_8){
_8={};
}
this.margins=_8.margins?_8.margins:{l:10,t:10,r:10,b:10};
this.stroke=_8.stroke;
this.fill=_8.fill;
this.delayInMs=_8.delayInMs||200;
this.title=_8.title;
this.titleGap=_8.titleGap;
this.titlePos=_8.titlePos;
this.titleFont=_8.titleFont;
this.titleFontColor=_8.titleFontColor;
this.chartTitle=null;
this.theme=null;
this.axes={};
this.stack=[];
this.plots={};
this.series=[];
this.runs={};
this.dirty=true;
this.coords=null;
this.node=dojo.byId(_7);
var _9=dojo.marginBox(_7);
this.surface=g.createSurface(this.node,_9.w||400,_9.h||300);
},destroy:function(){
dojo.forEach(this.series,_3);
dojo.forEach(this.stack,_3);
df.forIn(this.axes,_3);
if(this.chartTitle&&this.chartTitle.tagName){
dojo.destroy(this.chartTitle);
}
this.surface.destroy();
},getCoords:function(){
if(!this.coords){
this.coords=dojo.coords(this.node,true);
}
return this.coords;
},setTheme:function(_a){
this.theme=_a.clone();
this.dirty=true;
return this;
},addAxis:function(_b,_c){
var _d,_e=_c&&_c.type||"Default";
if(typeof _e=="string"){
if(!dc.axis2d||!dc.axis2d[_e]){
throw Error("Can't find axis: "+_e+" - didn't you forget to dojo"+".require() it?");
}
_d=new dc.axis2d[_e](this,_c);
}else{
_d=new _e(this,_c);
}
_d.name=_b;
_d.dirty=true;
if(_b in this.axes){
this.axes[_b].destroy();
}
this.axes[_b]=_d;
this.dirty=true;
return this;
},getAxis:function(_f){
return this.axes[_f];
},removeAxis:function(_10){
if(_10 in this.axes){
this.axes[_10].destroy();
delete this.axes[_10];
this.dirty=true;
}
return this;
},addPlot:function(_11,_12){
var _13,_14=_12&&_12.type||"Default";
if(typeof _14=="string"){
if(!dc.plot2d||!dc.plot2d[_14]){
throw Error("Can't find plot: "+_14+" - didn't you forget to dojo"+".require() it?");
}
_13=new dc.plot2d[_14](this,_12);
}else{
_13=new _14(this,_12);
}
_13.name=_11;
_13.dirty=true;
if(_11 in this.plots){
this.stack[this.plots[_11]].destroy();
this.stack[this.plots[_11]]=_13;
}else{
this.plots[_11]=this.stack.length;
this.stack.push(_13);
}
this.dirty=true;
return this;
},removePlot:function(_15){
if(_15 in this.plots){
var _16=this.plots[_15];
delete this.plots[_15];
this.stack[_16].destroy();
this.stack.splice(_16,1);
df.forIn(this.plots,function(idx,_17,_18){
if(idx>_16){
_18[_17]=idx-1;
}
});
var ns=dojo.filter(this.series,function(run){
return run.plot!=_15;
});
if(ns.length<this.series.length){
dojo.forEach(this.series,function(run){
if(run.plot==_15){
run.destroy();
}
});
this.runs={};
dojo.forEach(ns,function(run,_19){
this.runs[run.plot]=_19;
},this);
this.series=ns;
}
this.dirty=true;
}
return this;
},getPlotOrder:function(){
return df.map(this.stack,_6);
},setPlotOrder:function(_1a){
var _1b={},_1c=df.filter(_1a,function(_1d){
if(!(_1d in this.plots)||(_1d in _1b)){
return false;
}
_1b[_1d]=1;
return true;
},this);
if(_1c.length<this.stack.length){
df.forEach(this.stack,function(_1e){
var _1f=_1e.name;
if(!(_1f in _1b)){
_1c.push(_1f);
}
});
}
var _20=df.map(_1c,function(_21){
return this.stack[this.plots[_21]];
},this);
df.forEach(_20,function(_22,i){
this.plots[_22.name]=i;
},this);
this.stack=_20;
this.dirty=true;
return this;
},movePlotToFront:function(_23){
if(_23 in this.plots){
var _24=this.plots[_23];
if(_24){
var _25=this.getPlotOrder();
_25.splice(_24,1);
_25.unshift(_23);
return this.setPlotOrder(_25);
}
}
return this;
},movePlotToBack:function(_26){
if(_26 in this.plots){
var _27=this.plots[_26];
if(_27<this.stack.length-1){
var _28=this.getPlotOrder();
_28.splice(_27,1);
_28.push(_26);
return this.setPlotOrder(_28);
}
}
return this;
},addSeries:function(_29,_2a,_2b){
var run=new dc.Series(this,_2a,_2b);
run.name=_29;
if(_29 in this.runs){
this.series[this.runs[_29]].destroy();
this.series[this.runs[_29]]=run;
}else{
this.runs[_29]=this.series.length;
this.series.push(run);
}
this.dirty=true;
if(!("ymin" in run)&&"min" in run){
run.ymin=run.min;
}
if(!("ymax" in run)&&"max" in run){
run.ymax=run.max;
}
return this;
},removeSeries:function(_2c){
if(_2c in this.runs){
var _2d=this.runs[_2c];
delete this.runs[_2c];
this.series[_2d].destroy();
this.series.splice(_2d,1);
df.forIn(this.runs,function(idx,_2e,_2f){
if(idx>_2d){
_2f[_2e]=idx-1;
}
});
this.dirty=true;
}
return this;
},updateSeries:function(_30,_31){
if(_30 in this.runs){
var run=this.series[this.runs[_30]];
run.update(_31);
this._invalidateDependentPlots(run.plot,false);
this._invalidateDependentPlots(run.plot,true);
}
return this;
},getSeriesOrder:function(_32){
return df.map(df.filter(this.series,function(run){
return run.plot==_32;
}),_6);
},setSeriesOrder:function(_33){
var _34,_35={},_36=df.filter(_33,function(_37){
if(!(_37 in this.runs)||(_37 in _35)){
return false;
}
var run=this.series[this.runs[_37]];
if(_34){
if(run.plot!=_34){
return false;
}
}else{
_34=run.plot;
}
_35[_37]=1;
return true;
},this);
df.forEach(this.series,function(run){
var _38=run.name;
if(!(_38 in _35)&&run.plot==_34){
_36.push(_38);
}
});
var _39=df.map(_36,function(_3a){
return this.series[this.runs[_3a]];
},this);
this.series=_39.concat(df.filter(this.series,function(run){
return run.plot!=_34;
}));
df.forEach(this.series,function(run,i){
this.runs[run.name]=i;
},this);
this.dirty=true;
return this;
},moveSeriesToFront:function(_3b){
if(_3b in this.runs){
var _3c=this.runs[_3b],_3d=this.getSeriesOrder(this.series[_3c].plot);
if(_3b!=_3d[0]){
_3d.splice(_3c,1);
_3d.unshift(_3b);
return this.setSeriesOrder(_3d);
}
}
return this;
},moveSeriesToBack:function(_3e){
if(_3e in this.runs){
var _3f=this.runs[_3e],_40=this.getSeriesOrder(this.series[_3f].plot);
if(_3e!=_40[_40.length-1]){
_40.splice(_3f,1);
_40.push(_3e);
return this.setSeriesOrder(_40);
}
}
return this;
},resize:function(_41,_42){
var box;
switch(arguments.length){
case 0:
box=dojo.marginBox(this.node);
break;
case 1:
box=_41;
break;
default:
box={w:_41,h:_42};
break;
}
dojo.marginBox(this.node,box);
this.surface.setDimensions(box.w,box.h);
this.dirty=true;
this.coords=null;
return this.render();
},getGeometry:function(){
var ret={};
df.forIn(this.axes,function(_43){
if(_43.initialized()){
ret[_43.name]={name:_43.name,vertical:_43.vertical,scaler:_43.scaler,ticks:_43.ticks};
}
});
return ret;
},setAxisWindow:function(_44,_45,_46,_47){
var _48=this.axes[_44];
if(_48){
_48.setWindow(_45,_46);
dojo.forEach(this.stack,function(_49){
if(_49.hAxis==_44||_49.vAxis==_44){
_49.zoom=_47;
}
});
}
return this;
},setWindow:function(sx,sy,dx,dy,_4a){
if(!("plotArea" in this)){
this.calculateGeometry();
}
df.forIn(this.axes,function(_4b){
var _4c,_4d,_4e=_4b.getScaler().bounds,s=_4e.span/(_4e.upper-_4e.lower);
if(_4b.vertical){
_4c=sy;
_4d=dy/s/_4c;
}else{
_4c=sx;
_4d=dx/s/_4c;
}
_4b.setWindow(_4c,_4d);
});
dojo.forEach(this.stack,function(_4f){
_4f.zoom=_4a;
});
return this;
},zoomIn:function(_50,_51){
var _52=this.axes[_50];
if(_52){
var _53,_54,_55=_52.getScaler().bounds;
var _56=Math.min(_51[0],_51[1]);
var _57=Math.max(_51[0],_51[1]);
_56=_51[0]<_55.lower?_55.lower:_56;
_57=_51[1]>_55.upper?_55.upper:_57;
_53=(_55.upper-_55.lower)/(_57-_56);
_54=_56-_55.lower;
this.setAxisWindow(_50,_53,_54);
this.render();
}
},calculateGeometry:function(){
if(this.dirty){
return this.fullGeometry();
}
var _58=dojo.filter(this.stack,function(_59){
return _59.dirty||(_59.hAxis&&this.axes[_59.hAxis].dirty)||(_59.vAxis&&this.axes[_59.vAxis].dirty);
},this);
_5a(_58,this.plotArea);
return this;
},fullGeometry:function(){
this._makeDirty();
dojo.forEach(this.stack,_1);
if(!this.theme){
this.setTheme(new dojox.charting.Theme(dojox.charting._def));
}
dojo.forEach(this.series,function(run){
if(!(run.plot in this.plots)){
if(!dc.plot2d||!dc.plot2d.Default){
throw Error("Can't find plot: Default - didn't you forget to dojo"+".require() it?");
}
var _5b=new dc.plot2d.Default(this,{});
_5b.name=run.plot;
this.plots[run.plot]=this.stack.length;
this.stack.push(_5b);
}
this.stack[this.plots[run.plot]].addSeries(run);
},this);
dojo.forEach(this.stack,function(_5c){
if(_5c.hAxis){
_5c.setAxis(this.axes[_5c.hAxis]);
}
if(_5c.vAxis){
_5c.setAxis(this.axes[_5c.vAxis]);
}
},this);
var dim=this.dim=this.surface.getDimensions();
dim.width=g.normalizedLength(dim.width);
dim.height=g.normalizedLength(dim.height);
df.forIn(this.axes,_1);
_5a(this.stack,dim);
var _5d=this.offsets={l:0,r:0,t:0,b:0};
df.forIn(this.axes,function(_5e){
df.forIn(_5e.getOffsets(),function(o,i){
_5d[i]+=o;
});
});
if(this.title){
this.titleGap=(this.titleGap==0)?0:this.titleGap||this.theme.chart.titleGap||20;
this.titlePos=this.titlePos||this.theme.chart.titlePos||"top";
this.titleFont=this.titleFont||this.theme.chart.titleFont;
this.titleFontColor=this.titleFontColor||this.theme.chart.titleFontColor||"black";
var _5f=g.normalizedLength(g.splitFontString(this.titleFont).size);
_5d[this.titlePos=="top"?"t":"b"]+=(_5f+this.titleGap);
}
df.forIn(this.margins,function(o,i){
_5d[i]+=o;
});
this.plotArea={width:dim.width-_5d.l-_5d.r,height:dim.height-_5d.t-_5d.b};
df.forIn(this.axes,_1);
_5a(this.stack,this.plotArea);
return this;
},render:function(){
if(this.theme){
this.theme.clear();
}
if(this.dirty){
return this.fullRender();
}
this.calculateGeometry();
df.forEachRev(this.stack,function(_60){
_60.render(this.dim,this.offsets);
},this);
df.forIn(this.axes,function(_61){
_61.render(this.dim,this.offsets);
},this);
this._makeClean();
if(this.surface.render){
this.surface.render();
}
return this;
},fullRender:function(){
this.fullGeometry();
var _62=this.offsets,dim=this.dim;
dojo.forEach(this.series,_2);
df.forIn(this.axes,_2);
dojo.forEach(this.stack,_2);
if(this.chartTitle&&this.chartTitle.tagName){
dojo.destroy(this.chartTitle);
}
this.surface.clear();
this.chartTitle=null;
var t=this.theme,_63=t.plotarea&&t.plotarea.fill,_64=t.plotarea&&t.plotarea.stroke;
if(_63){
this.surface.createRect({x:_62.l-1,y:_62.t-1,width:dim.width-_62.l-_62.r+2,height:dim.height-_62.t-_62.b+2}).setFill(_63);
}
if(_64){
this.surface.createRect({x:_62.l,y:_62.t,width:dim.width-_62.l-_62.r+1,height:dim.height-_62.t-_62.b+1}).setStroke(_64);
}
df.foldr(this.stack,function(z,_65){
return _65.render(dim,_62),0;
},0);
_63=this.fill!==undefined?this.fill:(t.chart&&t.chart.fill);
_64=this.stroke!==undefined?this.stroke:(t.chart&&t.chart.stroke);
if(_63=="inherit"){
var _66=this.node,_63=new dojo.Color(dojo.style(_66,"backgroundColor"));
while(_63.a==0&&_66!=document.documentElement){
_63=new dojo.Color(dojo.style(_66,"backgroundColor"));
_66=_66.parentNode;
}
}
if(_63){
if(_62.l){
this.surface.createRect({width:_62.l,height:dim.height+1}).setFill(_63);
}
if(_62.r){
this.surface.createRect({x:dim.width-_62.r,width:_62.r+1,height:dim.height+2}).setFill(_63);
}
if(_62.t){
this.surface.createRect({width:dim.width+1,height:_62.t}).setFill(_63);
}
if(_62.b){
this.surface.createRect({y:dim.height-_62.b,width:dim.width+1,height:_62.b+2}).setFill(_63);
}
}
if(this.title){
var _67=(g.renderer=="canvas"),_68=_67||!dojo.isIE&&!dojo.isOpera?"html":"gfx",_69=g.normalizedLength(g.splitFontString(this.titleFont).size);
this.chartTitle=dc.axis2d.common.createText[_68](this,this.surface,dim.width/2,this.titlePos=="top"?_69+this.margins.t:dim.height-this.margins.b,"middle",this.title,this.titleFont,this.titleFontColor);
}
if(_64){
this.surface.createRect({width:dim.width-1,height:dim.height-1}).setStroke(_64);
}
df.forIn(this.axes,function(_6a){
_6a.render(dim,_62);
});
this._makeClean();
if(this.surface.render){
this.surface.render();
}
return this;
},delayedRender:function(){
if(!this._delayedRenderHandle){
this._delayedRenderHandle=setTimeout(dojo.hitch(this,function(){
clearTimeout(this._delayedRenderHandle);
this._delayedRenderHandle=null;
this.render();
}),this.delayInMs);
}
return this;
},connectToPlot:function(_6b,_6c,_6d){
return _6b in this.plots?this.stack[this.plots[_6b]].connect(_6c,_6d):null;
},fireEvent:function(_6e,_6f,_70){
if(_6e in this.runs){
var _71=this.series[this.runs[_6e]].plot;
if(_71 in this.plots){
var _72=this.stack[this.plots[_71]];
if(_72){
_72.fireEvent(_6e,_6f,_70);
}
}
}
return this;
},_makeClean:function(){
dojo.forEach(this.axes,_4);
dojo.forEach(this.stack,_4);
dojo.forEach(this.series,_4);
this.dirty=false;
},_makeDirty:function(){
dojo.forEach(this.axes,_5);
dojo.forEach(this.stack,_5);
dojo.forEach(this.series,_5);
this.dirty=true;
},_invalidateDependentPlots:function(_73,_74){
if(_73 in this.plots){
var _75=this.stack[this.plots[_73]],_76,_77=_74?"vAxis":"hAxis";
if(_75[_77]){
_76=this.axes[_75[_77]];
if(_76&&_76.dependOnData()){
_76.dirty=true;
dojo.forEach(this.stack,function(p){
if(p[_77]&&p[_77]==_75[_77]){
p.dirty=true;
}
});
}
}else{
_75.dirty=true;
}
}
}});
function _78(_79){
return {min:_79.hmin,max:_79.hmax};
};
function _7a(_7b){
return {min:_7b.vmin,max:_7b.vmax};
};
function _7c(_7d,h){
_7d.hmin=h.min;
_7d.hmax=h.max;
};
function _7e(_7f,v){
_7f.vmin=v.min;
_7f.vmax=v.max;
};
function _80(_81,_82){
if(_81&&_82){
_81.min=Math.min(_81.min,_82.min);
_81.max=Math.max(_81.max,_82.max);
}
return _81||_82;
};
function _5a(_83,_84){
var _85={},_86={};
dojo.forEach(_83,function(_87){
var _88=_85[_87.name]=_87.getSeriesStats();
if(_87.hAxis){
_86[_87.hAxis]=_80(_86[_87.hAxis],_78(_88));
}
if(_87.vAxis){
_86[_87.vAxis]=_80(_86[_87.vAxis],_7a(_88));
}
});
dojo.forEach(_83,function(_89){
var _8a=_85[_89.name];
if(_89.hAxis){
_7c(_8a,_86[_89.hAxis]);
}
if(_89.vAxis){
_7e(_8a,_86[_89.vAxis]);
}
_89.initializeScalers(_84,_8a);
});
};
})();
}
