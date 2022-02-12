export default class DOMBuilder {
    constructor(parent) {
        this.parent = parent;
    }

    build(type, attributes) {
        this.elem = document.createElement(type);
        this.parent.appendChild(this.elem);

        Object.keys(attributes).forEach(key => {
            if (key == "innerHTML") {
                this.elem.innerHTML = attributes[key];
            } else {
                this.elem.setAttribute(key, attributes[key]);
            }
        });

        return new DOMBuilder(this.elem);
    }

    get lastElement() {
        return this.parent;
    }
}