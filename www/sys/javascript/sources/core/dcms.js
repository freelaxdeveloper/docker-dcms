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