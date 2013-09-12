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
		case 'error' : html = '<div class="alert alert-danger"><p>Something went wrong communicating with the push notification server, plesae try again later.</p></div>';
			break;
		case 'unsupported' : html = '<div class="alert alert-warning"><p>To enable or modify push notifications for this site, use Safari 7.0 or newer.</p></div>';
			break;
		case 'default' : html = '<div class="alert alert-info"><p>To enable push notifications for this site, click "Allow" when Safari asks you.</p></div>';
			break;
		case 'granted' : html = '<div class="alert alert-success"><p>Push notifications are enabled for this site.</p></div>';
			break;
		case 'denied' : html = '<div class="alert alert-warning"><p>You have opted not to receive push notifications from us.</p><button class="btn btn-default btn-small" onClick="surrealroad_safaripush_requestPermission();">Enable push notifications</button></div>';
			break;
	}
	jQuery(".safari-push-info").html(html);
}