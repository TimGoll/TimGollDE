import { AjaxHandler } from "./ajax";
import { Dictionary } from "./generic_types";

export class ProjectsHandler {
    private ajax : AjaxHandler;

    constructor() {
        this.ajax = new AjaxHandler(false);
    };

    init() : void {
        //TODO this is obviously just a test function
        this.requestProjectsList((data) => {
            for (let key in data) {
                let topic = data[key];
                console.log(topic);
                this.requestProject(topic['path'], (event) => {
                    console.log(event)
                })
            }
        });
    };

    private requestProjectsList(callback: (event : Dictionary) => void, type? : string) : void {
        this.ajax.send({
            url: 'server/projects.php',
            contents: {
                'action': 'request_project_list',
                'type': type
            },
            type: 'post',
            on_complete: (event) => {
                callback(JSON.parse(event));
            }
        });
    };

    private requestProject(path : string, callback: (event : Dictionary) => void) : void {
        this.ajax.send({
            url: path,
            on_complete: (event) => {
                callback(JSON.parse(event))
            }
        });
    };
}