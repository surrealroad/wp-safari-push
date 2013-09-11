// Javascript for communicating with push servers and getting permission from user
// Adapted from https://github.com/connorlacombe/Safari-Push-Notifications (@mynamesconnor)

window.onload = function() {
	console.log(SafariPushParams);
	if(SafariPushParams.websitePushID===null) {
		console.log("Website Push ID is missing");
	} else if(SafariPushParams.webServiceURL===null) {
		console.log("Web Service URL is missing");
	} else if(window.navigator.userAgent.indexOf('7.0 Safari') > -1) {
		surrealroad_safaripush_checkPermission();
	} else {
		// unsupported browser
		console.log("Unsupported browser");
	}
};

function surrealroad_safaripush_checkPermission() {
	var pResult = window.safari.pushNotification.permission(SafariPushParams.websitePushID);
	if(pResult.permission === 'default') {
		//request permission
		surrealroad_safaripush_requestPermission();
	}
	else if(pResult.permission === 'granted') {
		SafariPushParams.token = pResult.deviceToken;
	}
	else if(pResult.permission === 'denied') {
	}
	//console.log("Check: " + pResult);
}

function surrealroad_safaripush_requestPermission() {
	window.safari.pushNotification.requestPermission(SafariPushParams.webServiceURL, SafariPushParams.websitePushID, {}, function(c) {
		if(c.permission === 'granted') {
			SafariPushParams.token = pResult.deviceToken;
		}
		else if(c.permission === 'denied') {
		}
		//console.log("Request: " + c);
	});
}