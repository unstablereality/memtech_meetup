<?php
namespace Memtech\Traits;

use DMS\Service\Meetup\MeetupKeyAuthClient;

trait MeetupTrait {
    /**
     *  Set up the Meetup API connection strings and create the connection.
     *
     *  @return mixed
     */
    public function meetupConnect()
    {
        $meetup_api_key = getenv('MEETUP_KEY');
        $connection = MeetupKeyAuthClient::factory(array('key' => $meetup_api_key));

        return $connection;
    }
}