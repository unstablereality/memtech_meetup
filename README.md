# memtech_tweeter

## Twitter Command

A simple script to automatically tweet about memtech events

Designed to be run from the crontab

Usage: `tweeter.php memtech:tweet  --dev`

When using `--dev` the command will NOT tweet results

Attach an image to the tweet:

`tweeter.php memtech:tweet http://url.to/image.jpg,http://url.to/image2.jpg`

Maximum 4 images. Must separate URLs with a comma, no leading spaces.

## Social Export Command

Designed to be run from the commandline to export a CSV of events from meetup.com

Usage: `php tweeter.php memtech:export` Will return 50 upcoming events.

Usage: `php tweeter.php memtech:export 10` Will return 10 upcoming events.

Results will be saved to `export.csv` in the same folder. `export.csv` **_will be overwritten._**
