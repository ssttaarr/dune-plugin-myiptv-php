<?php
/*

MyIPTV plugin (http://blog.isayev.org.ua)

*/

///////////////////////////////////////////////////////////////////////////

require_once 'lib/hashed_array.php';
require_once 'lib/tv/abstract_tv.php';
require_once 'lib/tv/default_epg_item.php';

require_once 'myiptv_channel.php';

///////////////////////////////////////////////////////////////////////////

class DemoM3uTv extends AbstractTv
{
    public function __construct()
    {
        parent::__construct(
            AbstractTv::MODE_CHANNELS_N_TO_M,
            DemoConfig::TV_FAVORITES_SUPPORTED,
            false);
    }

    public function get_fav_icon_url()
    {
        return DemoConfig::FAV_CHANNEL_GROUP_ICON_PATH;
    }

    public function get_tv_stream_url($playback_uid, &$plugin_cookies)
    {
	$playback_id = substr($playback_uid, 31);

	$playback_url = $this->global_channels[$playback_id]['urls'][$this->global_channels[$playback_id]['source']];

	$use_proxy = isset($plugin_cookies->use_proxy) ?
            $plugin_cookies->use_proxy : 'no';

	if ($use_proxy == 'yes') 
	    $playback_url = str_replace('udp://@', 'http://ts://'.$plugin_cookies->proxy_ip.':'.$plugin_cookies->proxy_port.'/udp/', $playback_url);
	    
	if (substr(strtolower($playback_url), 0, 12) == 'http://ts://')
	    $url = $playback_url;
	else if (substr(strtolower($playback_url), 0, 11) == 'http://ts//')
	    $url = $playback_url;
	else if (substr(strtolower($playback_url), 0, 13) == 'http://mp4://')
	     $url = $playback_url;
	else if (substr(strtolower($playback_url), 0, 12) == 'http://mp4//')
	     $url = $playback_url;
	else if (substr(strtolower($playback_url), 0, 7) == 'http://')
	     $url = str_replace('http://', 'http://ts://', $playback_url);
	else if (substr(strtolower($playback_url), 0, 4) == 'rtmp') {
	    $f = array('rtmp://$OPT:rtmp-raw=', 'rtmp://', 'rtmpt://','rtmpe://','rtmpte://', ' --');
	    $t = array('', "http://ts://127.0.0.1/cgi-bin/plugins/myiptv/rtmp?rtmp://", "http://ts://127.0.0.1/cgi-bin/plugins/myiptv/rtmp?rtmpt://","http://ts://127.0.0.1/cgi-bin/plugins/myiptv/rtmp?rtmpe://","http://ts://127.0.0.1/cgi-bin/plugins/myiptv/rtmp?rtmpte://", ' ');
	    $url = str_ireplace($f, $t, $playback_url)." live=1";
	}
	else 
	     $url = $playback_url;
	
	hd_print($url);
	return $url;
    }


    public function parse_id($id) {
	if (preg_match("/vsetv_(\d+)/", $id, $matches)) { 
		if ($matches[1] < 1000)
		   return $matches[1];
	}
    		
	else if (preg_match("/tvs_(.*)/", $id, $matches)) {
		$channel_id = array_search(strtoupper($matches[1]), $this->id_to_link);
		if ($chennel_id === true)
			return $channel_id;		
	}		
	return 0;
    }
 
    public function parse_playlist($file) 
    {
	$path_parts = pathinfo($file);		
	if (strtolower($path_parts['extension']) == 'm3u')
		return self::parse_m3u($file);
	else if (strtolower($path_parts['extension']) == 'xspf')
		return self::parse_xspf($file);
	else {
		hd_print("Unknown extension: $file");
		return array();
	}
    }
	

    public function parse_m3u($m3u_file)
    {
	$playlist = array();

	if (!($m3u_lines = file($m3u_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))) {
		hd_print("Error opening $m3u_file");
		return $playlist;
	}
	
	$i = 0;

	foreach ($m3u_lines as $line) {
		$line = trim($line);	
	 	if (preg_match('/#EXTM3U/', strtoupper($line), $matches))
			continue;
		
		if (preg_match('/^#EXTINF:[^,]*,(.+)$/', $line, $matches)) {
			$caption = trim($matches[1]);

			if (preg_match('/^#EXTINF:.*logo=([^ ^,]+)/', $line, $matches)) {
			    $logo = preg_replace('/\"/', '', $matches[1]);			
			}
			
			if (preg_match('/^#EXTINF:.* id=([^ ^,]+)/', $line, $matches)) {
			    $id = preg_replace('/\"/', '', $matches[1]);			
			}
			
			if (preg_match('/^#EXTINF:.*epg_shift=([^ ^,]+)/', $line, $matches)) {
			    $epg_shift = preg_replace('/\"/', '', $matches[1]);			
			}
			if (preg_match('/^#EXTINF:.*group_id=([^ ^,]+)/', $line, $matches)) {
			    $group_id = preg_replace('/\"/', '', $matches[1]);			
			}
			continue;
		}
		$url = $line;
		$playlist[$i]['url'] = $url;
		$playlist[$i]['caption'] = ($caption == '') ? $url : $caption;		
		$playlist[$i]['logo'] = $logo;
		$playlist[$i]['group'] = $group_id;		
		$playlist[$i]['id'] = self::parse_id(trim($id));
		$playlist[$i]['epg_shift'] = ($epg_shift == '') ? 0 : floatval($epg_shift);
		$playlist[$i]['epg_shift'] = (($playlist[$i]['epg_shift'] < -24) || ($playlist[$i]['epg_shift'] > -24)) ? 0 : $playlist[$i]['epg_shift'] + 24;
		$caption = '';
		$logo = '';
		$id = 0;
		$group_id = 0;
		$i++;
		$epg_shift = 0;
		$group_title = '';
	}


    	return $playlist;

     }

    public function parse_xspf($xspf_file)
    {
    	libxml_use_internal_errors(true);

	$i = 0;	$playlist = array();
	$caption = ''; $logo = ''; $id = 0; $epg_shift = 0; $group_title = '';

	if (!($test = simplexml_load_string(file_get_contents($xspf_file)))) {
		hd_print("Error parsing $xspf_file");
		return $playlist;
	}

	else {
	
	foreach ($test->trackList->children() as $track) {
	    $caption = ''; $logo = ''; 	$id = ''; $epg_shift = 0; $group_title = ''; $group_id = 0;
	    $caption = trim($track->title);
	    $url = trim($track->location);
	    $id_t = $track->extension->xpath('myiptv:id');
	    $id = isset($id_t[0]) ? self::parse_id(trim($id_t[0])) : ''; 
	    $epg_shift_t = $track->extension->xpath('myiptv:epg_shift');
	    $epg_shift = isset($epg_shift_t[0]) ? floatval(trim($epg_shift_t[0])) : 0; 
	    $logo_t = $track->extension->xpath('myiptv:logo');
	    $group_id_t = $track->extension->xpath('myiptv:group_id');
	    $group_id = isset($group_id_t[0]) ? trim($group_id_t[0]) : 0; 
	    $logo = isset($logo_t[0]) ? trim($logo_t[0]) : ''; 
	    $playlist[$i]['url'] = $url;
	    $playlist[$i]['caption'] = ($caption == '') ? $url : $caption;		
	    $playlist[$i]['logo'] = $logo;		
	    $playlist[$i]['id'] = $id;
	    $playlist[$i]['group'] = $group_id;
	    $playlist[$i]['epg_shift'] = ($epg_shift == '') ? 0 : floatval($epg_shift);
	    $playlist[$i]['epg_shift'] = (($playlist[$i]['epg_shift'] < -24) || ($playlist[$i]['epg_shift'] > -24)) ? 0 : $playlist[$i]['epg_shift'] + 24;
	    
	    $i++;
	}
	return $playlist;
	}
    }

    public function xorEncrypt($InputString)
    {
      $KeyString = 'bynthtcyj?';
      $KeyStringLength = mb_strlen( $KeyString );
      $InputStringLength = mb_strlen( $InputString );
      for ( $i = 0; $i < $InputStringLength; $i++ )
      {
         $rPos = $i % $KeyStringLength;
         $r = ord( $InputString[$i] ) ^ ord( $KeyString[$rPos] );
         $InputString[$i] = chr($r);
      }
       return $InputString;
    }


    public function decrypt($input) {
	return unserialize(self::xorEncrypt($input));
    }

    function make_id_key($caption) {
	return md5(strtolower(str_replace(array("\r", "\n", "\"", " "), '', $caption)));
    }


    function get_group_by_channel($groups, $id) {
	$group = array_key_exists($id, $groups) ? $groups[$id] : 0;
	return $group;
    }


    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////

    public function folder_entered(MediaURL $media_url, &$plugin_cookies) {
	if ($media_url->get_raw_string() == 'tv_group_list')
                $this->load_channels($plugin_cookies);            
    }

    private static function get_icon_path($channel_id)
    { 
	$channel_id = ($channel_id < 1200) ? $channel_id : 0;
	return sprintf(DemoConfig::M3U_ICON_FILE_URL_FORMAT, $channel_id); 
    }

    private static function get_future_epg_days($channel_id)
    { 
	$days = ($channel_id == 0) ? 0 : 1;
	return $days;
    }

    protected function load_channels(&$plugin_cookies)
    {
        $this->channels = new HashedArray();
        $this->groups = new HashedArray();
	$this->global_channels = array();
	$this->id_to_link = array();

        if ($this->is_favorites_supported())
        {
            $this->groups->put(
                new FavoritesGroup(
                    $this,
                    '__favorites',
                    DemoConfig::FAV_CHANNEL_GROUP_CAPTION,
                    DemoConfig::FAV_CHANNEL_GROUP_ICON_PATH));
        }

        $all_channels_group = 
            new AllChannelsGroup(
                $this,
                DemoConfig::ALL_CHANNEL_GROUP_CAPTION,
                DemoConfig::ALL_CHANNEL_GROUP_ICON_PATH);

        $this->groups->put($all_channels_group);

	$out_lines = "";
	$channels_id_parsed = array();
	$m3u_lines = array();
	
	$load_ex = isset($plugin_cookies->load_ex) ?
            $plugin_cookies->load_ex : 'yes';

	if ($load_ex == 'yes') {
		if ($this->global_channels = self::decrypt(file_get_contents('compress.zlib://'.sprintf(DemoConfig::DATA_PATH, '01'))))
			hd_print ('Loading 01...');
		else {
			/// $this->global_channels = self::decrypt(file_get_contents('compress.zlib://'.dirname(__FILE__).'/data/01'));
			hd_print ('Can not load 01...');
		}
	}
	
	if ($channels_id_parsed =  self::decrypt(file_get_contents('compress.zlib://'.sprintf(DemoConfig::DATA_PATH, '02'))))
		hd_print ('Loading 02...');
	else {
		$channels_id_parsed =  self::decrypt(file_get_contents('compress.zlib://'.dirname(__FILE__).'/data/02'));
		hd_print ('Using local copy 02...');
	}


	if ($this->id_to_link =  self::decrypt(file_get_contents('compress.zlib://'.sprintf(DemoConfig::DATA_PATH, '04'))))
		hd_print ('Loading 04...');
	else {
		$this->id_to_link =  self::decrypt(file_get_contents('compress.zlib://'.dirname(__FILE__).'/data/04'));
		hd_print ('Using local copy 04...');
	}


        $m3u = isset($plugin_cookies->m3u) ? $plugin_cookies->m3u : '';

	$m3u_type = isset($plugin_cookies->m3u_type) ?
            $plugin_cookies->m3u_type : '1';
	
	if (($m3u_type == 3) && ($m3u == ''))
	    $m3u_type = 1;

	$m3u_dir = isset($plugin_cookies->m3u_dir) ?
            $plugin_cookies->m3u_dir : '/D';

	$m3u_files = array();
	

	if ($m3u_type == 1) {
		foreach (glob('{'.dirname(__FILE__).'/playlists/*.[xX][sS][pP][fF],'.dirname(__FILE__).'/playlists/*.[mM]3[uU]}', GLOB_BRACE) as $file) 
    			if (is_file($file)) $m3u_files[]=$file;
	}
	else if ($m3u_type == 2) {
		foreach (glob('{'.$m3u_dir.'/*.[mM]3[uU],'.$m3u_dir.'/*.[xX][sS][pP][fF]}', GLOB_BRACE) as $file) 
    			if (is_file($file)) $m3u_files[]=$file;
	}
	else 
		$m3u_files[]=$m3u;

	 
	if ($group_defs =  self::decrypt(file_get_contents('compress.zlib://'.sprintf(DemoConfig::DATA_PATH, '03'))))
		hd_print ('Loading 03...');
	else {
		$group_defs =  self::decrypt(file_get_contents('compress.zlib://'.dirname(__FILE__).'/data/03'));
		hd_print ('Using local copy 03...');
	}
        
	foreach ($m3u_files as $m3u_file) {
		$path_parts = pathinfo($m3u_file);		
		$source = $path_parts['filename'];        
 
		if (preg_match('/^[a-zA-Z0-9-_]+$/', $source)) 
		  foreach (self::parse_playlist($m3u_file) as $playlist_line) {
			$caption = $playlist_line['caption']; 
            		$media_url = $playlist_line['url'];
			$group_id = $playlist_line['group']; 
			$id = ($playlist_line['id'] == '') ? 0 : $playlist_line['id'];
			$id_key = self::make_id_key($caption);
			$this->global_channels[$id_key]['caption'] = 	$caption;	
			
			if ($id == 0)
				$this->global_channels[$id_key]['id'] = (array_key_exists($id_key,$channels_id_parsed)) ? $channels_id_parsed[$id_key] : $id;
			else
				$this->global_channels[$id_key]['id'] = $id;
						
			if ($group_id > 0) {
				list ($gid, $gname) = DemoConfig::get_group_name($group_id);
				$this->global_channels[$id_key]['group'] = $gid;
			}
			else
				$this->global_channels[$id_key]['group'] = self::get_group_by_channel($group_defs, $this->global_channels[$id_key]['id']);
			


			$this->global_channels[$id_key]['source'] = $source;
			$this->global_channels[$id_key]['urls'][$source] = $media_url;
			$this->global_channels[$id_key]['logo'] = $playlist_line['logo']; 
				
		 }
	}


	reset($this->global_channels);

			
	foreach ($this->global_channels as $cid => $global_channel) {

		$source = ((isset($plugin_cookies->{'src_'.$cid})) && (array_key_exists($plugin_cookies->{'src_'.$cid}, $global_channel['urls']))) ? $plugin_cookies->{'src_'.$cid} : $global_channel['source'];

		$this->global_channels[$cid]['source'] = $source;
		$global_channel['logo'] = (array_key_exists('logo', $global_channel)) ? $global_channel['logo'] : '';
		$logo = ($global_channel['logo'] != '') ? $global_channel['logo'] : self::get_icon_path($global_channel['id']);
		
		if ($global_channel['group'] == 0) 
			$global_channel['group'] = (array_key_exists($global_channel['id'], $group_defs)) ? $group_defs[$global_channel['id']] : 0;

		list ($gid, $gname) = DemoConfig::get_group_name($global_channel['group']);

		$channel =
                new DemoChannel(
                    $cid,
                    $global_channel['caption'],
                    $logo,
                    "http://ts://blog.isayev.org.ua/$cid",
                    -1,
                    0,
                    self::get_future_epg_days($global_channel['id']));

		if (!($this->groups->has($gid))) {
			$this->groups->put(
	                new DefaultGroup(
	                    strval($gid),
	                    strval($gname),
	                    strval(sprintf(DemoConfig::GROUP_ICON_PATH, $gid))));
		}

	    $this->channels->put($channel);
   	    $group = $this->groups->get($gid);
	    $channel->add_group($group);
            $group->add_channel($channel);

	}
	$this->channels->usort(DemoConfig::CHANNEL_SORT_FUNC_CB);
    }

    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    public function get_day_epg_iterator($channel_id, $day_start_ts, &$plugin_cookies)
    { 
    
    $channel_id = $this->global_channels[$channel_id]['id'];

    if ($channel_id == 0) 
	return array();
    
    $epg_shift = isset($plugin_cookies->epg_shift) ? $plugin_cookies->epg_shift : '0';
    
    $epg_date = date("Y-m-d", $day_start_ts);
    $epg = array();

    if (file_exists("/tmp/channel_".$channel_id."_".$day_start_ts)) {
	$doc = file_get_contents("/tmp/channel_".$channel_id."_".$day_start_ts);
	$epg = unserialize($doc);
    }
    else {
	$epg = array();	
	if ($channel_id < 1000) { 	
		try {
          	  $doc = HD::http_get_document(sprintf(DemoConfig::EPG_URL_FORMAT, $channel_id, $epg_date));
		}
		catch (Exception $e) {
		    hd_print("Can't fetch EPG ID:$id DATE:$epg_date (vsetv)");
	 	   return array();
		}
	
       		$doc = iconv('WINDOWS-1251', 'UTF-8', $doc);

		$patterns = array("/<div class=\"desc\">/", "/<div class=\"onair\">/", 
			"/<div class=\"pasttime\">/", "/<div class=\"time\">/", "/<br><br>/", "/<br>/", "/&nbsp;/");
        	$replace = array("|", "\n", "\n", "\n", ". ", ". ", "");

		$doc = strip_tags(preg_replace($patterns, $replace, $doc));

        	preg_match_all("/([0-2][0-9]:[0-5][0-9])([^\n]+)\n/", $doc, $matches);

		$last_time = 0;
	
        	foreach ($matches[1] as $key => $time) {
	 	    $str = explode("|", $matches[2][$key], 2);
		    $name = $str[0];
		    $desc = array_key_exists(1, $str) ? $str[1] : "";
		    $u_time = strtotime("$epg_date $time EEST");
		    $last_time = ($u_time < $last_time) ? $u_time + 86400  : $u_time ;
		    $epg[$last_time]["name"] = $name;
		    $epg[$last_time]["desc"] = $desc;
	  	}
		
	 }
	 else {
		$link = $this->id_to_link[$channel_id];
		try {
		  $opt[CURLOPT_USERAGENT] = 'Apache-HttpClient/UNAVAILABLE (java 1.4)';
          	  $doc = HD::http_get_document('http://tvsapi.cellmp.de/getProgram_1_3.php?date='.$epg_date.'&channel=["'.$link.'"]&time=05:00', $opt);
		}
		catch (Exception $e) {
		    hd_print("Can't fetch EPG ID:$id DATE:$epg_date (tvspielfilm)");
	 	   return array();
		}
		
		if (substr($doc, 0,3) == pack("CCC",0xef,0xbb,0xbf))
		    $doc=substr($doc, 3);

		if ($matches = json_decode($doc, true)) 
			foreach ($matches as $item) {
				$time = strtotime($item['anfangsdatum']." CEST");
				$epg[$time]['name'] = 	$item['titel'];
				$epg[$time]['desc'] = "{$item['genre']} {$item['jahr']} {$item['land']}";
			}
		
	}
        file_put_contents("/tmp/channel_".$channel_id."_".$day_start_ts, serialize($epg));
	}



	$epg_result = array();

	ksort($epg, SORT_NUMERIC);
	reset($epg);

	foreach ($epg as $time => $value) {
	    $epg_result[] =
                new DefaultEpgItem(
                    strval($value['name']),
                    strval($value['desc']),
                    intval($time + $epg_shift),
                    intval(-1));
		
	}
	
	return
            new EpgIterator(
                $epg_result,
                $day_start_ts,
                $day_start_ts + 100400);
    }
}

///////////////////////////////////////////////////////////////////////////
?>
