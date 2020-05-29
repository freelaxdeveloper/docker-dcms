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