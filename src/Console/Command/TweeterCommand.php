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
        // Set up the meetup API connection strings and create the connection
        //
        $meetup_api_key = getenv('MEETUP_KEY');
        $connection = new MeetupKeyAuthConnection($meetup_api_key);
        $m = new MeetupEvents($connection);
        $events = $m->getEvents(['group_urlname' => 'memphis-technology-user-groups']);

        // Set up the Twitter API connection strings and create the connection
        $twitter_key = getenv('TWITTER_KEY');
        $twitter_secret = getenv('TWITTER_SECRET');
        Codebird::setConsumerKey($twitter_key, $twitter_secret);
        $cb = Codebird::getInstance();
        //$cb->setToken("14434380-MIODrcLOcZybH77RJsb2P3Ji1qjHHGzkn2YwFdnU5", "LiD10if5InYPW4UlxOi6XqEzZylGGYlfkFDhfDdyAE8dv");

        // Get the next event matching the supplied keyword string
        $event = $this->getNextEvent($input->getArgument('keywords'), $events);
        $eventDate = date('m-d-Y', $event['time']/1000);
        //echo "The next event is " . $event['name'] . " on " . $eventDate . " at " .  $event['venue']['name']  . ", " . $event['venue']['address_1'] . PHP_EOL;
        $nextEvent="Come join us at 11:30 for #memtech lunch at " . $event['venue']['name'] . ", " . $event['venue']['address_1'] . ". " . $event['event_url'] . PHP_EOL;
        $output->writeln($nextEvent);

        if (!$input->getOption('dev'))
        {
            // Tweet about it!
            $params = ['status' => $nextEvent];
            $reply = $cb->statuses_update($params);
        }

    }

    /**
     * find events based on keyword submitted as script argument
     * @param $keyword
     * @param $events
     * @return mixed
     */
    protected function getNextEvent($keyword, $events) {
        $keyword = trim(strtolower($keyword));
        $results = [];
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
}