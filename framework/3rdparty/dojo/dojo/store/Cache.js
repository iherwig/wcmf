/*
	Copyright (c) 2004-2010, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/


if(!dojo._hasResource["dojo.store.Cache"]){
dojo._hasResource["dojo.store.Cache"]=true;
dojo.provide("dojo.store.Cache");
dojo.store.Cache=function(_1,_2,_3){
_3=_3||{};
return dojo.delegate(_1,{query:function(_4,_5){
var _6=_1.query(_4,_5);
_6.forEach(function(_7){
if(!_3.isLoaded||_3.isLoaded(_7)){
_2.put(_7);
}
});
return _6;
},get:function(id,_8){
return dojo.when(_2.get(id),function(_9){
return _9||dojo.when(_1.get(id,_8),function(_a){
if(_a){
_2.put(_a,{id:id});
}
return _a;
});
});
},add:function(_b,_c){
_2.add(_b,_c);
return _1.add(_b,_c);
},put:function(_d,_e){
_2.put(_d,_e);
return _1.put(_d,_e);
},remove:function(id,_f){
_2.remove(id,_f);
return _1.remove(id,_f);
},evict:function(id){
_2.remove(id);
}});
};
}
