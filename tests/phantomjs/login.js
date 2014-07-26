var page = new WebPage(),
	testindex = 0,
	loadInProgress = false;

page.onConsoleMessage = function(msg) {
	console.log(msg);
};

page.onLoadStarted = function() {
	loadInProgress = true;
	console.log("load started");
};

page.onLoadFinished = function() {
	loadInProgress = false;
	console.log("load finished");
};

var steps = [

	function() {
		//Load Login Page
		page.open("http://localhost/");
	},
	function() {
		//Enter Credentials
		page.evaluate(function() {
			var form = document.getElementById("loginForm");
			form.elements["login"].value = "admin";
			form.elements["password"].value = "password";
		});
	},
	function() {
		//Login
		page.evaluate(function() {
			var form = document.getElementById("loginForm");
			form.submit();
		});
	},
	function() {
		// Output content of page to stdout after form has been submitted
		page.evaluate(function() {
			console.log(document.querySelectorAll('html')[0].outerHTML);
		});
	},
	function() {
		// Make screenshot
		page.render('afterLogin.png');
	}
];

var waitCounter = 100;
var interval = setInterval(function() {

	if (!loadInProgress && typeof steps[testindex] == "function") {
		// Wait 5 seconds for GUI after login
		if (testindex === 3 && waitCounter > 0) {
			waitCounter--;
			return;
		}
		console.log("step " + (testindex + 1));
		steps[testindex]();
		testindex++;
	}
	if (typeof steps[testindex] != "function") {
		console.log("test complete!");
		phantom.exit();
	}
}, 50);
