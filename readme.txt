=== Safari Push ===
Tags: safari, push notifications, mac, os x, mavericks
Stable tag: 0.6.4
Tested up to: 3.8

A Safari Push Plugin for Wordpress

== Description ==

What does this plugin do?
--
This plugin lets you prompt users to allow Safari Push notifications from your Wordpress site, and then send out Safari Push notifications to every user each time you publish a new post from Wordpress.
Additionally, it lets you use a shortcode `[safari-push]` anywhere you like that will provide feedback to visitors on their notification status.
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
- Pushwoosh integration (pending response from PushWoosh support)
- Comply with WordPress security guidelines
- Better option for test notification

Additional credits
- Localisation and French-language version by Rémy Perona (http://remyperona.fr/)

== Installation ==

1. Install the plugin as you would any other WordPress plugin and enable it
2. Go to Settings > Safari Push
3. Fill in the details for your push service
4. (Optional) Visit your site in Safari 7.0+ and wait for the prompt to subscribe to notifications
5. (Optional) Add the shortcode `[safari-push]` somewhere on your site to view the status of push notifications
6. (Optional) Fill in the form at the bottom of the settings page to send a test psuh notification
7. (Optional) Edit the HTML displayed for the shortcode in the settings page

== Frequently Asked Questions ==

= Where can I get information on how to get the push service working? =

[Right here](https://developer.apple.com/notifications/safari-push-notifications/)

= How can I make my push service compatible with this plugin? =

See [our reference server](https://github.com/surrealroad/Safari-Push-Notifications) for example, but you need to have it receive a POST request that accepts "title", "body", "action", "urlargs" and "authentication" tags, and sends a notification to all registered devices.

= The notification status says denied, without ever prompting to allow it =

Your push package is probably incorrectly configured.
A common problem is to forget to include the "www." part of the domain in the "allowed domains" list. You need, for example, '"http://example.com", "http://www.example.com"'

= When are notifications sent? =

Notifications will be sent whenever a post is published for the first time.

= What about Pushwoosh support? =

I have been trying to get this to work with Pushwoosh. Unfortunately there are a couple of problems due to the API documentation not being particularly clear, and I have not received a response from anyone at Pushwoosh to in order address them.

= Can I customise what gets sent to which device? =

Not right now, maybe never. Depends if this is something that a lot of people would want.

== Changelog ==
= 0.6.4 =
* Workaround for Google Chrome for Mac identifying itself as Safari

= 0.6.3 =
* Fix for future versions of Safari

= 0.6.2 =
* Notification title and button text can now be changed in settings

= 0.6.1 =
* Fixed an issue that was generating a PHP warning
* Added some documentation and FAQ to the plugin page

= 0.6 =
* French localisation with thanks to Rémy Perona (http://remyperona.fr/)

= 0.5.3 =
* Remove subfolder for better compatibility

= 0.5.2 =
* Fixed folder structure for automatic downloads

= 0.5.1 =
* Added readme.

= 0.5 =
* First WordPress release