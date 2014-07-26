var page = new WebPage(),
	testindex = 0,
	loadInProgress = false;

page.viewportSize = {
	width: 1024,
	height: 768
};

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
		// logout
		page.evaluate(function() {
			function eventFire(el, etype) {
				if (el.fireEvent) {
					el.fireEvent('on' + etype);
				} else {
					var evObj = document.createEvent('Events');
					evObj.initEvent(etype, true, false);
					el.dispatchEvent(evObj);
				}
			}

			function isVisible(el) {
				return el.offsetWidth > 0 && el.offsetHeight > 0;
			}

			// Show menu
			eventFire(document.querySelector('#dijit_form_DropDownButton_0_label'), 'mousedown');

			var logoutMenuEntry = document.querySelector('#dijit_MenuItem_16');
			if (!logoutMenuEntry || !isVisible(logoutMenuEntry)) {
				console.error('Logout menu entry not found');
				phantom.exit(1);
			}
			eventFire(document.querySelector('#dijit_MenuItem_16'), 'click');
		});
	},
	function() {
		// Check if logout was successful
		page.evaluate(function() {
			if (document.title.trim() !== "Tiny Tiny RSS : Login") {
				console.error('Logout unsuccessful');
				phantom.exit(1);
			}
		});
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
