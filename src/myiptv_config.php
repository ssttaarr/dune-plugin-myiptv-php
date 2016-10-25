<?php
/*

MyIPTV plugin (http://blog.isayev.org.ua)

*/

class DemoConfig
{

    const PLUGIN_VERSION 	   = '2.2.2 (blog.isayev.org.ua)';

    const TV_FAVORITES_SUPPORTED   = true;

    const EPG_URL_FORMAT	= 'http://www.vsetv.com/schedule_channel_%d_day_%s_nsc_1.html';

    const ALL_CHANNEL_GROUP_CAPTION     = 'Все каналы';
    const ALL_CHANNEL_GROUP_ICON_PATH   = 'plugin_file://icons/all.png';
    const FAV_CHANNEL_GROUP_CAPTION     = 'Избранное';
    const FAV_CHANNEL_GROUP_ICON_PATH   = 'plugin_file://icons/favorites.png';

    const GROUP_ICON_PATH   = 'plugin_file://icons/g%d.png';
    
    const USE_M3U_FILE = true;

    const M3U_ICON_FILE_URL_FORMAT = 'http://blog-isayev-org-ua.googlecode.com/svn/trunk/channel_logos/%d.png';

    const M3U_ICON_FILE_URL_DEFAULT = 'http://blog-isayev-org-ua.googlecode.com/svn/trunk/channel_logos/online.png';

    const M3U_ID_FILE_URL = 'http://blog-isayev-org-ua.googlecode.com/svn/trunk/dune_plugin_myiptv/myiptv_channels_id';

    const DATA_PATH = 'http://blog-isayev-org-ua.googlecode.com/svn/trunk/dune_plugin_myiptv/%s';

    const CHANNEL_SORT_FUNC_CB = 'DemoConfig::sort_channels_cb';

    public static function get_group_name($gid) {

    	// Groups id 0 - 12 :

    	$group_names = array('Общие', 'Познавательные', 'Новости', 'Развлекательные', 'Детские', 'Музыкальные', 'Комедийные', 
		'Спортивные', 'Интернациональные', 'Фильмы/Сериалы', 'Эротические', 'Радио', 'Немецкие');
	//

	$gid = array_key_exists($gid, $group_names) ? $gid : 0;    

	return array($gid, $group_names[$gid]);
    }

    ///////////////////////////////////////////////////////////////////////

    public static function sort_channels_cb($a, $b)
    {
        // Sort by channel numbers.
        //return strnatcasecmp($a->get_number(), $b->get_number());

        // Other options:
        return strnatcasecmp($a->get_title(), $b->get_title());
    }

    ///////////////////////////////////////////////////////////////////////
    // Folder views.

    public static function GET_TV_GROUP_LIST_FOLDER_VIEWS()
    {
        return array(
            array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 2,
                    ViewParams::num_rows => 10,
                    ViewParams::paint_details => false,
                    ViewParams::zoom_detailed_icon => false,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
    		    ViewItemParams::item_paint_icon => true,
    		    ViewItemParams::item_layout => HALIGN_LEFT,
    		    ViewItemParams::icon_valign => VALIGN_CENTER,
    		    ViewItemParams::icon_dx => 10,
    		    ViewItemParams::icon_dy => -5,
		    ViewItemParams::icon_width => 84,
		    ViewItemParams::icon_height => 48,
    		    ViewItemParams::item_caption_font_size => FONT_SIZE_NORMAL,
    		    ViewItemParams::item_caption_width => 700,

                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),

        );
    }

    public static function GET_TV_CHANNEL_LIST_FOLDER_VIEWS()
    {
        return array(
            array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 2,
                    ViewParams::num_rows => 10,
                    ViewParams::paint_details => false,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
    		    ViewItemParams::item_paint_icon => true,
    		    ViewItemParams::item_layout => HALIGN_LEFT,
    		    ViewItemParams::icon_valign => VALIGN_CENTER,
    		    ViewItemParams::icon_dx => 10,
    		    ViewItemParams::icon_dy => -5,
		    ViewItemParams::icon_width => 75,
		    ViewItemParams::icon_height => 55,
    		    ViewItemParams::item_caption_font_size => FONT_SIZE_NORMAL,
    		    ViewItemParams::item_caption_width => 700,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array 
		(
		    ViewItemParams::icon_path => 'plugin_file://icons/0.png'
		),
            ),

        );
    }
    
}

?>
