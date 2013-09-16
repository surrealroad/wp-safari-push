wp-safari-push
==

A Safari Push Plugin for Wordpress

What does this plugin do?
--
This plugin lets you prompt users to allow Safari Push notifications from your Wordpress site, and then send out Safari Push notifications to every user each time you publish a new post from Wordpress.
Additionally, it lets you use a shortcode `[safari-push]` anywhere you like that will provide feedback to visitors on their notification status.

What are Safari Push Notifications?
--
Push notifications are a feature in Safari 7 and Mac OS 10.9 “Mavericks” that allow websites to send users notifications to their desktop, even when Safari is closed.
For much more information on this, see https://developer.apple.com/notifications/safari-push-notifications/

What else do I need in order to use this plugin?
--
You'll also need a working https server for communicating with Safari and Apple Push Notification Server (referred to as the "Web Service") .
For a PHP implementation of the Web Service, refer to our reference project at https://github.com/surrealroad/Safari-Push-Notifications.

Can I see it in action?
--
Sure, go to http://www.controlcommandescape.com/push/ (you'll probably want to open that URL in Safari 7)
