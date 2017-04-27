<?php

namespace Memtech\Console\Command;

use Carbon\Carbon;
use DMS\Service\Meetup\MeetupKeyAuthClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Writer;

class ExportEventsCommand extends Command
{
    protected function configure()
    {
        $this->setName('memtech:exportevents')
            ->setDescription('Export Meetup Title, Date, and Organizers for a single month')
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                'Max events to return, default 50'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $m = $this->meetupConnect();
        $results = $input->getArgument('limit');
        $group_url = 'memphis-technology-user-groups';
        $export_file = 'events_export.csv';
        $today = new Carbon('now', 'America/Chicago');
        $first_day = new Carbon('first day of next month', 'America/Chicago');
        $last_day = new Carbon('last day of next month', 'America/Chicago');
        if (is_null($results))
        {
            $results = 50;
        }

        $all_events = $m->getEvents([
            'group_urlname' => $group_url,
            'page' => $results, // Results to return
        ]);

        if (is_file($export_file))
        {
            unlink($export_file);
        }

        $fp = fopen($export_file, 'w');

        foreach($all_events as $event)
        {
            $event_date = Carbon::createFromTimestamp($event['time'] / 1000);
            if ($event_date->between($first_day,$last_day))
            {
                $event_hosts = $m->getGroupEventsHosts([
                    'urlname' => $group_url,
                    'event_id' => $event['id']
                ]);

                foreach($event_hosts->getData() as $host)
                {
                    $fields = [
                        $event_date,
                        $event['name'],
                        $host['name']
                    ];
                    fputcsv($fp, $fields);
                }
            }
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