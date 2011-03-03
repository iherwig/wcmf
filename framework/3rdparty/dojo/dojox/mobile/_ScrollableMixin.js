/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.mobile._ScrollableMixin"]){
dojo._hasResource["dojox.mobile._ScrollableMixin"]=true;
dojo.provide("dojox.mobile._ScrollableMixin");
dojo.require("dijit._Widget");
dojo.require("dojox.mobile.scrollable");
dojo.declare("dojox.mobile._ScrollableMixin",null,{fixedHeader:"",destroy:function(){
this.cleanup();
},startup:function(){
var _1={};
if(this.fixedHeader){
_1.fixedHeaderHeight=dojo.byId(this.fixedHeader).offsetHeight;
}
if(this.fixedFooter){
_1.fixedFooterHeight=dojo.byId(this.fixedFooter).offsetHeight;
}
this.init(_1);
this.inherited(arguments);
}});
(function(){
var _2=new dojox.mobile.scrollable();
dojo.extend(dojox.mobile._ScrollableMixin,_2);
if(dojo.version.major==1&&dojo.version.minor==4){
dojo.mixin(dojox.mobile._ScrollableMixin._meta.hidden,_2);
}
})();
}
