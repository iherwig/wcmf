/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dijit.layout._ContentPaneResizeMixin"]){
dojo._hasResource["dijit.layout._ContentPaneResizeMixin"]=true;
dojo.provide("dijit.layout._ContentPaneResizeMixin");
dojo.declare("dijit.layout._ContentPaneResizeMixin",null,{doLayout:true,isLayoutContainer:true,_checkIfSingleChild:function(){
var _1=dojo.query("> *",this.containerNode).filter(function(_2){
return _2.tagName!=="SCRIPT";
}),_3=_1.filter(function(_4){
return dojo.hasAttr(_4,"data-dojo-type")||dojo.hasAttr(_4,"dojoType")||dojo.hasAttr(_4,"widgetId");
}),_5=dojo.filter(_3.map(dijit.byNode),function(_6){
return _6&&_6.domNode&&_6.resize;
});
if(_1.length==_3.length&&_5.length==1){
this._singleChild=_5[0];
}else{
delete this._singleChild;
}
dojo.toggleClass(this.containerNode,this.baseClass+"SingleChild",!!this._singleChild);
},resize:function(_7,_8){
this._layout(_7,_8);
},_layout:function(_9,_a){
if(_9){
dojo.marginBox(this.domNode,_9);
}
var cn=this.containerNode;
if(cn===this.domNode){
var mb=_a||{};
dojo.mixin(mb,_9||{});
if(!("h" in mb)||!("w" in mb)){
mb=dojo.mixin(dojo.marginBox(cn),mb);
}
this._contentBox=dijit.layout.marginBox2contentBox(cn,mb);
}else{
this._contentBox=dojo.contentBox(cn);
}
this._layoutChildren();
delete this._needLayout;
},_layoutChildren:function(){
if(this.doLayout){
this._checkIfSingleChild();
}
if(this._singleChild&&this._singleChild.resize){
var cb=this._contentBox||dojo.contentBox(this.containerNode);
this._singleChild.resize({w:cb.w,h:cb.h});
}else{
dojo.forEach(this.getChildren(),function(_b){
if(_b.resize){
_b.resize();
}
});
}
}});
}
