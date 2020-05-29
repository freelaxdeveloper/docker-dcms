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