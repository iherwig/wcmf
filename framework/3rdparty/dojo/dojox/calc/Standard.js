/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


define(["dojo","dijit/_Templated","dojox/math/_base","dijit/dijit","dijit/Menu","dijit/form/DropDownButton","dijit/TooltipDialog","dijit/form/TextBox","dijit/form/Button","dojox/calc/_Executor"],function(_1){
_1.experimental("dojox.calc.Standard");
_1.declare("dojox.calc.Standard",[dijit._Widget,dijit._Templated],{templateString:_1.cache("dojox.calc","templates/Standard.html","<div class=\"dijitReset dijitInline dojoxCalc\"\n><table class=\"dijitReset dijitInline dojoxCalcLayout\" dojoAttachPoint=\"calcTable\" rules=\"none\" cellspacing=0 cellpadding=0 border=0>\n\t<tr\n\t\t><td colspan=\"4\" class=\"dojoxCalcInputContainer\"\n\t\t\t><input dojoType=\"dijit.form.TextBox\" dojoAttachEvent=\"onBlur:onBlur,onKeyPress:onKeyPress\" dojoAttachPoint='textboxWidget'\n\t\t/></td\n\t></tr>\n\t<tr>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"seven\" label=\"7\" value='7' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"eight\" label=\"8\" value='8' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"nine\" label=\"9\" value='9' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"divide\" label=\"/\" value='/' dojoAttachEvent='onClick:insertOperator' />\n\t\t</td>\n\t</tr>\n\t<tr>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"four\" label=\"4\" value='4' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"five\" label=\"5\" value='5' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"six\" label=\"6\" value='6' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"multiply\" label=\"*\" value='*' dojoAttachEvent='onClick:insertOperator' />\n\t\t</td>\n\t</tr>\n\t<tr>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"one\" label=\"1\" value='1' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"two\" label=\"2\" value='2' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"three\" label=\"3\" value='3' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"add\" label=\"+\" value='+' dojoAttachEvent='onClick:insertOperator' />\n\t\t</td>\n\t</tr>\n\t<tr>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"decimal\" label=\".\" value='.' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"zero\" label=\"0\" value='0' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"equals\" label=\"x=y\" value='=' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcMinusButtonContainer\">\n\t\t\t<span dojoType=\"dijit.form.ComboButton\" dojoAttachPoint=\"subtract\" label='-' value='-' dojoAttachEvent='onClick:insertOperator'>\n\n\t\t\t\t<div dojoType=\"dijit.Menu\" style=\"display:none;\">\n\t\t\t\t\t<div dojoType=\"dijit.MenuItem\" dojoAttachEvent=\"onClick:insertMinus\">\n\t\t\t\t\t\t(-)\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</span>\n\t\t</td>\n\t</tr>\n\t<tr>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"clear\" label=\"Clear\" dojoAttachEvent='onClick:clearText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"sqrt\" label=\"&#x221A;\" value=\"&#x221A;\" dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"power\" label=\"^\" value=\"^\" dojoAttachEvent='onClick:insertOperator' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"comma\" label=\",\" value=',' dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t</tr>\n\t<tr>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"AnsButton\" label=\"Ans\" value=\"Ans\" dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"LeftParenButton\" label=\"(\" value=\"(\" dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"RightParenButton\" label=\")\" value=\")\" dojoAttachEvent='onClick:insertText' />\n\t\t</td>\n\t\t<td class=\"dojoxCalcButtonContainer\">\n\t\t\t<button dojoType=\"dijit.form.Button\" dojoAttachPoint=\"enter\" label=\"Enter\" dojoAttachEvent='onClick:parseTextbox' />\n\t\t</td>\n\t</tr>\n</table>\n<span dojoAttachPoint=\"executor\" dojoType=\"dojox.calc._Executor\" dojoAttachEvent=\"onLoad:executorLoaded\"></span>\n</div>\n"),readStore:null,writeStore:null,functions:[],widgetsInTemplate:true,executorLoaded:function(){
_1.addOnLoad(_1.hitch(this,function(){
this.loadStore(this.readStore,true);
this.loadStore(this.writeStore);
}));
},saveFunction:function(_2,_3,_4){
this.functions[_2]=this.executor.normalizedFunction(_2,_3,_4);
this.functions[_2].args=_3;
this.functions[_2].body=_4;
},loadStore:function(_5,_6){
function _7(_8){
for(var i=0;i<_8.length;i++){
this.saveFunction(_8[i].name[0],_8[i].args[0],_8[i].body[0]);
}
};
function _9(_a){
for(var i=0;i<_a.length;i++){
this.executor.normalizedFunction(_a[i].name[0],_a[i].args[0],_a[i].body[0]);
}
};
if(_5==null){
return;
}
if(_6){
_5.fetch({onComplete:_1.hitch(this,_9),onError:function(_b){
console.error(_b);
}});
}else{
_5.fetch({onComplete:_1.hitch(this,_7),onError:function(_c){
console.error(_c);
}});
}
},parseTextbox:function(){
var _d=this.textboxWidget.textbox.value;
if(_d==""&&this.commandList.length>0){
this.setTextboxValue(this.textboxWidget,this.commandList[this.commandList.length-1]);
_d=this.textboxWidget.textbox.value;
}
if(_d!=""){
var _e=this.executor.eval(_d);
if((typeof _e=="number"&&isNaN(_e))){
if(this.commandList.length==0||this.commandList[this.commandList.length-1]!=_d){
this.commandList.push(_d);
}
this.print(_d,false);
this.print("Not a Number",true);
}else{
if(((typeof _e=="object"&&"length" in _e)||typeof _e!="object")&&typeof _e!="function"&&_e!=null){
this.executor.eval("Ans="+_e);
if(this.commandList.length==0||this.commandList[this.commandList.length-1]!=_d){
this.commandList.push(_d);
}
this.print(_d,false);
this.print(_e,true);
}
}
this.commandIndex=this.commandList.length-1;
if(this.hasDisplay){
this.displayBox.scrollTop=this.displayBox.scrollHeight;
}
dijit.selectInputText(this.textboxWidget.textbox);
}else{
this.textboxWidget.focus();
}
},cycleCommands:function(_f,_10,_11){
if(_f==-1||this.commandList.length==0){
return;
}
var _12=_11.charOrCode;
if(_12==_1.keys.UP_ARROW){
this.cycleCommandUp();
}else{
if(_12==_1.keys.DOWN_ARROW){
this.cycleCommandDown();
}
}
},cycleCommandUp:function(){
if(this.commandIndex-1<0){
this.commandIndex=0;
}else{
this.commandIndex--;
}
this.setTextboxValue(this.textboxWidget,this.commandList[this.commandIndex]);
},cycleCommandDown:function(){
if(this.commandIndex+1>=this.commandList.length){
this.commandIndex=this.commandList.length;
this.setTextboxValue(this.textboxWidget,"");
}else{
this.commandIndex++;
this.setTextboxValue(this.textboxWidget,this.commandList[this.commandIndex]);
}
},onBlur:function(){
if(_1.isIE){
var tr=_1.doc.selection.createRange().duplicate();
var _13=tr.text||"";
var ntr=this.textboxWidget.textbox.createTextRange();
tr.move("character",0);
ntr.move("character",0);
try{
ntr.setEndPoint("EndToEnd",tr);
this.textboxWidget.textbox.selectionEnd=(this.textboxWidget.textbox.selectionStart=String(ntr.text).replace(/\r/g,"").length)+_13.length;
}
catch(e){
}
}
},onKeyPress:function(_14){
if(_14.charOrCode==_1.keys.ENTER){
this.parseTextbox();
_1.stopEvent(_14);
}else{
if(_14.charOrCode=="!"||_14.charOrCode=="^"||_14.charOrCode=="*"||_14.charOrCode=="/"||_14.charOrCode=="-"||_14.charOrCode=="+"){
if(_1.isIE){
var tr=_1.doc.selection.createRange().duplicate();
var _15=tr.text||"";
var ntr=this.textboxWidget.textbox.createTextRange();
tr.move("character",0);
ntr.move("character",0);
try{
ntr.setEndPoint("EndToEnd",tr);
this.textboxWidget.textbox.selectionEnd=(this.textboxWidget.textbox.selectionStart=String(ntr.text).replace(/\r/g,"").length)+_15.length;
}
catch(e){
}
}
if(this.textboxWidget.get("value")==""){
this.setTextboxValue(this.textboxWidget,"Ans");
}else{
if(this.putInAnsIfTextboxIsHighlighted(this.textboxWidget.textbox,_14.charOrCode)){
this.setTextboxValue(this.textboxWidget,"Ans");
dijit.selectInputText(this.textboxWidget.textbox,this.textboxWidget.textbox.value.length,this.textboxWidget.textbox.value.length);
}
}
}
}
},insertMinus:function(){
this.insertText("-");
},print:function(_16,_17){
var t="<span style='display:block;";
if(_17){
t+="text-align:right;'>";
}else{
t+="text-align:left;'>";
}
t+=_16+"<br></span>";
if(this.hasDisplay){
this.displayBox.innerHTML+=t;
}else{
this.setTextboxValue(this.textboxWidget,_16);
}
},setTextboxValue:function(_18,val){
_18.set("value",val);
},putInAnsIfTextboxIsHighlighted:function(_19){
if(typeof _19.selectionStart=="number"){
if(_19.selectionStart==0&&_19.selectionEnd==_19.value.length){
return true;
}
}else{
if(document.selection){
var _1a=document.selection.createRange();
if(_19.value==_1a.text){
return true;
}
}
}
return false;
},clearText:function(){
if(this.hasDisplay&&this.textboxWidget.get("value")==""){
this.displayBox.innerHTML="";
}else{
this.setTextboxValue(this.textboxWidget,"");
}
this.textboxWidget.focus();
},insertOperator:function(_1b){
if(typeof _1b=="object"){
_1b=_1b=dijit.getEnclosingWidget(_1b["target"]).value;
}
if(this.textboxWidget.get("value")==""||this.putInAnsIfTextboxIsHighlighted(this.textboxWidget.textbox)){
_1b="Ans"+_1b;
}
this.insertText(_1b);
},insertText:function(_1c){
setTimeout(_1.hitch(this,function(){
var _1d=this.textboxWidget.textbox;
if(_1d.value==""){
_1d.selectionStart=0;
_1d.selectionEnd=0;
}
if(typeof _1c=="object"){
_1c=_1c=dijit.getEnclosingWidget(_1c["target"]).value;
}
var _1e=_1d.value.replace(/\r/g,"");
if(typeof _1d.selectionStart=="number"){
var pos=_1d.selectionStart;
var cr=0;
if(navigator.userAgent.indexOf("Opera")!=-1){
cr=(_1d.value.substring(0,pos).match(/\r/g)||[]).length;
}
_1d.value=_1e.substring(0,_1d.selectionStart-cr)+_1c+_1e.substring(_1d.selectionEnd-cr);
_1d.focus();
pos+=_1c.length;
dijit.selectInputText(this.textboxWidget.textbox,pos,pos);
}else{
if(document.selection){
if(this.handle){
clearTimeout(this.handle);
this.handle=null;
}
_1d.focus();
this.handle=setTimeout(function(){
var _1f=document.selection.createRange();
_1f.text=_1c;
_1f.select();
this.handle=null;
},0);
}
}
}),0);
},hasDisplay:false,postCreate:function(){
this.handle=null;
this.commandList=[];
this.commandIndex=0;
if(this.displayBox){
this.hasDisplay=true;
}
if(this.toFracButton&&!dojox.calc.toFrac){
_1.style(this.toFracButton.domNode,{visibility:"hidden"});
}
if(this.functionMakerButton&&!dojox.calc.FuncGen){
_1.style(this.functionMakerButton.domNode,{visibility:"hidden"});
}
if(this.grapherMakerButton&&!dojox.calc.Grapher){
_1.style(this.grapherMakerButton.domNode,{visibility:"hidden"});
}
this._connects.push(dijit.typematic.addKeyListener(this.textboxWidget.textbox,{charOrCode:_1.keys.UP_ARROW,shiftKey:false,metaKey:false,ctrlKey:false},this,this.cycleCommands,200,200));
this._connects.push(dijit.typematic.addKeyListener(this.textboxWidget.textbox,{charOrCode:_1.keys.DOWN_ARROW,shiftKey:false,metaKey:false,ctrlKey:false},this,this.cycleCommands,200,200));
this.startup();
}});
return dojox.calc.Standard;
});
