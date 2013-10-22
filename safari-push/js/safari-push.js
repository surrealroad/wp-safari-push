// Javascript for communicating with push servers and getting permission from user
// Adapted from https://github.com/connorlacombe/Safari-Push-Notifications (@mynamesconnor)

jQuery( document ).ready(function() {
	//console.log(SafariPushParams);
	if(SafariPushParams.websitePushID===null) {
		console.log("Website Push ID is missing");
		SafariPushParams.status = "error";
	} else if(SafariPushParams.webServiceURL===null) {
		console.log("Web Service URL is missing");
		SafariPushParams.status = "error";
	} else if(window.navigator.userAgent.indexOf('7.0 Safari') > -1) {
		surrealroad_safaripush_checkPermission();
	} else {
		// unsupported browser
		//console.log("Unsupported browser");
		SafariPushParams.status = "unsupported";
	}
	surrealroad_safaripush_rendershortcode(SafariPushParams.status);
});

function surrealroad_safaripush_checkPermission() {
	var pResult = window.safari.pushNotification.permission(SafariPushParams.websitePushID);
	SafariPushParams.status = pResult.permission;
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
	window.safari.pushNotification.requestPermission(SafariPushParams.webServiceURL, SafariPushParams.websitePushID, null, surrealroad_safaripush_requestPermissionCallback);
}

function surrealroad_safaripush_requestPermissionCallback(permission) {
	if(permission.permission === 'granted') {
		SafariPushParams.token = permission.deviceToken;
	}
	else if(permission.permission === 'denied') {

	}
	//console.log("Request: " + permission);
	SafariPushParams.status = permission.permission;
	surrealroad_safaripush_rendershortcode(SafariPushParams.status);
}


// render [safari-push] shortcode
function surrealroad_safaripush_rendershortcode(status) {
	var html = "";
	switch(status) {
		case 'error' : html = SafariPushParams.errorMsg;
			break;
		case 'unsupported' : html = SafariPushParams.unsupportedMsg;
			break;
		case 'granted' : html = SafariPushParams.grantedMsg;
			break;
		case 'denied' : html = SafariPushParams.deniedMsg;
			break;
		default : html = SafariPushParams.defaultMsg;
	}
	jQuery(".safari-push-info").html(html);
}