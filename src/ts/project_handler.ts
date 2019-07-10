import { AjaxHandler } from "./ajax";

export class ProjectHandler {
    constructor() {

    };

    init() : void {
        let test_ajax = new AjaxHandler(false);

        test_ajax.request('data/projects/test.json', {on_complete: (event) => {
            console.log(event);
        }});
    };
}