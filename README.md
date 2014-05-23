wp-safari-push
==

A Safari Push Plugin for Wordpress

What does this plugin do?
--
This plugin lets you prompt users to allow Safari Push notifications from your Wordpress site, and then send out Safari Push notifications to every user each time you publish a new post from Wordpress. There's also an option to send notifications directly from the plugin's settings page.
Additionally, it lets you use the shortcode `[safari-push]` anywhere you like that will provide feedback to visitors on their notification status, and the shortcode `[safari-push-count]` to show how many subscribers you have (there's also a dashboard widget to tell you this too).

This plugin is intentionally kept simple, feel free to fork the source and modify it to suit your needs.

The source is maintained on GitHub: https://github.com/surrealroad/wp-safari-push

What this plugin won't do
--
This plugin doesn't communicate with Apple's Push Notification Service (APNS) directly. You need a dedicated, secure server for that, which is beyond the scope of this plugin. See below for more details.

What are Safari Push Notifications?
--
Push notifications are a feature in Safari 7 and Mac OS 10.9 “Mavericks” that allow websites to send users notifications to their desktop, even when Safari is closed.
For much more information on this, see https://developer.apple.com/notifications/safari-push-notifications/

What else do I need in order to use this plugin?
--
You'll also need a seperate, working HTTPS server for communicating with Safari and Apple Push Notification Server (referred to as the "Web Service").
For a PHP implementation of the Web Service, refer to our reference project at https://github.com/surrealroad/Safari-Push-Notifications.

Can I see it in action?
--
Sure, go to http://www.controlcommandescape.com/push/ (you'll probably want to open that URL in Safari 7)


TODO:
- Comply with WordPress security guidelines

Additional credits
- Localisation and French-language version by Rémy Perona (http://remyperona.fr/)