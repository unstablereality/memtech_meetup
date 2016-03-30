<?php

  /*  #memtech Meetup.com/Twitter auto poster
      Author: Daniel Soskel
      Date: March 2016

      Usage: tweeter.php [keyword]

      Searches Meetup.com for the next #memtech event containing the supplied keyword and posts a tweet about it
      to @unstablereality's twitter. */

  require __DIR__ . '/vendor/autoload.php';
  
  // Set up the environment variables 
  $dotenv = new Dotenv\Dotenv(__DIR__);
  $dotenv->load();
  $dotenv->required(['TWITTER_KEY','TWITTER_SECRET','MEETUP_KEY']);

  // Set up the meetup API connection strings and create the connection
  //
  $meetup_api_key = getenv('MEETUP_KEY');
  $connection = new MeetupKeyAuthConnection($meetup_api_key);
  $m = new MeetupEvents($connection);
  $events = $m->getEvents( array('group_urlname' => 'memphis-technology-user-groups' ) );
 
  // Set up the Twitter API connection strings and create the connection
  $twitter_key = getenv('TWITTER_KEY');
  $twitter_secret = getenv('TWITTER_SECRET');
  \Codebird\Codebird::setConsumerKey($twitter_key, $twitter_secret);
  $cb = \Codebird\Codebird::getInstance();
  //$cb->setToken("14434380-MIODrcLOcZybH77RJsb2P3Ji1qjHHGzkn2YwFdnU5", "LiD10if5InYPW4UlxOi6XqEzZylGGYlfkFDhfDdyAE8dv");

  // Get the next event matching the supplied keyword string
  $args=implode(" ", array_slice($argv, 1));
  $event = getNextEvent($args, $events);
  $eventDate = date('m-d-Y', $event['time']/1000);
  //echo "The next event is " . $event['name'] . " on " . $eventDate . " at " .  $event['venue']['name']  . ", " . $event['venue']['address_1'] . PHP_EOL;
  $nextEvent="Come join us at 11:30 for #memtech lunch at " . $event['venue']['name'] . ", " . $event['venue']['address_1'] . ". " . $event['event_url'] . PHP_EOL;
  echo $nextEvent;

  // Tweet about it!
  //$params = array('status' => $nextEvent);
  //$reply = $cb->statuses_update($params);

  // find events based on keyword submitted as script argument
  function getNextEvent($keyword, $events) {
    $keyword = trim(strtolower($keyword));
    foreach ($events as $e) {
      if (strpos(strtolower($e['name']), $keyword) !== false && array_key_exists("venue", $e)) {
        $venue = $e['venue'];
        $date = date('m-d-Y', $e['time']/1000);
        //echo $e['name'] . " on " . $date . " at " .  $venue['name']  . ", " . $venue['address_1'] . PHP_EOL;
        //echo $e['event_url'] . PHP_EOL;
        $results[] = $e;
      }
    }
    return $results[0];
  }
  
?>
