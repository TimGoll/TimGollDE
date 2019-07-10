export interface ICallbacks {
    on_progress? : (event : Object) => void,
    on_complete? : (event : Object) => void
};

export class AjaxHandler {
    should_cache : boolean;

    constructor(shoud_cache : boolean) {
        this.should_cache = shoud_cache;
    }

    transmit (url : string, data? : Object, callbacks? : ICallbacks) : void {
        let xmlhttp = new XMLHttpRequest();

        xmlhttp.onreadystatechange = () => {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
                if (callbacks !== undefined && callbacks.on_complete !== undefined)
                    callbacks.on_complete(xmlhttp.responseText);
        };

        xmlhttp.onprogress = (event) => {
            if (callbacks !== undefined && callbacks.on_progress !== undefined)
                callbacks.on_progress(event)
        };

        if (!this.should_cache)
            url += '?nocache=' + Math.random() * 1000000

        xmlhttp.open('GET', url);
        xmlhttp.send(JSON.stringify(data));
    };

    request (url : string, callbacks? : ICallbacks) : void {
        this.transmit(url, callbacks, {});
    }
}