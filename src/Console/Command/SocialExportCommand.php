<?php

namespace Memtech\Console\Command;

use Carbon\Carbon;
use Memtech\Traits\MeetupTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Writer;

class SocialExportCommand extends Command
{
    use MeetupTrait;

    private $twitter_map = [
        'php' => 'MemphisPHP',
        'midsouth makers' => 'MidsouthMakers',
        'ruby' => 'MemphisRuby',
        'python' => 'MemphisPython',
        'game dev' => 'MemphisGameDev',
        'web workers' => 'MemphisWW',
        'agile' => 'MemphisAgile',
    ];

    protected function configure()
    {
        $this->setName('memtech:export')
            ->setDescription('Export Meetup Dates and Info')
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
        if (is_null($results)) {
            $results = 50;
        }

        $all_events = $m->getEvents([
            'group_urlname' => 'memphis-technology-user-groups',
            'page' => $results, // Results to return
        ]);

        if (is_file('export.csv')) {
            unlink('export.csv');
        }

        $fp = fopen('export.csv', 'w');

        foreach ($all_events as $event) {
            $event_date = Carbon::createFromTimestamp($event['time'] / 1000);
            $hours_before = 6;
            $name = strtolower($event['name']);

            if ((strpos($name, 'lunch') !== false) ||
                (strpos($name, 'burger') !== false)
            ) {
                $hours_before = 2;
            }

            if (strpos($name, 'breakfast') !== false) {
                $hours_before = 19;
            }

            if (strpos($name, 'coworking') !== false) {
                $hours_before = 18;
            }

            $event_name = $this->processEventName($event['name']);

            $fields = [
                $event_date->subHours($hours_before)->format('m/d/Y H:i:s'),
                $event_name,
                $event['event_url'],
            ];

            fputcsv($fp, $fields);
        }

        fclose($fp);
    }

    protected function processEventName($name)
    {
        // If the name doesn't have #memtech, make it so
        if (strpos($name, '#memtech') === false) {
            $name .= ' #memtech';
        }

        foreach ($this->twitter_map as $needle => $twitter_handle) {
            if (strpos(strtolower($name), $needle) !== false) {
                $name .= ' @' . $twitter_handle;
            }
        }

        return $name;
    }
}
