/*
	Copyright (c) 2004-2011, The Dojo Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/

/*
	This is an optimized version of Dojo, built for deployment and not for
	development. To get sources and documentation, please visit:

		http://dojotoolkit.org
*/

if(!dojo._hasResource["dojo.fx.Toggler"]){dojo._hasResource["dojo.fx.Toggler"]=true;dojo.provide("dojo.fx.Toggler");dojo.declare("dojo.fx.Toggler",null,{node:null,showFunc:dojo.fadeIn,hideFunc:dojo.fadeOut,showDuration:200,hideDuration:200,constructor:function(_1){var _2=this;dojo.mixin(_2,_1);_2.node=_1.node;_2._showArgs=dojo.mixin({},_1);_2._showArgs.node=_2.node;_2._showArgs.duration=_2.showDuration;_2.showAnim=_2.showFunc(_2._showArgs);_2._hideArgs=dojo.mixin({},_1);_2._hideArgs.node=_2.node;_2._hideArgs.duration=_2.hideDuration;_2.hideAnim=_2.hideFunc(_2._hideArgs);dojo.connect(_2.showAnim,"beforeBegin",dojo.hitch(_2.hideAnim,"stop",true));dojo.connect(_2.hideAnim,"beforeBegin",dojo.hitch(_2.showAnim,"stop",true));},show:function(_3){return this.showAnim.play(_3||0);},hide:function(_4){return this.hideAnim.play(_4||0);}});}if(!dojo._hasResource["dojo.fx"]){dojo._hasResource["dojo.fx"]=true;dojo.provide("dojo.fx");(function(){var d=dojo,_5={_fire:function(_6,_7){if(this[_6]){this[_6].apply(this,_7||[]);}return this;}};var _8=function(_9){this._index=-1;this._animations=_9||[];this._current=this._onAnimateCtx=this._onEndCtx=null;this.duration=0;d.forEach(this._animations,function(a){this.duration+=a.duration;if(a.delay){this.duration+=a.delay;}},this);};d.extend(_8,{_onAnimate:function(){this._fire("onAnimate",arguments);},_onEnd:function(){d.disconnect(this._onAnimateCtx);d.disconnect(this._onEndCtx);this._onAnimateCtx=this._onEndCtx=null;if(this._index+1==this._animations.length){this._fire("onEnd");}else{this._current=this._animations[++this._index];this._onAnimateCtx=d.connect(this._current,"onAnimate",this,"_onAnimate");this._onEndCtx=d.connect(this._current,"onEnd",this,"_onEnd");this._current.play(0,true);}},play:function(_a,_b){if(!this._current){this._current=this._animations[this._index=0];}if(!_b&&this._current.status()=="playing"){return this;}var _c=d.connect(this._current,"beforeBegin",this,function(){this._fire("beforeBegin");}),_d=d.connect(this._current,"onBegin",this,function(_e){this._fire("onBegin",arguments);}),_f=d.connect(this._current,"onPlay",this,function(arg){this._fire("onPlay",arguments);d.disconnect(_c);d.disconnect(_d);d.disconnect(_f);});if(this._onAnimateCtx){d.disconnect(this._onAnimateCtx);}this._onAnimateCtx=d.connect(this._current,"onAnimate",this,"_onAnimate");if(this._onEndCtx){d.disconnect(this._onEndCtx);}this._onEndCtx=d.connect(this._current,"onEnd",this,"_onEnd");this._current.play.apply(this._current,arguments);return this;},pause:function(){if(this._current){var e=d.connect(this._current,"onPause",this,function(arg){this._fire("onPause",arguments);d.disconnect(e);});this._current.pause();}return this;},gotoPercent:function(_10,_11){this.pause();var _12=this.duration*_10;this._current=null;d.some(this._animations,function(a){if(a.duration<=_12){this._current=a;return true;}_12-=a.duration;return false;});if(this._current){this._current.gotoPercent(_12/this._current.duration,_11);}return this;},stop:function(_13){if(this._current){if(_13){for(;this._index+1<this._animations.length;++this._index){this._animations[this._index].stop(true);}this._current=this._animations[this._index];}var e=d.connect(this._current,"onStop",this,function(arg){this._fire("onStop",arguments);d.disconnect(e);});this._current.stop();}return this;},status:function(){return this._current?this._current.status():"stopped";},destroy:function(){if(this._onAnimateCtx){d.disconnect(this._onAnimateCtx);}if(this._onEndCtx){d.disconnect(this._onEndCtx);}}});d.extend(_8,_5);dojo.fx.chain=function(_14){return new _8(_14);};var _15=function(_16){this._animations=_16||[];this._connects=[];this._finished=0;this.duration=0;d.forEach(_16,function(a){var _17=a.duration;if(a.delay){_17+=a.delay;}if(this.duration<_17){this.duration=_17;}this._connects.push(d.connect(a,"onEnd",this,"_onEnd"));},this);this._pseudoAnimation=new d.Animation({curve:[0,1],duration:this.duration});var _18=this;d.forEach(["beforeBegin","onBegin","onPlay","onAnimate","onPause","onStop","onEnd"],function(evt){_18._connects.push(d.connect(_18._pseudoAnimation,evt,function(){_18._fire(evt,arguments);}));});};d.extend(_15,{_doAction:function(_19,_1a){d.forEach(this._animations,function(a){a[_19].apply(a,_1a);});return this;},_onEnd:function(){if(++this._finished>this._animations.length){this._fire("onEnd");}},_call:function(_1b,_1c){var t=this._pseudoAnimation;t[_1b].apply(t,_1c);},play:function(_1d,_1e){this._finished=0;this._doAction("play",arguments);this._call("play",arguments);return this;},pause:function(){this._doAction("pause",arguments);this._call("pause",arguments);return this;},gotoPercent:function(_1f,_20){var ms=this.duration*_1f;d.forEach(this._animations,function(a){a.gotoPercent(a.duration<ms?1:(ms/a.duration),_20);});this._call("gotoPercent",arguments);return this;},stop:function(_21){this._doAction("stop",arguments);this._call("stop",arguments);return this;},status:function(){return this._pseudoAnimation.status();},destroy:function(){d.forEach(this._connects,dojo.disconnect);}});d.extend(_15,_5);dojo.fx.combine=function(_22){return new _15(_22);};dojo.fx.wipeIn=function(_23){var _24=_23.node=d.byId(_23.node),s=_24.style,o;var _25=d.animateProperty(d.mixin({properties:{height:{start:function(){o=s.overflow;s.overflow="hidden";if(s.visibility=="hidden"||s.display=="none"){s.height="1px";s.display="";s.visibility="";return 1;}else{var _26=d.style(_24,"height");return Math.max(_26,1);}},end:function(){return _24.scrollHeight;}}}},_23));d.connect(_25,"onEnd",function(){s.height="auto";s.overflow=o;});return _25;};dojo.fx.wipeOut=function(_27){var _28=_27.node=d.byId(_27.node),s=_28.style,o;var _29=d.animateProperty(d.mixin({properties:{height:{end:1}}},_27));d.connect(_29,"beforeBegin",function(){o=s.overflow;s.overflow="hidden";s.display="";});d.connect(_29,"onEnd",function(){s.overflow=o;s.height="auto";s.display="none";});return _29;};dojo.fx.slideTo=function(_2a){var _2b=_2a.node=d.byId(_2a.node),top=null,_2c=null;var _2d=(function(n){return function(){var cs=d.getComputedStyle(n);var pos=cs.position;top=(pos=="absolute"?n.offsetTop:parseInt(cs.top)||0);_2c=(pos=="absolute"?n.offsetLeft:parseInt(cs.left)||0);if(pos!="absolute"&&pos!="relative"){var ret=d.position(n,true);top=ret.y;_2c=ret.x;n.style.position="absolute";n.style.top=top+"px";n.style.left=_2c+"px";}};})(_2b);_2d();var _2e=d.animateProperty(d.mixin({properties:{top:_2a.top||0,left:_2a.left||0}},_2a));d.connect(_2e,"beforeBegin",_2e,_2d);return _2e;};})();}if(!dojo._hasResource["dojox.fx.flip"]){dojo._hasResource["dojox.fx.flip"]=true;dojo.provide("dojox.fx.flip");dojo.experimental("dojox.fx.flip");var borderConst="border",widthConst="Width",heightConst="Height",topConst="Top",rightConst="Right",leftConst="Left",bottomConst="Bottom";dojox.fx.flip=function(_2f){var _30=dojo.create("div"),_31=_2f.node=dojo.byId(_2f.node),s=_31.style,_32=null,hs=null,pn=null,_33=_2f.lightColor||"#dddddd",_34=_2f.darkColor||"#555555",_35=dojo.style(_31,"backgroundColor"),_36=_2f.endColor||_35,_37={},_38=[],_39=_2f.duration?_2f.duration/2:250,dir=_2f.dir||"left",_3a=0.9,_3b="transparent",_3c=_2f.whichAnim,_3d=_2f.axis||"center",_3e=_2f.depth;var _3f=function(_40){return ((new dojo.Color(_40)).toHex()==="#000000")?"#000001":_40;};if(dojo.isIE<7){_36=_3f(_36);_33=_3f(_33);_34=_3f(_34);_35=_3f(_35);_3b="black";_30.style.filter="chroma(color='#000000')";}var _41=(function(n){return function(){var ret=dojo.coords(n,true);_32={top:ret.y,left:ret.x,width:ret.w,height:ret.h};};})(_31);_41();hs={position:"absolute",top:_32["top"]+"px",left:_32["left"]+"px",height:"0",width:"0",zIndex:_2f.zIndex||(s.zIndex||0),border:"0 solid "+_3b,fontSize:"0",visibility:"hidden"};var _42=[{},{top:_32["top"],left:_32["left"]}];var _43={left:[leftConst,rightConst,topConst,bottomConst,widthConst,heightConst,"end"+heightConst+"Min",leftConst,"end"+heightConst+"Max"],right:[rightConst,leftConst,topConst,bottomConst,widthConst,heightConst,"end"+heightConst+"Min",leftConst,"end"+heightConst+"Max"],top:[topConst,bottomConst,leftConst,rightConst,heightConst,widthConst,"end"+widthConst+"Min",topConst,"end"+widthConst+"Max"],bottom:[bottomConst,topConst,leftConst,rightConst,heightConst,widthConst,"end"+widthConst+"Min",topConst,"end"+widthConst+"Max"]};pn=_43[dir];if(typeof _3e!="undefined"){_3e=Math.max(0,Math.min(1,_3e))/2;_3a=0.4+(0.5-_3e);}else{_3a=Math.min(0.9,Math.max(0.4,_32[pn[5].toLowerCase()]/_32[pn[4].toLowerCase()]));}var p0=_42[0];for(var i=4;i<6;i++){if(_3d=="center"||_3d=="cube"){_32["end"+pn[i]+"Min"]=_32[pn[i].toLowerCase()]*_3a;_32["end"+pn[i]+"Max"]=_32[pn[i].toLowerCase()]/_3a;}else{if(_3d=="shortside"){_32["end"+pn[i]+"Min"]=_32[pn[i].toLowerCase()];_32["end"+pn[i]+"Max"]=_32[pn[i].toLowerCase()]/_3a;}else{if(_3d=="longside"){_32["end"+pn[i]+"Min"]=_32[pn[i].toLowerCase()]*_3a;_32["end"+pn[i]+"Max"]=_32[pn[i].toLowerCase()];}}}}if(_3d=="center"){p0[pn[2].toLowerCase()]=_32[pn[2].toLowerCase()]-(_32[pn[8]]-_32[pn[6]])/4;}else{if(_3d=="shortside"){p0[pn[2].toLowerCase()]=_32[pn[2].toLowerCase()]-(_32[pn[8]]-_32[pn[6]])/2;}}_37[pn[5].toLowerCase()]=_32[pn[5].toLowerCase()]+"px";_37[pn[4].toLowerCase()]="0";_37[borderConst+pn[1]+widthConst]=_32[pn[4].toLowerCase()]+"px";_37[borderConst+pn[1]+"Color"]=_35;p0[borderConst+pn[1]+widthConst]=0;p0[borderConst+pn[1]+"Color"]=_34;p0[borderConst+pn[2]+widthConst]=p0[borderConst+pn[3]+widthConst]=_3d!="cube"?(_32["end"+pn[5]+"Max"]-_32["end"+pn[5]+"Min"])/2:_32[pn[6]]/2;p0[pn[7].toLowerCase()]=_32[pn[7].toLowerCase()]+_32[pn[4].toLowerCase()]/2+(_2f.shift||0);p0[pn[5].toLowerCase()]=_32[pn[6]];var p1=_42[1];p1[borderConst+pn[0]+"Color"]={start:_33,end:_36};p1[borderConst+pn[0]+widthConst]=_32[pn[4].toLowerCase()];p1[borderConst+pn[2]+widthConst]=0;p1[borderConst+pn[3]+widthConst]=0;p1[pn[5].toLowerCase()]={start:_32[pn[6]],end:_32[pn[5].toLowerCase()]};dojo.mixin(hs,_37);dojo.style(_30,hs);dojo.body().appendChild(_30);var _44=function(){dojo.destroy(_30);s.backgroundColor=_36;s.visibility="visible";};if(_3c=="last"){for(i in p0){p0[i]={start:p0[i]};}p0[borderConst+pn[1]+"Color"]={start:_34,end:_36};p1=p0;}if(!_3c||_3c=="first"){_38.push(dojo.animateProperty({node:_30,duration:_39,properties:p0}));}if(!_3c||_3c=="last"){_38.push(dojo.animateProperty({node:_30,duration:_39,properties:p1,onEnd:_44}));}dojo.connect(_38[0],"play",function(){_30.style.visibility="visible";s.visibility="hidden";});return dojo.fx.chain(_38);};dojox.fx.flipCube=function(_45){var _46=[],mb=dojo.marginBox(_45.node),_47=mb.w/2,_48=mb.h/2,_49={top:{pName:"height",args:[{whichAnim:"first",dir:"top",shift:-_48},{whichAnim:"last",dir:"bottom",shift:_48}]},right:{pName:"width",args:[{whichAnim:"first",dir:"right",shift:_47},{whichAnim:"last",dir:"left",shift:-_47}]},bottom:{pName:"height",args:[{whichAnim:"first",dir:"bottom",shift:_48},{whichAnim:"last",dir:"top",shift:-_48}]},left:{pName:"width",args:[{whichAnim:"first",dir:"left",shift:-_47},{whichAnim:"last",dir:"right",shift:_47}]}};var d=_49[_45.dir||"left"],p=d.args;_45.duration=_45.duration?_45.duration*2:500;_45.depth=0.8;_45.axis="cube";for(var i=p.length-1;i>=0;i--){dojo.mixin(_45,p[i]);_46.push(dojox.fx.flip(_45));}return dojo.fx.combine(_46);};dojox.fx.flipPage=function(_4a){var n=_4a.node,_4b=dojo.coords(n,true),x=_4b.x,y=_4b.y,w=_4b.w,h=_4b.h,_4c=dojo.style(n,"backgroundColor"),_4d=_4a.lightColor||"#dddddd",_4e=_4a.darkColor,_4f=dojo.create("div"),_50=[],hn=[],dir=_4a.dir||"right",pn={left:["left","right","x","w"],top:["top","bottom","y","h"],right:["left","left","x","w"],bottom:["top","top","y","h"]},_51={right:[1,-1],left:[-1,1],top:[-1,1],bottom:[1,-1]};dojo.style(_4f,{position:"absolute",width:w+"px",height:h+"px",top:y+"px",left:x+"px",visibility:"hidden"});var hs=[];for(var i=0;i<2;i++){var r=i%2,d=r?pn[dir][1]:dir,wa=r?"last":"first",_52=r?_4c:_4d,_53=r?_52:_4a.startColor||n.style.backgroundColor;hn[i]=dojo.clone(_4f);var _54=function(x){return function(){dojo.destroy(hn[x]);};}(i);dojo.body().appendChild(hn[i]);hs[i]={backgroundColor:r?_53:_4c};hs[i][pn[dir][0]]=_4b[pn[dir][2]]+_51[dir][0]*i*_4b[pn[dir][3]]+"px";dojo.style(hn[i],hs[i]);_50.push(dojox.fx.flip({node:hn[i],dir:d,axis:"shortside",depth:_4a.depth,duration:_4a.duration/2,shift:_51[dir][i]*_4b[pn[dir][3]]/2,darkColor:_4e,lightColor:_4d,whichAnim:wa,endColor:_52}));dojo.connect(_50[i],"onEnd",_54);}return dojo.fx.chain(_50);};dojox.fx.flipGrid=function(_55){var _56=_55.rows||4,_57=_55.cols||4,_58=[],_59=dojo.create("div"),n=_55.node,_5a=dojo.coords(n,true),x=_5a.x,y=_5a.y,nw=_5a.w,nh=_5a.h,w=_5a.w/_57,h=_5a.h/_56,_5b=[];dojo.style(_59,{position:"absolute",width:w+"px",height:h+"px",backgroundColor:dojo.style(n,"backgroundColor")});for(var i=0;i<_56;i++){var r=i%2,d=r?"right":"left",_5c=r?1:-1;var cn=dojo.clone(n);dojo.style(cn,{position:"absolute",width:nw+"px",height:nh+"px",top:y+"px",left:x+"px",clip:"rect("+i*h+"px,"+nw+"px,"+nh+"px,0)"});dojo.body().appendChild(cn);_58[i]=[];for(var j=0;j<_57;j++){var hn=dojo.clone(_59),l=r?j:_57-(j+1);var _5d=function(xn,_5e,_5f){return function(){if(!(_5e%2)){dojo.style(xn,{clip:"rect("+_5e*h+"px,"+(nw-(_5f+1)*w)+"px,"+((_5e+1)*h)+"px,0px)"});}else{dojo.style(xn,{clip:"rect("+_5e*h+"px,"+nw+"px,"+((_5e+1)*h)+"px,"+((_5f+1)*w)+"px)"});}};}(cn,i,j);dojo.body().appendChild(hn);dojo.style(hn,{left:x+l*w+"px",top:y+i*h+"px",visibility:"hidden"});var a=dojox.fx.flipPage({node:hn,dir:d,duration:_55.duration||900,shift:_5c*w/2,depth:0.2,darkColor:_55.darkColor,lightColor:_55.lightColor,startColor:_55.startColor||_55.node.style.backgroundColor}),_60=function(xn){return function(){dojo.destroy(xn);};}(hn);dojo.connect(a,"play",this,_5d);dojo.connect(a,"play",this,_60);_58[i].push(a);}_5b.push(dojo.fx.chain(_58[i]));}dojo.connect(_5b[0],"play",function(){dojo.style(n,{visibility:"hidden"});});return dojo.fx.combine(_5b);};}if(!dojo._hasResource["dojox.mobile.compat"]){dojo._hasResource["dojox.mobile.compat"]=true;dojo.provide("dojox.mobile.compat");if(!dojo.isWebKit){dojo.extend(dojox.mobile.View,{_doTransition:function(_61,_62,_63,dir){var _64;this.wakeUp(_62);if(!_63||_63=="none"){_62.style.display="";_61.style.display="none";_62.style.left="0px";this.invokeCallback();}else{if(_63=="slide"){var w=_61.offsetWidth;var s1=dojo.fx.slideTo({node:_61,duration:400,left:-w*dir,top:_61.offsetTop});var s2=dojo.fx.slideTo({node:_62,duration:400,left:0});_62.style.position="absolute";_62.style.left=w*dir+"px";_62.style.display="";_64=dojo.fx.combine([s1,s2]);dojo.connect(_64,"onEnd",this,function(){_61.style.display="none";_62.style.position="relative";this.invokeCallback();});_64.play();}else{if(_63=="flip"){_64=dojox.fx.flip({node:_61,dir:"right",depth:0.5,duration:400});_62.style.position="absolute";_62.style.left="0px";dojo.connect(_64,"onEnd",this,function(){_61.style.display="none";_62.style.position="relative";_62.style.display="";this.invokeCallback();});_64.play();}else{if(_63=="fade"){_64=dojo.fx.chain([dojo.fadeOut({node:_61,duration:600}),dojo.fadeIn({node:_62,duration:600})]);_62.style.position="absolute";_62.style.left="0px";_62.style.display="";dojo.style(_62,"opacity",0);dojo.connect(_64,"onEnd",this,function(){_61.style.display="none";_62.style.position="relative";dojo.style(_61,"opacity",1);this.invokeCallback();});_64.play();}}}}},wakeUp:function(_65){if(dojo.isIE&&!_65._wokeup){_65._wokeup=true;var _66=_65.style.display;_65.style.display="";var _67=_65.getElementsByTagName("*");for(var i=0,len=_67.length;i<len;i++){var val=_67[i].style.display;_67[i].style.display="none";_67[i].style.display="";_67[i].style.display=val;}_65.style.display=_66;}}});dojo.extend(dojox.mobile.Switch,{buildRendering:function(){this.domNode=this.srcNodeRef||dojo.doc.createElement("DIV");this.domNode.className="mblSwitch";this.domNode.innerHTML="<div class=\"mblSwitchInner\">"+"<div class=\"mblSwitchBg mblSwitchBgLeft\">"+"<div class=\"mblSwitchCorner mblSwitchCorner1T\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner2T\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner3T\"></div>"+"<div class=\"mblSwitchText mblSwitchTextLeft\">"+this.leftLabel+"</div>"+"<div class=\"mblSwitchCorner mblSwitchCorner1B\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner2B\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner3B\"></div>"+"</div>"+"<div class=\"mblSwitchBg mblSwitchBgRight\">"+"<div class=\"mblSwitchCorner mblSwitchCorner1T\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner2T\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner3T\"></div>"+"<div class=\"mblSwitchText mblSwitchTextRight\">"+this.rightLabel+"</div>"+"<div class=\"mblSwitchCorner mblSwitchCorner1B\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner2B\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner3B\"></div>"+"</div>"+"<div class=\"mblSwitchKnobContainer\">"+"<div class=\"mblSwitchCorner mblSwitchCorner1T\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner2T\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner3T\"></div>"+"<div class=\"mblSwitchKnob\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner1B\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner2B\"></div>"+"<div class=\"mblSwitchCorner mblSwitchCorner3B\"></div>"+"</div>"+"</div>";var n=this.inner=this.domNode.firstChild;this.left=n.childNodes[0];this.right=n.childNodes[1];this.knob=n.childNodes[2];dojo.addClass(this.domNode,(this.value=="on")?"mblSwitchOn":"mblSwitchOff");this[this.value=="off"?"left":"right"].style.display="none";},_changeState:function(_68){if(!this.inner.parentNode||!this.inner.parentNode.tagName){dojo.addClass(this.domNode,(_68=="on")?"mblSwitchOn":"mblSwitchOff");return;}var pos;if(this.inner.offsetLeft==0){if(_68=="on"){return;}pos=-53;}else{if(_68=="off"){return;}pos=0;}var a=dojo.fx.slideTo({node:this.inner,duration:500,left:pos});var _69=this;dojo.connect(a,"onEnd",function(){_69[_68=="off"?"left":"right"].style.display="none";});a.play();}});if(dojo.isIE||dojo.isBB){dojo.extend(dojox.mobile.RoundRect,{buildRendering:function(){dojox.mobile.createRoundRect(this);this.domNode.className="mblRoundRect";}});dojox.mobile.RoundRectList._addChild=dojox.mobile.RoundRectList.prototype.addChild;dojo.extend(dojox.mobile.RoundRectList,{buildRendering:function(){dojox.mobile.createRoundRect(this,true);this.domNode.className="mblRoundRectList";},postCreate:function(){this.redrawBorders();},addChild:function(_6a){dojox.mobile.RoundRectList._addChild.apply(this,arguments);this.redrawBorders();if(dojox.mobile.applyPngFilter){dojox.mobile.applyPngFilter(_6a.domNode);}},redrawBorders:function(){var _6b=false;for(var i=this.containerNode.childNodes.length-1;i>=0;i--){var c=this.containerNode.childNodes[i];if(c.tagName=="LI"){c.style.borderBottomStyle=_6b?"solid":"none";_6b=true;}}}});dojo.extend(dojox.mobile.EdgeToEdgeList,{buildRendering:function(){this.domNode=this.containerNode=this.srcNodeRef||dojo.doc.createElement("UL");this.domNode.className="mblEdgeToEdgeList";}});if(dojox.mobile.IconContainer){dojox.mobile.IconContainer._addChild=dojox.mobile.IconContainer.prototype.addChild;dojo.extend(dojox.mobile.IconContainer,{addChild:function(_6c){dojox.mobile.IconContainer._addChild.apply(this,arguments);if(dojox.mobile.applyPngFilter){dojox.mobile.applyPngFilter(_6c.domNode);}}});}dojo.mixin(dojox.mobile,{createRoundRect:function(_6d,_6e){var i;_6d.domNode=dojo.doc.createElement("DIV");_6d.domNode.style.padding="0px";_6d.domNode.style.backgroundColor="transparent";_6d.domNode.style.borderStyle="none";_6d.containerNode=dojo.doc.createElement(_6e?"UL":"DIV");_6d.containerNode.className="mblRoundRectContainer";if(_6d.srcNodeRef){_6d.srcNodeRef.parentNode.replaceChild(_6d.domNode,_6d.srcNodeRef);for(i=0,len=_6d.srcNodeRef.childNodes.length;i<len;i++){_6d.containerNode.appendChild(_6d.srcNodeRef.removeChild(_6d.srcNodeRef.firstChild));}_6d.srcNodeRef=null;}_6d.domNode.appendChild(_6d.containerNode);for(i=0;i<=5;i++){var top=dojo.create("DIV");top.className="mblRoundCorner mblRoundCorner"+i+"T";_6d.domNode.insertBefore(top,_6d.containerNode);var _6f=dojo.create("DIV");_6f.className="mblRoundCorner mblRoundCorner"+i+"B";_6d.domNode.appendChild(_6f);}}});if(dojox.mobile.ScrollableView){dojo.extend(dojox.mobile.ScrollableView,{postCreate:function(){var _70=dojo.create("DIV",{className:"mblDummyForIE",innerHTML:"&nbsp;"},this.containerNode,"first");dojo.style(_70,{position:"relative",marginBottom:"-2px",fontSize:"1px"});}});}}if(dojo.isIE<=6){dojox.mobile.applyPngFilter=function(_71){_71=_71||dojo.body();var _72=_71.getElementsByTagName("IMG");var _73=dojo.moduleUrl("dojo","resources/blank.gif");for(var i=0,len=_72.length;i<len;i++){var img=_72[i];var w=img.offsetWidth;var h=img.offsetHeight;if(w===0||h===0){if(dojo.style(img,"display")!="none"){continue;}img.style.display="";w=img.offsetWidth;h=img.offsetHeight;img.style.display="none";if(w===0||h===0){continue;}}var src=img.src;if(src.indexOf("resources/blank.gif")!=-1){continue;}img.src=_73;img.runtimeStyle.filter="progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+src+"')";img.style.width=w+"px";img.style.height=h+"px";}};}dojox.mobile.loadCss=function(_74){if(!dojo.global._loadedCss){var obj={};dojo.forEach(dojox.mobile.getCssPaths(),function(_75){obj[_75]=true;});dojo.global._loadedCss=obj;}if(!dojo.isArray(_74)){_74=[_74];}for(var i=0;i<_74.length;i++){var _76=_74[i];if(!dojo.global._loadedCss[_76]){dojo.global._loadedCss[_76]=true;if(dojo.doc.createStyleSheet){setTimeout(function(_77){return function(){dojo.doc.createStyleSheet(_77);};}(_76),0);}else{var _78=dojo.doc.createElement("link");_78.href=_76;_78.type="text/css";_78.rel="stylesheet";var _79=dojo.doc.getElementsByTagName("head")[0];_79.appendChild(_78);}}}};dojox.mobile.getCssPaths=function(){var _7a=[];var i,j;var s=dojo.doc.styleSheets;for(i=0;i<s.length;i++){var r=s[i].cssRules||s[i].imports;if(!r){continue;}for(j=0;j<r.length;j++){if(r[j].href){_7a.push(r[j].href);}}}var _7b=dojo.doc.getElementsByTagName("link");for(i=0,len=_7b.length;i<len;i++){if(_7b[i].href){_7a.push(_7b[i].href);}}return _7a;};dojox.mobile.loadCompatPattern=/\/themes\/(domButtons|buttons|iphone|android).*\.css$/;dojox.mobile.loadCompatCssFiles=function(){var _7c=dojox.mobile.getCssPaths();for(var i=0;i<_7c.length;i++){var _7d=_7c[i];if(_7d.match(dojox.mobile.loadCompatPattern)&&_7d.indexOf("-compat.css")==-1){var _7e=_7d.substring(0,_7d.length-4)+"-compat.css";dojox.mobile.loadCss(_7e);}}};dojox.mobile.hideAddressBar=function(){};dojo.addOnLoad(function(){if(dojo.config["mblLoadCompatCssFiles"]!==false){dojox.mobile.loadCompatCssFiles();}if(dojox.mobile.applyPngFilter){dojox.mobile.applyPngFilter();}});}}if(!dojo._hasResource["dojox.mobile.app.compat"]){dojo._hasResource["dojox.mobile.app.compat"]=true;dojo.provide("dojox.mobile.app.compat");dojo.extend(dojox.mobile.app.AlertDialog,{_doTransition:function(dir){var h=dojo.marginBox(this.domNode.firstChild).h;var _7f=this.controller.getWindowSize().h;var _80=_7f-h;var low=_7f;var _81=dojo.fx.slideTo({node:this.domNode,duration:400,top:{start:dir<0?_80:low,end:dir<0?low:_80}});var _82=dojo[dir<0?"fadeOut":"fadeIn"]({node:this.mask,duration:400});var _83=dojo.fx.combine([_81,_82]);var _84=this;dojo.connect(_83,"onEnd",this,function(){if(dir<0){_84.domNode.style.display="none";dojo.destroy(_84.domNode);dojo.destroy(_84.mask);}});_83.play();}});dojo.extend(dojox.mobile.app.List,{deleteRow:function(){var row=this._selectedRow;dojo.style(row,{visibility:"hidden",minHeight:"0px"});dojo.removeClass(row,"hold");var _85=dojo.contentBox(row).h;dojo.animateProperty({node:row,duration:800,properties:{height:{start:_85,end:1},paddingTop:{end:0},paddingBottom:{end:0}},onEnd:this._postDeleteAnim}).play();}});if(dojox.mobile.app.ImageView&&!dojo.create("canvas").getContext){dojo.extend(dojox.mobile.app.ImageView,{buildRendering:function(){this.domNode.innerHTML="ImageView widget is not supported on this browser."+"Please try again with a modern browser, e.g. "+"Safari, Chrome or Firefox";this.canvas={};},postCreate:function(){}});}if(dojox.mobile.app.ImageThumbView){dojo.extend(dojox.mobile.app.ImageThumbView,{place:function(_86,x,y){dojo.style(_86,{top:y+"px",left:x+"px",visibility:"visible"});}});}}