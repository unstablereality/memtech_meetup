<?php

namespace Memtech\Console\Command;

use Carbon\Carbon;
use DMS\Service\Meetup\MeetupKeyAuthClient;
use Memtech\Traits\MeetupTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Writer;

class ExportEventsCommand extends Command
{
    use MeetupTrait;

    protected function configure()
    {
        $this->setName('memtech:exportevents')
            ->setDescription('Export Meetup Title, Date, and Organizers for a single month')
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Max events to return, default 50',
                50
            )
            ->addOption(
                'month',
                'm',
                InputOption::VALUE_REQUIRED,
                'Month for which to retrieve events, default is next month',
                'next month'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $m = $this->meetupConnect();
        $results = $input->getOption('limit');
        $month = $input->getOption('month');
        $group_url = 'memphis-technology-user-groups';
        $export_file = 'events_export.csv';
        $today = new Carbon('now', 'America/Chicago');

        $first_day = new Carbon("first day of $month", 'America/Chicago');
        $last_day = new Carbon("last day of $month", 'America/Chicago');

        $all_events = $m->getEvents([
            'group_urlname' => $group_url,
            'page' => $results, // Results to return
        ]);

        if (is_file($export_file))
        {
            unlink($export_file);
        }

        $fp = fopen($export_file, 'w');

        // Set up some column headers
        fputcsv($fp, ["Date","Event Title","Organizer"]);

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
