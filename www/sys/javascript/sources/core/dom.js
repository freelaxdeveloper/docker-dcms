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