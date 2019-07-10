import { AjaxHandler } from "./ajax";

export class ProjectsHandler {
    private ajax : AjaxHandler;

    constructor() {
        this.ajax = new AjaxHandler(false);
    };

    init() : void {
        this.requestProjectsList();
    };


    private requestProjectsList(type? : string) : void {
        this.ajax.send({
            url: 'server/projects.php',
            contents: {
                'action': 'request_project_list',
                'type': type
            },
            type: 'post',
            on_complete: (event) => {
                console.log("test");
                console.log(JSON.parse(event));
            }
        });
    };
}