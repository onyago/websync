Owner:   Onyago Inc.
Version: 1.0
Name:    web sync
This script is free to use for noncommercial and commercial sites

What does this script help you?
If you manage your community events with onyago.com or you want synchronize events from you preferred places in the world to your website this script will help you.
Onyago provide two different sites. One is for development purpose, named "Sandbox", the other is the production Site. In this readme we use URLs to both worlds.

Files in this Package:
readme.txt____________this file
config.php____________the main configuration file
crontask.php__________script to sync the events to the SQLite database
functions.php_________functions from use
helpscript.php________a script to search and manage your feed subscriptions
index.php_____________example how to use the data on your page
web_taygete.sqlite3___a SQLite database with the needed tables
.htaccess_____________a preconfigured file to protect your installation
vendor/onyago_________directory with the onyago api class
  
Setup the system and make the first synchronization:
1) Setup an onyago account and get your access_token.

Onyago does not allow anonymous access. To use our API you need an account.
If you does not have an account now, create one over the API:
API Call: SignupUser
Production: https://www.onyago.com/api/1.0/?d=34
Sandbox:    https://sandbox.onyago.com/api/1.0/?d=34

If you already have an account, use the CheckUser API Call:
Production: https://www.onyago.com/api/1.0/?d=1
Sandbox:    https://sandbox.onyago.com/api/1.0/?d=1

In both cases you need from the result the sid and the access_token. These two values has to be placed in the config.php file.

2) Change the config.php file
Insert the "sid" and "access_token" in the config file. The mode is set to "html" for the first run and to see if all works fine.
Mode:
-> html  = render html output for test reason (this is for configuration usage)
-> image = send a 1x1 pixel image out (use this if you don't have a cron functionality)
-> cron  = send text output (standard usage) if you run the script with cronjob

Set the "environment" to "sandbox" or "production"!
Now run the /crontask.php Script
Output looks like:
	Timestamp: 1465821303
	Check for feeds to process.
	No feeds subscribed in table feeds.
	go to onyago.com and search for cid od fid and add this to your database.
	use our helpscript.php also included in this package.
	Delete old events

3.1 ) Logging
The script use a local "log.txt" file as default. If your webserver does not have rights to create a files, place an empty one to the script directory.

4) Search and subscribe feeds
Open the /helpscript.php with your browser. Select your country and search a city by name. If all works file and sid and access_token are right, you find your city. If not, take a look in the log.txt file.

5) Check Synchronization
Subscribe at least one city feed. In the next step go to /crontask.php and check if the synchronization works well. If the events are synchronized, you can change the logging to log only errors. Change the sys/debug to FALSE.

As next step you can select the working mechanism of the system.

cron mode:
For cron mode add the script to your cron job by user "crontab -e" or the cron help tool of your provider and add the script.
Example: 14 1,7,13,20 * * * /usr/bin/php /xxx/crontask.php
Run each 6 Hours on the 14th minute

image mode:
If you don't have a cron job possibility on your webserver use the image method. Edit a standard html page of your site and add an image link to the crontask.php:
<img src="crontask.php" style="width:1px;height:1px;">
So every time a visitor come to your page the script will send a 1x1 pixel gif image to the visitor's browser. If the last synchronization is older as 6 hours, the script run one sync.

6) Cleanup
If the sync process run well, change the name of the helpscript.php or delete this file or protect from access by other people.
By default the option sys/deleteoldevents is set to TRUE. This mean the sync script delete old events in the past. Actual it deletes events with start date time older as 24 hours.

7) Implement the events in your Site
No you can be creative. Take a look on the https://sandbox.onyago.com/api/1.0/?d=145
Get Events call, that you have an idea what data is stored in your SQLite database.
To browse the SQLite database the phpLiteAdmin (http://phpliteadmin.googlecode.com) is a simple and good tool.
We also put a little example in this package. You can use this index.php as a starting point.
Note: To format our date values to human readable format you can use the SQLite datetime (edatestart, 'unixepoch') formatting. Take a look to:
https://www.sqlite.org/lang_datefunc.html
If you have a stylish listing of the events implemented on your site, we are happy to bundle this in our package as an example for other users.
Note: Event images are stored on our server on a special way. Read the section
"Eimage and eimagearray:" on https://sandbox.onyago.com/api/1.0/?d=145

Also take a look on the help on https://www.onyago.com or the development server
https://developer.onyago.com.

If you have recommendations or questions, send us an email on onyago@onyago.com

Thanks for help our idea of onyago.com
Now it’s on you ;)
