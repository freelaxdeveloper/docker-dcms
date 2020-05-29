/* DCMS jsBuild system */



// FILE core/dcms.js
var DCMS = {
    StyleAnimation: true,
    StyleAnimationDuration: 600
};

DCMS.getEventHandler = function(func, context){
    context = context || window;
    if (typeof func !== 'function')
        throw new TypeError("Обработчиком события должна быть функция");
    
    return function(){
        return func.apply(context, arguments);
    };
    
};

DCMS.delay = function(delay, funct, context){
    setTimeout(DCMS.getEventHandler(funct, context), delay);    
};


DCMS.isArray = function(array){
    return (typeof(array)=='object') && (array instanceof Array);
};

DCMS.isEmpty = function(mixed_var) {
    if (mixed_var === "" || mixed_var === 0 || mixed_var === "0" || mixed_var === null || mixed_var === false || typeof mixed_var === 'undefined') {
        return true;
    } 
    if (typeof mixed_var == 'object') {
        for (var key in mixed_var) {
            return false;
        }
        return true;
    } 
    return false;
}

DCMS.isScalar = function (mixed_var) {
    return (/boolean|number|string/).test(typeof mixed_var);
};

DCMS.isNumber = function (mixed_var) {
    return !isNaN(parseFloat(mixed_var)) && isFinite(mixed_var);
};

DCMS.isFunction = function(func){
    return (typeof func == 'function');
};

DCMS.isDom = function(dom){
    return dom && DCMS.isFunction(dom.appendChild);
};

DCMS.objectToPost = function(obj){
    if (DCMS.isScalar(obj))
        return obj;
    var pairs = [];
    
    for (var key in obj){
        pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(obj[key]));
    }
    
    
    return pairs.join('&');
};

DCMS.countProp = function(obj){
    var count = 0;
        
    for (var prop in obj){
        if (!DCMS.isFunction(obj[prop]))
            count++;
    }    
    
    return count;
};

// FILE core/ajax.js
// include "core/dcms.js"

function getXmlHttp() {
    var xmlhttp;
    try {
        xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
        try {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        } catch (E) {
            xmlhttp = false;
        }
    }
    if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
        xmlhttp = new XMLHttpRequest();
    }
    return xmlhttp;
}

DCMS.Ajax = function(settings) {
    if (!settings)
        throw "Не заданы параметры запроса";

    var url = settings.url.split('?');
    url[1] =  (url[1] ? url[1] + '&': '') + '_='+Math.random().toString();
        

    var xhr = getXmlHttp();
    xhr.open(settings.post ? "POST" : 'GET', url.join('?'), true);
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    if (settings.post){
        xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    }
    xhr.onreadystatechange = function() {
        if (xhr.readyState != 4)
            return;
        if (xhr.status == 200) {
            if (settings.callback)
                settings.callback.call(this, xhr.responseText);
        } else {
            if (settings.error)
                settings.error.call(this, xhr.statusText);
        }
    }
        
    xhr.send(DCMS.objectToPost(settings.post));
};

// FILE core/animate.js
// include "core/dcms.js"

DCMS.Animate = function(duration, callback, delta){
    
    if (delta && DCMS.Animate.deltaFunctions[delta])
        this.delta = DCMS.Animate.deltaFunctions[delta];    
    else 
        this.delta = DCMS.Animate.deltaFunctions['life'];
          
    
    if (typeof callback != 'function')
        throw new Error('callback не является функцией');
    else
        this.callback = callback;
    
    this.duration = duration || 1000;
    this.runned = true;
    this.interval = 20;
    this.start = new Date().getTime();
    this.Step();
};

    DCMS.Animate.prototype.End = function(to_end){
        if (!this.runned)
            return;
        this.runned = false;
    
        if (to_end)
            this.callback(1);
    }

    DCMS.Animate.prototype.Step = function(){
        if (!this.runned)
            return;
        
        var step = (new Date().getTime() - this.start)/ this.duration;
        
        if (step >= 1)
            step = 1;
        
        this.callback(step == 1 ? 1 :this.delta(step));        
        
        if (step < 1)
            setTimeout(DCMS.getEventHandler(this.Step, this), this.interval);
    };

    DCMS.Animate.deltaFunctions = {
        linear: function (input){
            return input;
        },
        drop: function(input){
            return DCMS.Animate.deltaFunctions._easeOut(DCMS.Animate.deltaFunctions._bounce)(input);        
        },
        life: function(input){
            return DCMS.Animate.deltaFunctions._easeOut(DCMS.Animate.deltaFunctions._quad)(input);
        },
        _bounce: function (progress) {
            for (var a = 0, b = 1, result; 1; a += b, b /= 2) {
                if (progress >= (7 - 4 * a) / 11) {
                    return -Math.pow((11 - 6 * a - 11 * progress) / 4, 2) + Math.pow(b, 2);
                }
            }
            return 1;
        },
        _easeOut: function (delta) {
            return function(progress) {
                return 1 - delta(1 - progress);
            }
        },
        _quad: function (progress) {
            return Math.pow(progress, 4);
        }

    };

// FILE core/dom.js
// include "core/dcms.js"
DCMS.Dom = {
    
    };


    DCMS.Dom.setStyle = function(dom, prop, value){
        dom.style[prop.toCamel()] = value;
    };

    DCMS.Dom.getComputedValue = function(dom, prop){
        var dash_prop = prop.toDash();
        
        switch(dash_prop){
            case 'height':
                return dom.offsetHeight + 'px';
            case 'width':
                return dom.offsetWidth + 'px';
            case 'left':
                return dom.offsetLeft + 'px';
            case 'top':
                return dom.offsetTop + 'px';
        }        
        
        return window.getComputedStyle(dom, null).getPropertyValue(dash_prop);
    };

    DCMS.Dom.getProbeValue = function(dom, prop, value){
        var tmp_val = getComputedStyle(dom, null).getPropertyValue(prop.toDash());
        dom.style[prop] = value;
        var def_val = getComputedStyle(dom, null).getPropertyValue(prop.toDash());
        dom.style[prop] = tmp_val;
        return def_val;
    };

    DCMS.Dom.getDefaultValue = function(dom, prop){
        return DCMS.Dom.getProbeValue(dom, prop, '');
    };

    DCMS.Dom.classAdd = function(domNode, className){
        if (domNode == undefined || domNode.className == undefined)
            return;
    
        if (className instanceof Array){
            for(var i = 0; i < className.length; i++)
                DCMS.Dom.classAdd(domNode, className[i]);
            return;
        }   
    
        if (DCMS.Dom.classHas(domNode, className))
            return;
    
        var classes = domNode.className.split(' ');
        classes.push(className);
        domNode.className = classes.join(' ').trim();
    };

    DCMS.Dom.classRemove = function(domNode, className){
        if (domNode == undefined || domNode.className == undefined)
            return;

        if (!DCMS.Dom.classHas(domNode, className))
            return;
        var classes = domNode.className.split(' ');
        var classesSet = [];
        for (var i = 0; i < classes.length; i++){
            if (classes[i] == className)
                continue;
            classesSet.push(classes[i]);
        }
        domNode.className = classesSet.join(' ');
    };

    DCMS.Dom.classHas = function(domNode, className){
        if (domNode == undefined || domNode.className == undefined)
            return false;
        
        return ~domNode.className.split(' ').indexOf(className);
    };

    DCMS.Dom.create = function(tagName, classes, parent, before){
        var dom = document.createElement(tagName);
    
        if (classes)
            DCMS.Dom.classAdd(dom, classes);
    
        if (DCMS.isDom(parent)){
            if (DCMS.isDom(before))
                parent.insertBefore(dom, before);            
            else
                parent.appendChild(dom);            
        }           
    
        return dom;
    };

    DCMS.Dom.createFromHtml = function(html, classes, parent, before){
        var div = document.createElement('div');
        try{
            div.innerHTML = html;
        }
        catch(e){
            console.log(e);
        }
        
        var dom = div.firstChild;
    
        if (classes)
            DCMS.Dom.classAdd(dom, classes);
    
        if (DCMS.isDom(parent)){
            if (dom.id && document.getElementById(dom.id))
                return dom;
            
            if (DCMS.isDom(before))
                parent.insertBefore(dom, before);            
            else
                parent.appendChild(dom);            
        } 
    
        return dom;
    };

    DCMS.Dom.parseStyle = function(value){    
        var p = parseFloat(value);
        var q = value.replace(/^[\-\d\.]+/,'');
        return isNaN(p) ? {
            value: q, 
            units: ''
        } : {
            value: p, 
            units: q
        };
    };
    
    DCMS.Dom.inputInsert = function(node, Open, Close, CursorEnd) {    
        node.focus();
        if (window.attachEvent && navigator.userAgent.indexOf('Opera') === -1) {                                        
            var s = node.sel;
            if(s){                                  
                var l = s.text.length;
                s.text = Open + s.text + Close;
                s.moveEnd("character", -Close.length);
                s.moveStart("character", -l);                                           
                s.select();                
            }
        } else {                                              
            var ss = node.scrollTop;
            var sel1 = node.value.substr(0, node.selectionStart);
            var sel2 = node.value.substr(node.selectionEnd);
            var sel = node.value.substr(node.selectionStart, node.selectionEnd - node.selectionStart);  
        
        
            node.value = sel1 + Open + sel + Close + sel2;
            if (CursorEnd){
                node.selectionStart = sel1.length + Open.length + sel.length + Close.length;
                node.selectionEnd = node.selectionStart;
            }else{            
                node.selectionStart = sel1.length + Open.length;
                node.selectionEnd = node.selectionStart + sel.length;            
            }
            node.scrollTop = ss; 
                                                    
        }
        return false;
    }

// FILE core/animation.js
// include "core/animate.js"
// include "core/dom.js"
DCMS.Animation = {
   
    };

    DCMS.Animation._AnimatingNodes = [];
    DCMS.Animation._AnimatingProperties = [];
    DCMS.Animation._AnimatingAnimates = [];

    DCMS.Animation.addToList = function(dom, property, animate){
        DCMS.Animation._AnimatingNodes.push(dom);
        DCMS.Animation._AnimatingProperties.push(property);
        DCMS.Animation._AnimatingAnimates.push(animate);
    };
    
    DCMS.Animation.deleteFromlist = function(index){
        DCMS.Animation._AnimatingNodes.splice(index, 1);
        DCMS.Animation._AnimatingProperties.splice(index, 1);
        DCMS.Animation._AnimatingAnimates.splice(index, 1);
    };
    
    DCMS.Animation.getIndexByProp = function(dom, property){
        for (var i = 0 ; i < DCMS.Animation._AnimatingNodes.length; i++){            
            if (DCMS.Animation._AnimatingNodes[i] == dom && DCMS.Animation._AnimatingProperties[i] == property)
                return i;
        }
        return -1;
    };

    DCMS.Animation.stop = function(dom, property, to_end_step){    
        var index = DCMS.Animation.getIndexByProp(dom, property);    
        if (~index){
            // console.log('stop', dom, property);
            DCMS.Animation._AnimatingAnimates[index].End(!!to_end_step);
            DCMS.Animation.deleteFromlist(index);
        }    
    };

    DCMS.Animation.colorStep = function(color1, color2, step){    
        if (!(color1 instanceof RGBColor))
            color1 = new RGBColor(color1);
        
        if (!(color2 instanceof RGBColor))
            color2 = new RGBColor(color2);
        
        var r =  parseInt(color1.r + (color2.r - color1.r) * step);
        var g =  parseInt(color1.g + (color2.g - color1.g) * step);
        var b =  parseInt(color1.b + (color2.b - color1.b) * step);
        
        if (color1.a == 0){
            r =  color2.r;
            g =  color2.g;
            b =  color2.b;
        }
        
        if (color2.a == 0){
            r =  color1.r;
            g =  color1.g;
            b =  color1.b;
        }
        
        var a =  parseFloat(color1.a + (color2.a - color1.a) * step);
        
        return new RGBColor('rgba(' + r + ', ' + g + ',' + b +',' + a +')');
    };

    
    DCMS.Animation.style = function(dom, property, value, duration, callbackEnd){
        if (!dom || !dom.style)
            return false;
       
        DCMS.Animation.stop(dom, property);
    
        if (!DCMS.isNumber(duration))
            duration = DCMS.StyleAnimationDuration;
        if (duration > 0 && duration < 30) // считаем, что задано в секундах
            duration *= 1000;
        
        property = property.toCamel();
    
        var styleStart, styleEnd;
        if (DCMS.isArray(value) && value.length == 2){
            styleStart = DCMS.Dom.parseStyle(value[0] === '' ? DCMS.Dom.getComputedValue(dom, property) : value[0]);
            styleEnd = DCMS.Dom.parseStyle(value[1] === '' ? DCMS.Dom.getDefaultValue(dom, property): value[1]); 
            value = value[1];
        }else{
            styleStart = DCMS.Dom.parseStyle(DCMS.Dom.getComputedValue(dom, property));
            styleEnd = DCMS.Dom.parseStyle(value === '' ? DCMS.Dom.getDefaultValue(dom, property): value);            
        }
    
        if (styleEnd.units == '%')
            styleEnd = DCMS.Dom.parseStyle(DCMS.Dom.getProbeValue(dom, property, styleEnd.value + styleEnd.units));
    
        if (styleStart.value && styleStart.units && styleEnd.units && styleStart.units != styleEnd.units)
            return false;
    
        var units =  styleEnd.units || styleStart.units;    
        
        var colorEnd = new RGBColor(styleEnd.value);
        var colorStart = new RGBColor(styleStart.value);            
    
        var callback = function(step){
            //console.log(step);
            if (colorEnd.ok){
                dom.style[property] = DCMS.Animation.colorStep(colorStart, colorEnd, step);
            //console.log(DCMS.Animation.colorStep(colorStart, colorEnd, step));                
            }else if (DCMS.isNumber(styleEnd.value)){
                dom.style[property] = parseFloat(styleStart.value + (styleEnd.value - styleStart.value) * step).toFixed(2) + units; 
            } else{
                step = 1;
            }
                        
            if (step == 1){
                dom.style[property] = value;
                
                DCMS.Animation.stop(dom, property);
                if (DCMS.isFunction(callbackEnd))
                    callbackEnd.call(dom);
            }                            
        }
    
        if (DCMS.StyleAnimation && duration)
            DCMS.Animation.addToList(dom, property, new DCMS.Animate(duration, callback));
        else
            callback(1);
    
        return true;
    };

// FILE core/event.js
//include "core/dcms.js"
DCMS.Event = {
        /**
     * Подписка на событие
     * Использование:
     *  menu.on('select', function(item) { ... }
     */
        on: function(eventName, handler) {
            if (!this._eventHandlers)
                this._eventHandlers = [];
            if (!this._eventHandlers[eventName]) {
                this._eventHandlers[eventName] = [];
            }
            this._eventHandlers[eventName].push(handler);
        },
        /**
     * Прекращение подписки
     *  menu.off('select',  handler)
     */
        off: function(eventName, handler) {
            var handlers = this._eventHandlers[eventName];
            if (!handlers)
                return;
            for (var i = 0; i < handlers.length; i++) {
                if (handlers[i] == handler) {
                    handlers.splice(i--, 1);
                }
            }
        },
        /**
     * Генерация события с передачей данных
     *  this.trigger('select', item);
     */
        trigger: function(eventName) {

            if (!this._eventHandlers[eventName]) {
                return; // обработчиков для события нет
            }

            // вызвать обработчики 
            var handlers = this._eventHandlers[eventName];
            for (var i = 0; i < handlers.length; i++) {
                handlers[i].apply(this, [].slice.call(arguments, 1));
            }

        }
    }

// FILE core/ready.js
function ready(f){
    DCMS.Event.on('ready', f);
}

if ( document.addEventListener ) {
    // Use the handy event callback
    document.addEventListener( "DOMContentLoaded", function(){
        document.removeEventListener( "DOMContentLoaded", arguments.callee, false );
        DCMS.Event.trigger('ready');
    }, false );

// If IE event model is used
} else if ( document.attachEvent ) {
    // ensure firing before onload,
    // maybe late but safe also for iframes
    document.attachEvent("onreadystatechange", function(){
        if ( document.readyState === "complete" ) {
            document.detachEvent( "onreadystatechange", arguments.callee );
            DCMS.Event.trigger('ready');
        }
    });
}else{
    window.onload = function(){
        DCMS.Event.trigger('ready');
    } 
}

DCMS.listing_update = function(url, ids, callback, callback_err){
    DCMS.Ajax({
        url: url,
        post:'skip_ids='+ids.join(','),
        callback: callback,
        error: callback_err
    });
};

// FILE core/user_update.js
//include "core/dcms.js"
DCMS.UserUpdate = {
        fields: ['mail_new_count', 'friend_new_count'],
        errors: 0,
        time_last: 0,
        id_user: null,
        timeout: null,
        interval: 7,
        delay_update: function(sec) {
            sec = sec || this.interval;
            var self = this;
            self.stop();
            self.timeout = setTimeout(function() {
                self.update.call(self);
            }, sec * 1000);
        },
        update: function() {
            var self = this;
            DCMS.Ajax({
                url: '/ajax/user.json.php?' + this.fields.join('&'),
                callback: function() {
                    self.onresult.apply(self, arguments);
                },
                error: function() {
                    self.onerror.call(self);
                }
            })
        },
        onerror: function() {
            this.errors++;
            this.delay_update(30 * this.errors);
        },
        onresult: function(data) {
            DCMS.Event.trigger('user_update', JSON.parse(data));
            this.errors = 0;
            this.delay_update(this.interval);
        },
        stop: function() {
            if (this.timeout)
                clearTimeout(this.timeout);
        }
    }

// FILE plugins/event.js
Event = (function() {

    var guid = 0;
    
    function fixEvent(event) {
        event = event || window.event;
  
        if ( event.isFixed ) {
            return event;
        }
        event.isFixed = true;
  
        event.preventDefault = event.preventDefault || function(){
            this.returnValue = false;
        }
        event.stopPropagation = event.stopPropagaton || function(){
            this.cancelBubble = true;
        }
    
        if (!event.target) {
            event.target = event.srcElement;
        }
  
        if (!event.relatedTarget && event.fromElement) {
            event.relatedTarget = event.fromElement == event.target ? event.toElement : event.fromElement;
        }
  
        if ( event.pageX == null && event.clientX != null ) {
            var html = document.documentElement, body = document.body;
            event.pageX = event.clientX + (html && html.scrollLeft || body && body.scrollLeft || 0) - (html.clientLeft || 0);
            event.pageY = event.clientY + (html && html.scrollTop || body && body.scrollTop || 0) - (html.clientTop || 0);
        }
  
        if ( !event.which && event.button ) {
            event.which = (event.button & 1 ? 1 : ( event.button & 2 ? 3 : ( event.button & 4 ? 2 : 0 ) ));
        }
	
        return event;
    }  
  
    /* Вызывается в контексте элемента всегда this = element */
    function commonHandle(event) {
        event = fixEvent(event);
    
        var handlers = this.events[event.type];

        for ( var g in handlers ) {
            var handler = handlers[g];
            var ret = handler.call(this, event);
            if ( ret === false ) {
                event.preventDefault();
                event.stopPropagation();
            }
        }
    }
  
    return {
        add: function(elem, types, handler) {
            if (elem.setInterval && ( elem != window && !elem.frameElement ) ) {
                elem = window;
            }
      
            if (!handler.guid) {
                handler.guid = ++guid;
            }
      
            if (!elem.events) {
                elem.events = {};
                elem.handle = function(event) {
                    if (typeof Event !== "undefined") {
                        return commonHandle.call(elem, event);
                    }
                }
            }
	  
            types = DCMS.isArray(types)?types:[types];
            for (var i=0; i<types.length; i++){
                var type = types[i];
                if (!elem.events[type]) {
                    elem.events[type] = {};        
      
                    if (elem.addEventListener)
                        elem.addEventListener(type, elem.handle, false);
                    else if (elem.attachEvent)
                        elem.attachEvent("on" + type, elem.handle);
                }
      
                elem.events[type][handler.guid] = handler;
            
            }
        },
    
        remove: function(elem, type, handler) {
            var handlers = elem.events && elem.events[type];
      
            if (!handlers) return;
      
            delete handlers[handler.guid];
      
            for(var any in handlers) return ;
            if (elem.removeEventListener)
                elem.removeEventListener(type, elem.handle, false);
            else if (elem.detachEvent)
                elem.detachEvent("on" + type, elem.handle);
		
            delete elem.events[type];
	
	  
            for (var any in elem.events)
                return;
            try {
                delete elem.handle;
                delete elem.events; 
            } catch(e) { // IE
                elem.removeAttribute("handle");
                elem.removeAttribute("events");
            }
        } 
    }
}());

// FILE plugins/rgbcolor.js
function RGBColor(color_string)
{
    color_string = color_string.toString();
    this.ok = false;     
    color_string = color_string.replace(/ /g,'');
    color_string = color_string.toLowerCase();

    // before getting into regexps, try simple matches
    // and overwrite the input
    var simple_colors = {
        aliceblue: 'f0f8ff',
        antiquewhite: 'faebd7',
        aqua: '00ffff',
        aquamarine: '7fffd4',
        azure: 'f0ffff',
        beige: 'f5f5dc',
        bisque: 'ffe4c4',
        black: '000000',
        blanchedalmond: 'ffebcd',
        blue: '0000ff',
        blueviolet: '8a2be2',
        brown: 'a52a2a',
        burlywood: 'deb887',
        cadetblue: '5f9ea0',
        chartreuse: '7fff00',
        chocolate: 'd2691e',
        coral: 'ff7f50',
        cornflowerblue: '6495ed',
        cornsilk: 'fff8dc',
        crimson: 'dc143c',
        cyan: '00ffff',
        darkblue: '00008b',
        darkcyan: '008b8b',
        darkgoldenrod: 'b8860b',
        darkgray: 'a9a9a9',
        darkgreen: '006400',
        darkkhaki: 'bdb76b',
        darkmagenta: '8b008b',
        darkolivegreen: '556b2f',
        darkorange: 'ff8c00',
        darkorchid: '9932cc',
        darkred: '8b0000',
        darksalmon: 'e9967a',
        darkseagreen: '8fbc8f',
        darkslateblue: '483d8b',
        darkslategray: '2f4f4f',
        darkturquoise: '00ced1',
        darkviolet: '9400d3',
        deeppink: 'ff1493',
        deepskyblue: '00bfff',
        dimgray: '696969',
        dodgerblue: '1e90ff',
        feldspar: 'd19275',
        firebrick: 'b22222',
        floralwhite: 'fffaf0',
        forestgreen: '228b22',
        fuchsia: 'ff00ff',
        gainsboro: 'dcdcdc',
        ghostwhite: 'f8f8ff',
        gold: 'ffd700',
        goldenrod: 'daa520',
        gray: '808080',
        green: '008000',
        greenyellow: 'adff2f',
        honeydew: 'f0fff0',
        hotpink: 'ff69b4',
        indianred : 'cd5c5c',
        indigo : '4b0082',
        ivory: 'fffff0',
        khaki: 'f0e68c',
        lavender: 'e6e6fa',
        lavenderblush: 'fff0f5',
        lawngreen: '7cfc00',
        lemonchiffon: 'fffacd',
        lightblue: 'add8e6',
        lightcoral: 'f08080',
        lightcyan: 'e0ffff',
        lightgoldenrodyellow: 'fafad2',
        lightgrey: 'd3d3d3',
        lightgreen: '90ee90',
        lightpink: 'ffb6c1',
        lightsalmon: 'ffa07a',
        lightseagreen: '20b2aa',
        lightskyblue: '87cefa',
        lightslateblue: '8470ff',
        lightslategray: '778899',
        lightsteelblue: 'b0c4de',
        lightyellow: 'ffffe0',
        lime: '00ff00',
        limegreen: '32cd32',
        linen: 'faf0e6',
        magenta: 'ff00ff',
        maroon: '800000',
        mediumaquamarine: '66cdaa',
        mediumblue: '0000cd',
        mediumorchid: 'ba55d3',
        mediumpurple: '9370d8',
        mediumseagreen: '3cb371',
        mediumslateblue: '7b68ee',
        mediumspringgreen: '00fa9a',
        mediumturquoise: '48d1cc',
        mediumvioletred: 'c71585',
        midnightblue: '191970',
        mintcream: 'f5fffa',
        mistyrose: 'ffe4e1',
        moccasin: 'ffe4b5',
        navajowhite: 'ffdead',
        navy: '000080',
        oldlace: 'fdf5e6',
        olive: '808000',
        olivedrab: '6b8e23',
        orange: 'ffa500',
        orangered: 'ff4500',
        orchid: 'da70d6',
        palegoldenrod: 'eee8aa',
        palegreen: '98fb98',
        paleturquoise: 'afeeee',
        palevioletred: 'd87093',
        papayawhip: 'ffefd5',
        peachpuff: 'ffdab9',
        peru: 'cd853f',
        pink: 'ffc0cb',
        plum: 'dda0dd',
        powderblue: 'b0e0e6',
        purple: '800080',
        red: 'ff0000',
        rosybrown: 'bc8f8f',
        royalblue: '4169e1',
        saddlebrown: '8b4513',
        salmon: 'fa8072',
        sandybrown: 'f4a460',
        seagreen: '2e8b57',
        seashell: 'fff5ee',
        sienna: 'a0522d',
        silver: 'c0c0c0',
        skyblue: '87ceeb',
        slateblue: '6a5acd',
        slategray: '708090',
        snow: 'fffafa',
        springgreen: '00ff7f',
        steelblue: '4682b4',
        tan: 'd2b48c',
        teal: '008080',
        thistle: 'd8bfd8',
        tomato: 'ff6347',
        turquoise: '40e0d0',
        violet: 'ee82ee',
        violetred: 'd02090',
        wheat: 'f5deb3',
        white: 'ffffff',
        whitesmoke: 'f5f5f5',
        yellow: 'ffff00',
        yellowgreen: '9acd32'
    };
    for (var key in simple_colors) {
        if (color_string == key) {
            color_string = simple_colors[key];
        }
    }
    // emd of simple type-in colors

    // array of color definition objects
    var color_defs = [
    {
        re: /^rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)$/,
        example: ['rgb(123, 234, 45)', 'rgb(255,234,245)'],
        process: function (bits){
            return [
            parseInt(bits[1]),
            parseInt(bits[2]),
            parseInt(bits[3])
            ];
        }
    },
    {
        re: /^rgba\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3}),\s*(\d{1}(.\d+)?)\)$/,
        example: ['rgba(123, 234, 45, 0.34)', 'rgba(255,234,245,1)'],
        process: function (bits){
            return [
            parseInt(bits[1]),
            parseInt(bits[2]),
            parseInt(bits[3]),            
            parseFloat(bits[4])
            ];
        }
    },
    {
        re: /^#(\w{2})(\w{2})(\w{2})(\w{2})$/,
        example: ['#00ff00ff', '336699ff'], // формат прозрачности осла
        process: function (bits){
            return [
            parseInt(bits[1], 16),
            parseInt(bits[2], 16),
            parseInt(bits[3], 16),
            parseInt(bits[4], 16)/255
            ];
        }
    },
    {
        re: /^#(\w{2})(\w{2})(\w{2})$/,
        example: ['#00ff00', '336699'],
        process: function (bits){
            return [
            parseInt(bits[1], 16),
            parseInt(bits[2], 16),
            parseInt(bits[3], 16)
            ];
        }
    },
    {
        re: /^#(\w{1})(\w{1})(\w{1})(\w{1})$/,
        example: ['#fb0f', 'f0ff'],
        process: function (bits){
            return [
            parseInt(bits[1] + bits[1], 16),
            parseInt(bits[2] + bits[2], 16),
            parseInt(bits[3] + bits[3], 16),
            parseInt(bits[4] + bits[4], 16)/255
            ];
        }
    },
    {
        re: /^#(\w{1})(\w{1})(\w{1})$/,
        example: ['#fb0', 'f0f'],
        process: function (bits){
            return [
            parseInt(bits[1] + bits[1], 16),
            parseInt(bits[2] + bits[2], 16),
            parseInt(bits[3] + bits[3], 16)
            ];
        }
    }
    ];

    // search through the definitions to find a match
    for (var i = 0; i < color_defs.length; i++) {
        var re = color_defs[i].re;
        var processor = color_defs[i].process;
        var bits = re.exec(color_string);
        if (bits) {
            channels = processor(bits);
            this.r = channels[0];
            this.g = channels[1];
            this.b = channels[2];
            this.a = channels[3] == undefined ? 1: channels[3];
            this.ok = true;
        }

    }

    // validate/cleanup values
    this.r = (this.r < 0 || isNaN(this.r)) ? 0 : ((this.r > 255) ? 255 : this.r);
    this.g = (this.g < 0 || isNaN(this.g)) ? 0 : ((this.g > 255) ? 255 : this.g);
    this.b = (this.b < 0 || isNaN(this.b)) ? 0 : ((this.b > 255) ? 255 : this.b);
    this.a = (this.a < 0 || isNaN(this.a)) ? 0 : ((this.a > 1) ? 1 : this.a);

    this.toRGBA = function () {
        return 'rgba(' + this.r + ', ' + this.g + ', ' + this.b + ', ' + this.a +')';
    }
    
    this.toRGB = function () {
        return 'rgb(' + this.r + ', ' + this.g + ', ' + this.b + ')';
    }
    
    this.toHex = function () {
        var r = this.r.toString(16);
        var g = this.g.toString(16);
        var b = this.b.toString(16);
        if (r.length == 1) r = '0' + r;
        if (g.length == 1) g = '0' + g;
        if (b.length == 1) b = '0' + b;
        return '#' + r + g + b;
    }
    
    this.toHexA = function () {
        var r = this.r.toString(16);
        var g = this.g.toString(16);
        var b = this.b.toString(16);
        
        var a = parseInt(this.a * 255).toString(16);
                
        if (r.length == 1) r = '0' + r;
        if (g.length == 1) g = '0' + g;
        if (b.length == 1) b = '0' + b;
        if (a.length == 1) a = '0' + a;
        return '#' + r + g + b + a;
    }

    this.toString = this.toRGBA;

}

// FILE plugins/string.js
String.prototype.trim = function(){
    return this.replace(/^\s+|\s+$/g, "");
};

    String.prototype.toCamel = function(){
        return this.replace(/(\-[a-z])/g, function($1){
            return $1.toUpperCase().replace('-','');
        });
    };

    String.prototype.toDash = function(){
        return this.replace(/([A-Z])/g, function($1){
            return "-"+$1.toLowerCase();
        });
    };

    String.prototype.toUnderscore = function(){
        return this.replace(/([A-Z])/g, function($1){
            return "_"+$1.toLowerCase();
        });
    };

