<?php
/*

MyIPTV plugin (http://blog.isayev.org.ua)

*/

///////////////////////////////////////////////////////////////////////////

require_once 'lib/tv/default_channel.php';

///////////////////////////////////////////////////////////////////////////

class DemoChannel extends DefaultChannel
{
    private $number;
    private $past_epg_days;
    private $future_epg_days;

    ///////////////////////////////////////////////////////////////////////

    public function __construct(
        $id, $title, $icon_url, $streaming_url, $number, $past_epg_days, $future_epg_days)
    {
        parent::__construct($id, $title, $icon_url, $streaming_url);

        $this->number = $number;
        $this->past_epg_days = $past_epg_days;
        $this->future_epg_days = $future_epg_days;
	$this->playback_url_is_stream_url = false;
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_number()
    { return $this->number; }

    public function get_past_epg_days()
    { return $this->past_epg_days; }

    public function get_future_epg_days()
    { return $this->future_epg_days; }
}

///////////////////////////////////////////////////////////////////////////
?>
