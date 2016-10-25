<?php
/*

MyIPTV plugin (http://blog.isayev.org.ua)

*/

///////////////////////////////////////////////////////////////////////////

require_once 'lib/abstract_controls_screen.php';

///////////////////////////////////////////////////////////////////////////

class DemoSetupScreen extends AbstractControlsScreen
{
    const ID = 'setup';

    ///////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        parent::__construct(self::ID);
	
    }

    public function do_get_control_defs(&$plugin_cookies)
    {
        $defs = array();

        $show_tv = isset($plugin_cookies->show_tv) ?
            $plugin_cookies->show_tv : 'yes';

        $m3u = isset($plugin_cookies->m3u) ?
            $plugin_cookies->m3u : '';

        $use_proxy = isset($plugin_cookies->use_proxy) ?
            $plugin_cookies->use_proxy : 'no';

        $proxy_ip = isset($plugin_cookies->proxy_ip) ?
            $plugin_cookies->proxy_ip : '192.168.1.1';

	$proxy_port = isset($plugin_cookies->proxy_port) ?
            $plugin_cookies->proxy_port : '9999';

	$m3u_type = isset($plugin_cookies->m3u_type) ?
            $plugin_cookies->m3u_type : '1';

	$m3u_dir = isset($plugin_cookies->m3u_dir) ?
            $plugin_cookies->m3u_dir : '/D';

	$epg_shift = isset($plugin_cookies->epg_shift) ?
            $plugin_cookies->epg_shift : '0';

	$load_ex = isset($plugin_cookies->load_ex) ?
            $plugin_cookies->load_ex : 'yes';

        $show_ops = array();
        $show_ops['yes'] = 'Да';
        $show_ops['no'] = 'Нет';

  	$m3u_ops = array();
        $m3u_ops['1'] = 'Плейлисты из плагина';
        $m3u_ops['2'] = 'Плейлисты из каталога первого HDD/USB-диска';
        $m3u_ops['3'] = 'Плейлист по ссылке';


	for ($i = -12; $i<13; $i++)
		$shift_ops[$i*3600] = $i; 

	$this->add_label($defs,
    	    'Версия myIPTV plugin:', DemoConfig::PLUGIN_VERSION);

        $this->add_combobox($defs,
            'show_tv', 'myIPTV в разделе ТВ:',
            $show_tv, $show_ops, 0, true);

	$this->add_combobox($defs,
            'load_ex', 'Загружать публичные каналы:',
            $load_ex, $show_ops, 0, true);

	$this->add_combobox($defs,
            'epg_shift', 'Коррекция программы (час):',
            $epg_shift, $shift_ops, 0, true);

    	$this->add_combobox($defs,
            'm3u_type', 'Загружать:',
            $m3u_type, $m3u_ops, 0, true);
	
	if ($m3u_type == 2) {

	    $m3u_dir_ops = array();
	    $m3u_dir_ops['/D'] = '/';

	    foreach (glob('/D/*') as $file) 
    		if (is_dir($file)) $m3u_dir_ops[$file] = substr($file,3,strlen($file));


	    $this->add_combobox($defs,
            	'm3u_dir', 'Каталог:',
            	$m3u_dir, $m3u_dir_ops, 0, true);
	}
	else if ($m3u_type == 3) {
	    $this->add_text_field($defs,
        	'm3u', 'Ссылка на плейлист:', $m3u,
		false, false, false, true, 500, false, true);
	}

        $this->add_combobox($defs,
            'use_proxy', 'Использовать Proxy для UDP:',
            $use_proxy, $show_ops, 0, true);
	
	if ($use_proxy == 'yes') {

    	    $this->add_text_field($defs,
        	'proxy_ip', 'Адрес proxy-сервера (IP или DNS):', $proxy_ip,
		false, false, false, true, 500, false, true);

	    $this->add_text_field($defs,
    		'proxy_port', 'Порт proxy-сервера:', $proxy_port,
        	true, false, false,  true, null, false, true);
	}

        return $defs;
    }

    public function get_control_defs(MediaURL $media_url, &$plugin_cookies)
    {
        return $this->do_get_control_defs($plugin_cookies);
    }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        
        if ($user_input->action_type === 'confirm' || $user_input->action_type === 'apply' )
        {
            $control_id = $user_input->control_id;
            $new_value = $user_input->{$control_id};
            hd_print("Setup: changing $control_id value to $new_value");

            if ($control_id === 'show_tv')
                $plugin_cookies->show_tv = $new_value;
            else if ($control_id === 'm3u')
                $plugin_cookies->m3u = $new_value;
 	   
            else if ($control_id === 'm3u_type')
                $plugin_cookies->m3u_type = $new_value;
	   
	   else if ($control_id === 'm3u_dir')
                $plugin_cookies->m3u_dir = $new_value;
	   
	   else if ($control_id === 'epg_shift')
                $plugin_cookies->epg_shift = $new_value;
            
	   else if ($control_id === 'use_proxy') {
            	    $plugin_cookies->use_proxy = $new_value;
            	    $plugin_cookies->proxy_ip = isset($plugin_cookies->proxy_ip) ? $plugin_cookies->proxy_ip : '192.168.1.1';
		    $plugin_cookies->proxy_port = isset($plugin_cookies->proxy_port) ? $plugin_cookies->proxy_port : '9999';
                }
            else if ($control_id === 'proxy_ip')
                $plugin_cookies->proxy_ip = $new_value;
            else if ($control_id === 'proxy_port')
                $plugin_cookies->proxy_port = $new_value;
	else if ($control_id === 'load_ex')
                $plugin_cookies->load_ex = $new_value;

        }

        return ActionFactory::reset_controls(
            $this->do_get_control_defs($plugin_cookies));
    }
}

///////////////////////////////////////////////////////////////////////////
?>
