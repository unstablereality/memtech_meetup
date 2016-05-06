<?php

namespace Memtech\Console\Command;

use Carbon\Carbon;
use Codebird\Codebird;
use Mremi\UrlShortener\Model\Link;
use DMS\Service\Meetup\MeetupKeyAuthClient;
use Mremi\UrlShortener\Provider\Google\GoogleProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Writer;
use SplTempFileObject;

class SocialExportCommand extends Command
{
    protected function configure()
    {
        $this->setName('memtech:export')
            ->setDescription('Export Meetup Dates and Info');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $m = $this->meetupConnect();
        $all_events = $m->getEvents([
            'group_urlname' => 'memphis-technology-user-groups'
        ]);

        if (is_file('export.csv'))
        {
            unlink('export.csv');
        }
        
        $fp = fopen('export.csv', 'w');

        foreach($all_events as $event)
        {
            $event_date = Carbon::createFromTimestamp($event['time'] / 1000);
            $fields = [
                $event_date->subHours(6)->format('m/d/Y H:i:s'),
                $event['name'],
                $event['event_url'],
            ];

            fputcsv($fp, $fields);
        }

        fclose($fp);
    }

    /**
     *  Set up the Meetup API connection strings and create the connection.
     *
     *  @return mixed
     */
    protected function meetupConnect()
    {
        $meetup_api_key = getenv('MEETUP_KEY');
        $connection = MeetupKeyAuthClient::factory(array('key' => $meetup_api_key));

        return $connection;
    }
}