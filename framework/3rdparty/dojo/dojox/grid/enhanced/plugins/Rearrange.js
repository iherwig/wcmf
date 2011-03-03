/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojox.grid.enhanced.plugins.Rearrange"]){
dojo._hasResource["dojox.grid.enhanced.plugins.Rearrange"]=true;
dojo.provide("dojox.grid.enhanced.plugins.Rearrange");
dojo.require("dojox.grid.enhanced._Plugin");
dojo.require("dojox.grid.enhanced.plugins._RowMapLayer");
dojo.declare("dojox.grid.enhanced.plugins.Rearrange",dojox.grid.enhanced._Plugin,{name:"rearrange",constructor:function(_1,_2){
this.grid=_1;
this.setArgs(_2);
var _3=new dojox.grid.enhanced.plugins._RowMapLayer(_1);
dojox.grid.enhanced.plugins.wrap(_1,"_storeLayerFetch",_3);
},setArgs:function(_4){
this.args=dojo.mixin(this.args||{},_4||{});
this.args.setIdentifierForNewItem=this.args.setIdentifierForNewItem||function(v){
return v;
};
},destroy:function(){
this.inherited(arguments);
this.grid.unwrap("rowmap");
},onSetStore:function(_5){
this.grid.layer("rowmap").clearMapping();
},moveColumns:function(_6,_7){
var g=this.grid,_8=g.layout,_9=_8.cells,_a,i,_b=0,_c=true,_d={},_e={};
_6.sort(function(a,b){
return a-b;
});
for(i=0;i<_6.length;++i){
_d[_6[i]]=i;
if(_6[i]<_7){
++_b;
}
}
var _f=0;
var _10=0;
var _11=Math.max(_6[_6.length-1],_7);
if(_11==_9.length){
--_11;
}
for(i=_6[0];i<=_11;++i){
var j=_d[i];
if(j>=0){
if(i!=_7-_b+j){
_e[i]=_7-_b+j;
}
_f=j+1;
_10=_6.length-j-1;
}else{
if(i<_7&&_f>0){
_e[i]=i-_f;
}else{
if(i>=_7&&_10>0){
_e[i]=i+_10;
}
}
}
}
_b=0;
if(_7==_9.length){
--_7;
_c=false;
}
g._notRefreshSelection=true;
for(i=0;i<_6.length;++i){
_a=_6[i];
if(_a<_7){
_a-=_b;
}
++_b;
if(_a!=_7){
_8.moveColumn(_9[_a].view.idx,_9[_7].view.idx,_a,_7,_c);
_9=_8.cells;
}
if(_7<=_a){
++_7;
}
}
delete g._notRefreshSelection;
dojo.publish("dojox/grid/rearrange/move/"+g.id,["col",_e]);
},moveRows:function(_12,_13){
var g=this.grid,_14={},_15=[],_16=[],len=_12.length,i,r,k,arr,_17,_18;
for(i=0;i<len;++i){
r=_12[i];
if(r>=_13){
break;
}
_15.push(r);
}
_16=_12.slice(i);
arr=_15;
len=arr.length;
if(len){
_17={};
dojo.forEach(arr,function(r){
_17[r]=true;
});
_14[arr[0]]=_13-len;
for(k=0,i=arr[k]+1,_18=i-1;i<_13;++i){
if(!_17[i]){
_14[i]=_18;
++_18;
}else{
++k;
_14[i]=_13-len+k;
}
}
}
arr=_16;
len=arr.length;
if(len){
_17={};
dojo.forEach(arr,function(r){
_17[r]=true;
});
_14[arr[len-1]]=_13+len-1;
for(k=len-1,i=arr[k]-1,_18=i+1;i>=_13;--i){
if(!_17[i]){
_14[i]=_18;
--_18;
}else{
--k;
_14[i]=_13+k;
}
}
}
var _19=dojo.clone(_14);
g.layer("rowmap").setMapping(_14);
g.forEachLayer(function(_1a){
if(_1a.name()!="rowmap"){
_1a.invalidate();
return true;
}else{
return false;
}
},false);
g.selection.selected=[];
g._noInternalMapping=true;
g._refresh();
g._noInternalMapping=false;
setTimeout(function(){
dojo.publish("dojox/grid/rearrange/move/"+g.id,["row",_19]);
},0);
},moveCells:function(_1b,_1c){
var g=this.grid,s=g.store;
if(s.getFeatures()["dojo.data.api.Write"]){
if(_1b.min.row==_1c.min.row&&_1b.min.col==_1c.min.col){
return;
}
var _1d=g.layout.cells,cnt=_1b.max.row-_1b.min.row+1,r,c,tr,tc,_1e=[],_1f=[];
for(r=_1b.min.row,tr=_1c.min.row;r<=_1b.max.row;++r,++tr){
for(c=_1b.min.col,tc=_1c.min.col;c<=_1b.max.col;++c,++tc){
while(_1d[c]&&_1d[c].hidden){
++c;
}
while(_1d[tc]&&_1d[tc].hidden){
++tc;
}
_1e.push({"r":r,"c":c});
_1f.push({"r":tr,"c":tc,"v":_1d[c].get(r,g._by_idx[r].item)});
}
}
dojo.forEach(_1e,function(_20){
s.setValue(g._by_idx[_20.r].item,_1d[_20.c].field,"");
});
dojo.forEach(_1f,function(_21){
s.setValue(g._by_idx[_21.r].item,_1d[_21.c].field,_21.v);
});
s.save({onComplete:function(){
g._storeLayerFetch({start:_1b.min.row,count:cnt,onComplete:function(_22){
for(var i=0;i<cnt;++i){
g._addItem(_22[i],i+_1b.min.row);
}
}});
g._storeLayerFetch({start:_1c.min.row,count:cnt,onComplete:function(_23){
for(var i=0;i<cnt;++i){
g._addItem(_23[i],i+_1c.min.row);
}
setTimeout(function(){
dojo.publish("dojox/grid/rearrange/move/"+g.id,["cell",{"from":_1b,"to":_1c}]);
},0);
}});
}});
}
},copyCells:function(_24,_25){
var g=this.grid,s=g.store;
if(s.getFeatures()["dojo.data.api.Write"]){
if(_24.min.row==_25.min.row&&_24.min.col==_25.min.col){
return;
}
var _26=g.layout.cells,cnt=_24.max.row-_24.min.row+1,r,c,tr,tc,_27=[];
for(r=_24.min.row,tr=_25.min.row;r<=_24.max.row;++r,++tr){
for(c=_24.min.col,tc=_25.min.col;c<=_24.max.col;++c,++tc){
while(_26[c]&&_26[c].hidden){
++c;
}
while(_26[tc]&&_26[tc].hidden){
++tc;
}
_27.push({"r":tr,"c":tc,"v":_26[c].get(r,g._by_idx[r].item)});
}
}
dojo.forEach(_27,function(_28){
s.setValue(g._by_idx[_28.r].item,_26[_28.c].field,_28.v);
});
s.save({onComplete:function(){
g._storeLayerFetch({start:_25.min.row,count:cnt,onComplete:function(_29){
for(var i=0;i<cnt;++i){
g._addItem(_29[i],i+_25.min.row);
}
setTimeout(function(){
dojo.publish("dojox/grid/rearrange/copy/"+g.id,["cell",{"from":_24,"to":_25}]);
},0);
}});
}});
}
},changeCells:function(_2a,_2b,_2c){
var g=this.grid,s=g.store;
if(s.getFeatures()["dojo.data.api.Write"]){
var _2d=_2a,_2e=g.layout.cells,_2f=_2d.layout.cells,cnt=_2b.max.row-_2b.min.row+1,r,c,tr,tc,_30=[];
for(r=_2b.min.row,tr=_2c.min.row;r<=_2b.max.row;++r,++tr){
for(c=_2b.min.col,tc=_2c.min.col;c<=_2b.max.col;++c,++tc){
while(_2f[c]&&_2f[c].hidden){
++c;
}
while(_2e[tc]&&_2e[tc].hidden){
++tc;
}
_30.push({"r":tr,"c":tc,"v":_2f[c].get(r,_2d._by_idx[r].item)});
}
}
dojo.forEach(_30,function(_31){
s.setValue(g._by_idx[_31.r].item,_2e[_31.c].field,_31.v);
});
s.save({onComplete:function(){
g._storeLayerFetch({start:_2c.min.row,count:cnt,onComplete:function(_32){
for(var i=0;i<cnt;++i){
g._addItem(_32[i],i+_2c.min.row);
}
setTimeout(function(){
dojo.publish("dojox/grid/rearrange/change/"+g.id,["cell",_2c]);
},0);
}});
}});
}
},clearCells:function(_33){
var g=this.grid,s=g.store;
if(s.getFeatures()["dojo.data.api.Write"]){
var _34=g.layout.cells,cnt=_33.max.row-_33.min.row+1,r,c;
for(r=_33.min.row;r<=_33.max.row;++r){
for(c=_33.min.col;c<=_33.max.col;++c){
while(_34[c]&&_34[c].hidden){
++c;
}
s.setValue(g._by_idx[r].item,_34[c].field,"");
}
}
s.save({onComplete:function(){
g._storeLayerFetch({start:_33.min.row,count:cnt,onComplete:function(_35){
for(var i=0;i<cnt;++i){
g._addItem(_35[i],i+_33.min.row);
}
setTimeout(function(){
dojo.publish("dojox/grid/rearrange/change/"+g.id,["cell",_33]);
},0);
}});
}});
}
},insertRows:function(_36,_37,_38){
try{
var g=this.grid,s=g.store,_39=g.rowCount,_3a={},obj={idx:0},_3b=[],i,_3c=this;
var len=_37.length;
for(i=_38;i<g.rowCount;++i){
_3a[i]=i+len;
}
if(s.getFeatures()["dojo.data.api.Write"]){
if(_36){
var _3d=_36,_3e=_3d.store,_3f;
for(i=0;!_3f;++i){
_3f=g._by_idx[i];
}
var _40=s.getAttributes(_3f.item);
var _41=[];
dojo.forEach(_37,function(_42,i){
var _43={};
var _44=_3d._by_idx[_42];
if(_44){
dojo.forEach(_40,function(_45){
_43[_45]=_3e.getValue(_44.item,_45);
});
_43=_3c.args.setIdentifierForNewItem(_43,s,_39+obj.idx)||_43;
try{
s.newItem(_43);
_3b.push(_38+i);
_3a[_39+obj.idx]=_38+i;
++obj.idx;
}
catch(e){
}
}else{
_41.push(_42);
}
});
}else{
if(_37.length&&dojo.isObject(_37[0])){
dojo.forEach(_37,function(_46,i){
var _47=_3c.args.setIdentifierForNewItem(_46,s,_39+obj.idx)||_46;
try{
s.newItem(_47);
_3b.push(_38+i);
_3a[_39+obj.idx]=_38+i;
++obj.idx;
}
catch(e){
}
});
}else{
return;
}
}
g.layer("rowmap").setMapping(_3a);
s.save({onComplete:function(){
g._refresh();
setTimeout(function(){
dojo.publish("dojox/grid/rearrange/insert/"+g.id,["row",_3b]);
},0);
}});
}
}
catch(e){
}
},removeRows:function(_48){
var g=this.grid;
var s=g.store;
try{
dojo.forEach(dojo.map(_48,function(_49){
return g._by_idx[_49];
}),function(row){
if(row){
s.deleteItem(row.item);
}
});
s.save({onComplete:function(){
dojo.publish("dojox/grid/rearrange/remove/"+g.id,["row",_48]);
}});
}
catch(e){
}
},_getPageInfo:function(){
var _4a=this.grid.scroller,_4b=_4a.page,_4c=_4a.page,_4d=_4a.firstVisibleRow,_4e=_4a.lastVisibleRow,_4f=_4a.rowsPerPage,_50=_4a.pageNodes[0],_51,_52,_53,_54=[];
dojo.forEach(_50,function(_55,_56){
if(!_55){
return;
}
_53=false;
_51=_56*_4f;
_52=(_56+1)*_4f-1;
if(_4d>=_51&&_4d<=_52){
_4b=_56;
_53=true;
}
if(_4e>=_51&&_4e<=_52){
_4c=_56;
_53=true;
}
if(!_53&&(_51>_4e||_52<_4d)){
_54.push(_56);
}
});
return {topPage:_4b,bottomPage:_4c,invalidPages:_54};
}});
dojox.grid.EnhancedGrid.registerPlugin(dojox.grid.enhanced.plugins.Rearrange);
}
