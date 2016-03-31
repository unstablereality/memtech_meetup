<?php

namespace Memtech\Console\Command;

use MeetupEvents;
use Codebird\Codebird;
use MeetupKeyAuthConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TweeterCommand extends Command
{
    protected function configure()
    {
        $this
      ->setName('memtech:tweet')
      ->setDescription('Tweet about Lunch events')
      ->addArgument(
        'keywords',
        InputArgument::REQUIRED,
        'Keywords to search for?'
      )
      ->addOption(
        'dev',
        true,
        InputOption::VALUE_NONE,
        'If set, the task will not attempt to tweet'
      )
    ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $events = $this->meetup_connect();
        $cb = $this->twitter_connect();
        $tweetMsg = $this->getNextEvent($input->getArgument('keywords'), $events);

        $output->writeln($tweetMsg);

        if (!$input->getOption('dev')) {
            // Tweet about it!
      $params = ['status' => $tweetMsg];
            $reply = $cb->statuses_update($params);
        }
    }

  /** 
   *  Set up the Meetup API connection strings and create the connection.
   *
   *  @return mixed
   */
  protected function meetup_connect()
  {
      $meetup_api_key = getenv('MEETUP_KEY');
      $connection = new MeetupKeyAuthConnection($meetup_api_key);
      $m = new MeetupEvents($connection);
      $events = $m->getEvents(['group_urlname' => 'memphis-technology-user-groups']);

      return $events;
  }

  /** 
   *  Set up the Twitter API connection strings and create the connection.
   *
   *  @return mixed
   */
  protected function twitter_connect()
  {
      $twitter_key = getenv('TWITTER_KEY');
      $twitter_secret = getenv('TWITTER_SECRET');
      Codebird::setConsumerKey($twitter_key, $twitter_secret);
      $cb = Codebird::getInstance();
  }

  /**
   * find events based on keyword submitted as script argument.
   *
   * @param $keyword
   * @param $events
   *
   * @return string
   */
  protected function getNextEvent($keyword, $events)
  {
      $keyword = trim(strtolower($keyword));
      $results = [];

    // find a match
    foreach ($events as $e) {
        if (strpos(strtolower($e['name']), $keyword) !== false && array_key_exists('venue', $e)) {
            $venue = $e['venue'];
            $date = date('m-d-Y', $e['time'] / 1000);
            $results[] = $e;
        }
    }

    // build the tweet
    $event = $results[0];
      $eventDate = date('m-d-Y', $event['time'] / 1000);
      $tweets = array('Come join us at 11:30 for #memtech lunch at '.$event['venue']['name'].', '.$event['venue']['address_1'].'. '.$event['event_url'].PHP_EOL,
      "I'll be enjoying ".$event['venue']['name'].' at 11:30 today. Come find me at '.$event['venue']['address_1'].'. #memtech #techlunch'.$event['event_url'].PHP_EOL, );
      $tweetMsg = $tweets[array_rand($tweets)];

      return $tweetMsg;
  }
}
