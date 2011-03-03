/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.form.uploader.plugins.IFrame"]){
dojo._hasResource["dojox.form.uploader.plugins.IFrame"]=true;
dojo.provide("dojox.form.uploader.plugins.IFrame");
dojo.require("dojox.form.uploader.plugins.HTML5");
dojo.require("dojo.io.iframe");
dojo.declare("dojox.form.uploader.plugins.IFrame",[],{force:"",upload:function(_1){
if(!this.supports("multiple")||this.force=="iframe"){
this.uploadIFrame(_1);
dojo.stopEvent(_1);
return;
}
},uploadIFrame:function(){
url=this.getUrl();
var _2=dojo.io.iframe.send({url:this.getUrl(),form:this.form,handleAs:"json",error:dojo.hitch(this,function(_3){
console.error("HTML Upload Error:"+_3.message);
}),load:dojo.hitch(this,function(_4,_5,_6){
this.onComplete(_4);
})});
}});
dojox.form.addUploaderPlugin(dojox.form.uploader.plugins.IFrame);
}
