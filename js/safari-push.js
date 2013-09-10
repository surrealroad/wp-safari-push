// Javascript for communicating with push servers and getting permission from user
// Adapted from https://github.com/connorlacombe/Safari-Push-Notifications (@mynamesconnor)

window.onload = function() {
	if(window.navigator.userAgent.indexOf('7.0 Safari') > -1) {
		surrealroad_safaripush_checkPermission();
	}
	else {
		// unsupported browser
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
	console.log("Check: " + pResult);
}

function surrealroad_safaripush_requestPermission() {
	window.safari.pushNotification.requestPermission(SafariPushParams.webServiceURL, SafariPushParams.websitePushID, {"id": SafariPushParams.id}, function(c) {
		if(c.permission === 'granted') {
			SafariPushParams.token = pResult.deviceToken;
		}
		else if(c.permission === 'denied') {
		}
		console.log("Request: " + c);
	});
}
