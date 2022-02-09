import * as integration from "./integration.js";

async function setup() {
	console.log(Date.now());

	let markdown = await integration.requestFile({
		origin : "https://raw.githubusercontent.com",
		owner : "TimGoll",
		repository : "projektpraktikum",
		file : "README.md"
	});

	console.log(markdown)

	console.log(Date.now());

	let html = await integration.parseMarkdown({
		text: markdown
	})

	console.log(Date.now());

	document.body.innerHTML = html;
}

setup();
