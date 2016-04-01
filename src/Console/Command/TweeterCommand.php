<?php

namespace Memtech\Console\Command;

use MeetupEvents;
use Codebird\Codebird;
use MeetupKeyAuthConnection;
use Mremi\UrlShortener\Model\Link;
use Mremi\UrlShortener\Provider\Google\GoogleProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TweeterCommand extends Command
{
    protected function configure()
    {
        $this->setName('memtech:tweet')
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $m = $this->meetupConnect();
        $all_events = $m->getEvents([
            'group_urlname' => 'memphis-technology-user-groups'
        ]);

        $events = $this->getNextEvent($input->getArgument('keywords'), $all_events);

        // Make sure we found $events
        if ($events === false)
        {
            $output->writeln('No event matched keyword: ' . $input->getArgument('keywords'));
            exit();
        }

        $event = $this->parseEvent($events);
        $tweet = $this->generateTweet($event);

        $output->writeln($tweet);

        if (!$input->getOption('dev')) {
            // Tweet about it!
            $this->tweetEvent($tweet);
        }
    }

    /**
    * Send out the message via twitter
    * @param $tweetMsg
    */
    protected function tweetEvent($tweetMsg)
    {
        $cb = $this->twitterConnect();
        $params = ['status' => $tweetMsg];
        $reply = $cb->statuses_update($params);

        return $reply; // just in case we care to check
    }

    /** 
    *  Set up the Meetup API connection strings and create the connection.
    *
    *  @return mixed
    */
    protected function meetupConnect()
    {
        $meetup_api_key = getenv('MEETUP_KEY');
        $connection = new MeetupKeyAuthConnection($meetup_api_key);
        $m = new MeetupEvents($connection);

        return $m;
    }

    /**
    *  Set up the Twitter API connection strings and create the connection.
    *
    *  @return mixed
    */
    protected function twitterConnect()
    {
        Codebird::setConsumerKey(getenv('TWITTER_KEY'), getenv('TWITTER_SECRET'));
        $cb = Codebird::getInstance();
        $cb->setToken(getenv('TWITTER_TOKEN'), getenv('TWITTER_TOKEN_SECRET'));
        
        return $cb;
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
                $results[] = $e;
            }
        }

        if (count($results) > 0)
        {
            // Found results for the keyword
            return $results[0];
        }

        return false; // if we didn't find results we can check for false
    }

    /**
     * Parse event and return array of event details
     * @param $events
     * @return mixed
     */
    protected function parseEvent($events)
    {
        $event['venue_name'] = $events['venue']['name'];
        $event['date'] = date('m-d-Y', $events['time'] / 1000);
        $event['event_url'] = $events['event_url'];
        $event['address'] = $events['venue']['address_1'];

        return $event;
    }

    /**
     * Turn event details into tweet-able string
     * @param $event
     * @return mixed
     */
    protected function generateTweet($event)
    {
        $url = $this->shortenUrl($event['event_url']);
        $tweets = [
            'Come join us at 11:30 for #memtech lunch at ' . $event['venue_name'] . '. ' . $url . PHP_EOL,
            'I\'ll be enjoying ' . $event['venue_name'] . ' at 11:30 today. Join us for lunch! #memtech ' . $url . PHP_EOL,
            $event['venue_name'] . ' is the place to be at 11:30 today for #memtech lunch! ' . $url . PHP_EOL,
            'Need plans for lunch? Head out to ' . $event['venue_name'] . ' for #memetech lunch at 11:30. ' . $url . PHP_EOL,
            'Good food, good friends, good conversation. #memtech lunch. 11:30 today at ' . $event['venue_name'] . '. ' . $url . PHP_EOL,
        ];

        foreach ($tweets as $key => $tweet)
        {
            if (strlen($tweet) > 140)
            {
                unset($tweets[$key]);
            }
        }

        if (count($tweets) > 0)
        {
            shuffle($tweets);

            return $tweets[0];
        }

        exit('All tweets were too long to tweet');
    }

    /**
     * Shorten a URL using google's URL shortener
     * @param $url
     * @return string
     */
    protected function shortenUrl($url)
    {
        $link = new Link;
        $link->setLongUrl('http://mremi/url-shortener');

        $options =  [
            'connect_timeout' => 1,
            'timeout' => 1,
        ];

        $googleProvider = new GoogleProvider(getenv('GOOGLE_SHORTEN_KEY'), $options);
        $googleProvider->shorten($link);
        $shortUrl = $link->getShortUrl();

        return $shortUrl;
    }
}
