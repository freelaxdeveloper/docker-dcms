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