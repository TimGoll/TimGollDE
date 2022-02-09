import * as integration from "./integration.js";

async function setup() {
	console.log(Date.now());

	let markdown = await integration.requestFile({
		origin : "https://raw.githubusercontent.com",
		owner : "TimGoll",
		repository : "multiprocessing",
		file : "README.md"
	});

	console.log(markdown)

	console.log(Date.now());

	let html = await integration.parseMarkdown({
		text: markdown,
		mode: "gfm",
		context : "TimGoll/multiprocessing"
	})

	console.log(Date.now());

	document.getElementById("content").innerHTML = html;
}

setup();
