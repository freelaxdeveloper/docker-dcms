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