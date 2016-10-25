<?php
/*

MyIPTV plugin (http://blog.isayev.org.ua)

*/

///////////////////////////////////////////////////////////////////////////

require_once 'lib/default_dune_plugin.php';
require_once 'lib/utils.php';

require_once 'lib/tv/tv_group_list_screen.php';
require_once 'lib/tv/tv_favorites_screen.php';

require_once 'myiptv_config.php';
require_once 'myiptv_m3u_tv.php';
require_once 'myiptv_setup_screen.php';
require_once 'myiptv_tv_channel_list_screen.php';

///////////////////////////////////////////////////////////////////////////

class DemoPlugin extends DefaultDunePlugin
{
    public function __construct()
    {
        $this->tv = new DemoM3uTv();

        $this->add_screen(new DemoTvChannelListScreen($this->tv,
                DemoConfig::GET_TV_CHANNEL_LIST_FOLDER_VIEWS()));
        $this->add_screen(new TvFavoritesScreen($this->tv,
                DemoConfig::GET_TV_CHANNEL_LIST_FOLDER_VIEWS()));
        $this->add_screen(new TvGroupListScreen($this->tv,
                DemoConfig::GET_TV_GROUP_LIST_FOLDER_VIEWS()));
        $this->add_screen(new DemoSetupScreen());
    }

}

///////////////////////////////////////////////////////////////////////////
?>
