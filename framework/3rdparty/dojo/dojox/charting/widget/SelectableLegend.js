/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.charting.widget.SelectableLegend"]){
dojo._hasResource["dojox.charting.widget.SelectableLegend"]=true;
dojo.provide("dojox.charting.widget.SelectableLegend");
dojo.require("dojox.charting.widget.Legend");
dojo.require("dijit.form.CheckBox");
dojo.require("dojox.charting.action2d.Highlight");
(function(){
var df=dojox.lang.functional;
dojo.declare("dojox.charting.widget.SelectableLegend",[dojox.charting.widget.Legend],{outline:false,transitionFill:null,transitionStroke:null,postCreate:function(){
this.legends=[];
this.legendAnim={};
this.inherited(arguments);
},refresh:function(){
this.inherited(arguments);
this._applyEvents();
},_addLabel:function(_1,_2){
this.inherited(arguments);
var _3=dojo.query("td",this.legendBody);
var _4=_3[_3.length-1];
this.legends.push(_4);
var _5=new dijit.form.CheckBox({checked:true});
dojo.place(_5.domNode,_4,"first");
},_applyEvents:function(){
dojo.forEach(this.legends,function(_6,i){
var _7,_8=[],_9,_a;
if(this._isPie()){
_7=this.chart.stack[0];
_8.push(_7.group.children[i]);
_9=_7.name;
_a=this.chart.series[0].name;
}else{
_7=this.chart.series[i];
_8=_7.group.children;
_9=_7.plot;
_a=_7.name;
}
var _b={fills:df.map(_8,"x.getFill()"),strokes:df.map(_8,"x.getStroke()")};
var _c=dojo.query(".dijitCheckBox",_6)[0];
dojo.connect(_c,"onclick",this,function(){
this._toggle(_8,i,_6.vanished,_b,_a,_9);
_6.vanished=!_6.vanished;
});
var _d=dojo.query(".dojoxLegendIcon",_6)[0],_e=this._getFilledShape(this._surfaces[i].children);
dojo.forEach(["onmouseenter","onmouseleave"],function(_f){
dojo.connect(_d,_f,this,function(e){
this._highlight(e,_e,_8,i,_6.vanished,_b,_a,_9);
});
},this);
},this);
},_toggle:function(_10,_11,_12,dyn,_13,_14){
dojo.forEach(_10,function(_15,i){
var _16=dyn.fills[i],_17=this._getTransitionFill(_14),_18=dyn.strokes[i],_19=this.transitionStroke;
if(_16){
if(_17&&(typeof _16=="string"||_16 instanceof dojo.Color)){
dojox.gfx.fx.animateFill({shape:_15,color:{start:_12?_17:_16,end:_12?_16:_17}}).play();
}else{
_15.setFill(_12?_16:_17);
}
}
if(_18&&!this.outline){
_15.setStroke(_12?_18:_19);
}
},this);
},_highlight:function(e,_1a,_1b,_1c,_1d,dyn,_1e,_1f){
if(!_1d){
var _20=this._getAnim(_1f),_21=this._isPie(),_22=_23(e.type);
var _24={shape:_1a,index:_21?"legend"+_1c:"legend",run:{name:_1e},type:_22};
_20.process(_24);
dojo.forEach(_1b,function(_25,i){
_25.setFill(dyn.fills[i]);
var o={shape:_25,index:_21?_1c:i,run:{name:_1e},type:_22};
_20.process(o);
});
}
},_getAnim:function(_26){
if(!this.legendAnim[_26]){
this.legendAnim[_26]=new dojox.charting.action2d.Highlight(this.chart,_26);
}
return this.legendAnim[_26];
},_getTransitionFill:function(_27){
if(this.chart.stack[this.chart.plots[_27]].declaredClass.indexOf("dojox.charting.plot2d.Stacked")!=-1){
return this.chart.theme.plotarea.fill;
}
return null;
},_getFilledShape:function(_28){
var i=0;
while(_28[i]){
if(_28[i].getFill()){
return _28[i];
}
i++;
}
},_isPie:function(){
return this.chart.stack[0].declaredClass=="dojox.charting.plot2d.Pie";
}});
function _23(_29){
if(_29=="mouseenter"){
return "onmouseover";
}
if(_29=="mouseleave"){
return "onmouseout";
}
return "on"+_29;
};
})();
}
