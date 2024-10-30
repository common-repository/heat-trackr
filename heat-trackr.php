<?php
/**
 * @package heat-trackr
 * @version 1.01
 */
/*
Plugin Name: Heat Trackr
Plugin URI: http://www.mindseyesoftware.net
Description: This plugin allows the user to: 1) Display heatmaps 2) Display Clickmaps 3) Display Scattermaps (and optional pie charts) 4) Display Scrollmaps 5) Additionally the user can also configure and measure A/B style split testing.
Author: mindseyesoftware
Version: 1.01
Author URI: http://www.mindseyesoftware.net
*/

//ini_set('memory_limit', '1024M');

include('heat-trackr_draw.php');
include('heat-trackr_Browser.php');

$upload_dir = wp_upload_dir();
//$geoip_inc = WP_CONTENT_DIR."/uploads/heat-trackr/geoip.inc";
$geoip_inc = $upload_dir['basedir']."/heat-trackr/geoip.inc";
if (file_exists($geoip_inc)) {
	include($geoip_inc);
}
//$geoip_dat = WP_CONTENT_DIR."/uploads/heat-trackr/GeoIP.dat";
$geoip_dat = $upload_dir['basedir']."/heat-trackr/GeoIP.dat";

if (isset($_GET['wpslt_ping'])) {
    $id = $_GET['wpslt_ping'];
    $values = array('status'=> 2);
    $wpdb->update('wp_heat_trackr_abtest_trans', $values, array('tid'=>$id));
}

add_shortcode( 'WPSLT', 'var_loop' );
function var_loop($atts) {
    ob_start();
    require_once('heat-trackr_abtest_frontend_code.php');
    $out = $out.ob_get_contents();
    ob_end_clean();

    return $out;    
}

register_activation_hook(__FILE__,'heat_trackr_install');
register_uninstall_hook(__FILE__, 'heat_trackr_uninstall');

add_action('plugins_loaded', 'heat_trackr_update_db_check');
add_action('wp_enqueue_scripts', 'heat_trackr_enqueue_scripts');
add_action('admin_menu', 'heat_trackr_plugin_menu');

//add_action('init', 'heat_trackr_set_newuser_cookie');
function heat_trackr_set_newuser_cookie() {
    if (!isset($_COOKIE['heat-trackr-cookie'])) {
		$time = time();
        setcookie('heat-trackr-cookie', $time, $time+(3600*24*90), COOKIEPATH, COOKIE_DOMAIN, false); // expires in 90 days
    }
}

global $heat_trackr_db_version;
$heat_trackr_db_version = "1.0";

function heat_trackr_install() {
	global $wpdb;
	global $heat_trackr_db_version;
	$installed_ver = get_option("heat_trackr_db_version");

	if( $installed_ver != $heat_trackr_db_version ) {
		$table_name = $wpdb->prefix . "heat_trackr_clicks";
		// Create the database table for clicks
		$sql1 = "CREATE TABLE ".$table_name." (
			id INTEGER NOT NULL AUTO_INCREMENT,
			url VARCHAR(256) DEFAULT '' NOT NULL,
			reference TEXT DEFAULT '' NOT NULL,
			absPosX INTEGER NOT NULL,
			absPosY INTEGER NOT NULL,
			posX INTEGER NOT NULL,
			posY INTEGER NOT NULL,
			clickDate DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
			dayOfWeek VARCHAR(9) DEFAULT '' NOT NULL,
            timeOfDay VARCHAR(15),
			timeToClick INTEGER NOT NULL,
			dataId INTEGER NOT NULL,
			UNIQUE KEY id (id)
		);";

		$table_name = $wpdb->prefix . "heat_trackr_scrolls";
		// Create the database table for scrolls
		$sql2 = "CREATE TABLE ".$table_name." (
			id INTEGER NOT NULL AUTO_INCREMENT,
			url VARCHAR(256) DEFAULT '' NOT NULL,
			height INTEGER NOT NULL,
			count INTEGER NOT NULL,
			clickDate DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
			UNIQUE KEY id (id)
		);";
        
		$table_name = $wpdb->prefix . "heat_trackr_data";
		// Create the database table for data
		$sql3 = "CREATE TABLE ".$table_name." (
			id INTEGER NOT NULL AUTO_INCREMENT,
			visitor VARCHAR(9) DEFAULT '' NOT NULL,
			referrer VARCHAR(256) DEFAULT '' NOT NULL,
			searchTerms VARCHAR(256) DEFAULT '' NOT NULL,
			searchEngine VARCHAR(32) DEFAULT '' NOT NULL,
			country VARCHAR(32) DEFAULT '' NOT NULL,
			os VARCHAR(32) DEFAULT '' NOT NULL,
			browser VARCHAR(32) DEFAULT '' NOT NULL,
			windowWidth VARCHAR(8) DEFAULT '' NOT NULL,
			UNIQUE KEY id (id)
		);";
		
		$table_name = $wpdb->prefix . "heat_trackr_abtest_experiments";
		// Create the database table for experiments
		$sql4 = "CREATE TABLE ".$table_name." (
			id int(11) NOT NULL AUTO_INCREMENT,
			exp_name varchar(255) NOT NULL,
			original varchar(255) NOT NULL,
			variation1 varchar(255) NOT NULL,
			variation2 varchar(255) NOT NULL,
			variation3 varchar(255) NOT NULL,
			status varchar(50) NOT NULL,
			btwn int NOT NULL,
			PRIMARY KEY (id)
        );";
		
  		$table_name = $wpdb->prefix . "heat_trackr_abtest_trans";
		// Create the database table for experiment trans
		$sql5 = "CREATE TABLE ".$table_name." (
			tid int(11) NOT NULL AUTO_INCREMENT,
			exp_id int(11) NOT NULL,
			var_id int(11) NOT NULL,
			status int(11) NOT NULL,
			PRIMARY KEY (tid)
        );";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql1);
		dbDelta($sql2);
		dbDelta($sql3);
		dbDelta($sql4);
		dbDelta($sql5);

		update_option("heat_trackr_db_version", $heat_trackr_db_version);
	}
}

function heat_trackr_update_db_check() {
    global $heat_trackr_db_version;
    if (get_site_option('heat_trackr_db_version') != $heat_trackr_db_version) {
        heat_trackr_install();
    }
}

function heat_trackr_uninstall() {
	global $wpdb;

	if (get_option('heat_trackr_data_delete') == "1") {
		$table_name = $wpdb->prefix . "heat_trackr_clicks";
		$wpdb->query("DROP TABLE IF EXISTS $table_name");

		$table_name = $wpdb->prefix . "heat_trackr_scrolls";
		$wpdb->query("DROP TABLE IF EXISTS $table_name");

		$table_name = $wpdb->prefix . "heat_trackr_data";
		$wpdb->query("DROP TABLE IF EXISTS $table_name");

		$table_name = $wpdb->prefix . "heat_trackr_abtest_experiments";
		$wpdb->query("DROP TABLE IF EXISTS $table_name");

  		$table_name = $wpdb->prefix . "heat_trackr_abtest_trans";
		$wpdb->query("DROP TABLE IF EXISTS $table_name");
	}
	
	if (get_option('heat_trackr_options_delete') == "1") {
		delete_option('heat_trackr_collect');
		delete_option('heat_trackr_collect_scrolls');
		delete_option('heat_trackr_all_clicks');
		delete_option('heat_trackr_period_start');
		delete_option('heat_trackr_period_end');
		delete_option('heat_trackr_display');
		delete_option('heat_trackr_click_summary');
		delete_option('heat_trackr_display_source');
		delete_option('heat_trackr_all_URL');
		delete_option('heat_trackr_URL');
		delete_option('heat_trackr_next_number');
	}
}

function heat_trackr_enqueue_scripts() {
	if (!is_admin()) {
		wp_register_script( 'jquery', '//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js' );
		wp_enqueue_script( 'jquery' );
		
		wp_register_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style('jquery-ui-style');
		wp_register_script( 'jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/jquery-ui.min.js' );
		wp_enqueue_script('jquery-ui');

		wp_enqueue_script('jquery-ui-dialog');
		//wp_enqueue_style('wp-jquery-ui-dialog');
		
		// embed the javascript files that make the AJAX requests
		wp_enqueue_script( 'collect_clicks', plugin_dir_url( __FILE__ ) . 'js/collect_clicks.js', array( 'jquery' ) );
		wp_enqueue_script( 'display_clicks', plugin_dir_url( __FILE__ ) . 'js/display_clicks.js', array( 'jquery' ) );

		wp_enqueue_script( 'flot', plugin_dir_url( __FILE__ ) . 'js/jquery.flot.js', array( 'jquery' ) );
		wp_enqueue_script( 'flot_pie', plugin_dir_url( __FILE__ ) . 'js/jquery.flot.pie.js', array( 'jquery' ) );

		// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
		wp_localize_script( 'collect_clicks', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

		// The CSS file used for the heatmap
		wp_enqueue_style( 'heat_trackr', plugin_dir_url( __FILE__ ) . 'css/heat-trackr.css' );
	}
}

function heat_trackr_plugin_menu() {
	add_menu_page('Heat Trackr : Settings', 'Heat Trackr', 'manage_options', 'heat-trackr-handle', 'heat_trackr_settings');
	add_submenu_page( 'heat-trackr-handle', 'Heat Trackr : Settings', 'Settings', 'manage_options', 'heat-trackr-handle', 'heat_trackr_settings');
	add_submenu_page( 'heat-trackr-handle', 'Heat Trackr : A/B Split Test', 'A/B Split Test', 'manage_options', 'heat-trackr_abtest_admin.php', 'heat_trackr_abtest_admin');
}

function heat_trackr_settings() {
	add_option('heat_trackr_collect', "1");
	add_option('heat_trackr_collect_scrolls', "1");
	add_option('heat_trackr_all_clicks', "1");
	add_option('heat_trackr_period_start');
	add_option('heat_trackr_period_end');
	add_option('heat_trackr_display', "1");
	add_option('heat_trackr_click_summary', "1");
	add_option('heat_trackr_display_source', "relative");
	add_option('heat_trackr_data_delete', "");
	add_option('heat_trackr_options_delete', "");
	add_option('heat_trackr_all_URL', "1");
	add_option('heat_trackr_URL');
	add_option('heat_trackr_next_number', "1");

	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	require_once 'heat-trackr_settings.php';
	//include('heat-trackr_settings.php');
}

function heat_trackr_abtest_admin() {
    global $wpdb;
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	require_once 'heat-trackr_abtest_admin.php';
    //include('heat-trackr_abtest_admin.php');
}

add_action( 'wp_ajax_nopriv_heat_trackr_add_click', 'heat_trackr_add_click' );
add_action( 'wp_ajax_heat_trackr_add_click', 'heat_trackr_add_click' );

function heat_trackr_add_click() {
	if (get_option('heat_trackr_collect') == "") exit;
	if (current_user_can('administrator')) exit; // Comment out this line to allow testing while logged in

	if ( get_magic_quotes_gpc() ) {
		$_POST = array_map( 'stripslashes_deep', $_POST );
	}

	global $wpdb;

    if (isset($_COOKIE['heat-trackr-cookie'])) {
		// Cookie was set less than 24 hours ago, categorize visitor as "New"
		if ((intval($_COOKIE['heat-trackr-cookie']) + (3600*24)) > time())
			$visitor = "New";
		else
			$visitor = "Returning";
    } else {
		$visitor = "New";
		heat_trackr_set_newuser_cookie();
    }
	
    $dataId = $_POST['dataId'];
    if ($dataId == -1) {
		/*if (isset($_SERVER["HTTP_REFERER"])) { //$_SERVER["HTTP_HOST"]
			$referrer = parse_url($_SERVER["HTTP_REFERER"]);
			$referrer = $referrer['host'];
		} else {
			$referrer = "";
		}*/
		$referrer = parse_url($_POST['referrer']);
		$referrer = $referrer['host'];

		$searchTerms = heat_trackr_searchTerms($_POST['referrer']);
		if ($searchTerms == "") $searchTerms = "None";
		$searchEngine = "";
		if ($referrer == "") {
			$referrer = "Direct";
			$searchEngine = "None";
		} else {
			$searchEngine = heat_trackr_searchEngine($referrer);
		}

		$IP = $_SERVER['REMOTE_ADDR'];
		global $geoip_dat;
		if (!empty($IP) && file_exists($geoip_dat)) {
			$geoip = geoip_open($geoip_dat, GEOIP_STANDARD);
			$country = geoip_country_name_by_addr($geoip, $IP);
			if (!$country) $country = "Unknown";

			/*$country2 = file_get_contents('http://api.hostip.info/get_html.php?ip='.$IP);
			if (strpos($country2, "Unknown Country") === false) {
				$country2 = explode("(", $country2);
				$country = trim(str_replace("Country: ", "", $country2[0]));
			} else {
				$country = "Unknown";
			}*/
		} else {
			$country = "Unknown";
		}

		$browser = new Browser();
		$os = $browser->getPlatform();
		$browserName = $browser->getBrowser();
		$windowWidth = $_POST['windowWidth'];
        //$request_url = $_SERVER['REQUEST_URI'];

        $table_name = $wpdb->prefix . "heat_trackr_data";
        $data = array(
            'visitor' => $visitor,
            'referrer' => $referrer,
            'searchTerms' => $searchTerms,
            'searchEngine' => $searchEngine,
            'country' => $country,
            'os' => $os,
            'browser' => $browserName,
            'windowWidth' => $windowWidth
        );
        $wpdb->insert($table_name, $data);
		$dataId = $wpdb->insert_id;
	}

	$table_name = $wpdb->prefix . "heat_trackr_clicks";

	// Get POST Variables from AJAX for add
	$url = $_POST['url'];
	if ($url != '') {
		$reference = $_POST['reference'];
		$absPosX = $_POST['absPosX'];
		$absPosY = $_POST['absPosY'];
		$posX = $_POST['posX'];
		$posY = $_POST['posY'];
		$clickDate = date('Y-m-d H:i:s');
        $dayOfWeek = heat_trackr_getDayOfWeek(date('D'));

        //$timeOfDay = date("h:i A");
		$prevTime = time() - (time() % 1800);
		$nextTime = $prevTime + 1800;
		$timeOfDay = date('H:iA', $prevTime) . "-" . date('H:iA', $nextTime);
		$timeToClick = $_POST['timeToClick'];
		
		$data = array(
			'url' => $url,
			'reference' => $reference,
			'absPosX' => $absPosX,
			'absPosY' => $absPosY,
			'posX' => $posX,
			'posY' => $posY,
			'clickDate' => $clickDate,
			'dayOfWeek' => $dayOfWeek,
			'timeOfDay' => $timeOfDay,
			'timeToClick' => $timeToClick,
			'dataId' => $dataId
		);

		$wpdb->insert($table_name, $data);
		//$lastId = $wpdb->insert_id;

		echo $dataId;
	}

	exit;
}

function heat_trackr_getDayOfWeek($day) {
	if ($day == 'Mon') return 'Monday';
	else if ($day == 'Tue') return 'Tuesday';
	else if ($day == 'Wed') return 'Wednesday';
	else if ($day == 'Thu') return 'Thursday';
	else if ($day == 'Fri') return 'Friday';
	else if ($day == 'Sat') return 'Saturday';
	else if ($day == 'Sun') return 'Sunday';
}
	
function heat_trackr_searchTerms($url = false) {
    if (!$url && !$url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false) {
        return '';
    }

    $urlPieces = parse_url($url);
    $query = isset($urlPieces['query']) ? $urlPieces['query'] : (isset($urlPieces['fragment']) ? $urlPieces['fragment'] : '');
    if (!$query) {
        return '';
    }
    parse_str($query, $queryPieces);

	if (isset($queryPieces['q'])) {
		return $queryPieces['q'];
    } else if (isset($queryPieces['p'])) {
		return $queryPieces['p'];
	} else if (isset($queryPieces['searchfor'])) {
		return $queryPieces['searchfor'];
	} else if (isset($queryPieces['keywords'])) {
		return $queryPieces['keywords'];
	} else if (isset($queryPieces['keyword'])) {
		return $queryPieces['keyword'];
	} else if (isset($queryPieces['key'])) {
		return $queryPieces['key'];
	} else if (isset($queryPieces['_nkw'])) {
		return $queryPieces['_nkw'];
	} else if (isset($queryPieces['searchTerms'])) {
		return $queryPieces['searchTerms'];
	} else if (isset($queryPieces['field-keywords'])) {
		return $queryPieces['field-keywords'];
	} else if (isset($queryPieces['query'])) {
		return $queryPieces['query'];
	} else if (isset($queryPieces['ovkey'])) {
		return $queryPieces['ovkey'];
	} else if (isset($queryPieces['search'])) {
		return $queryPieces['search'];
	} else if (isset($queryPieces['term'])) {
		return $queryPieces['term'];
    } else if (isset($queryPieces['on'])) {
		return $queryPieces['on'];
	} else if (isset($queryPieces['w'])) {
		return $queryPieces['w'];
	} else if (isset($queryPieces['text'])) {
		return $queryPieces['text'];
    } else if (isset($queryPieces['strsearchstring'])) {
		return $queryPieces['strsearchstring'];
    } else if (isset($queryPieces['s'])) {
		return $queryPieces['s'];
	} else if (isset($queryPieces['terms'])) {
		return $queryPieces['terms'];
	} else if (isset($queryPieces['qs'])) {
		return $queryPieces['qs'];
	} else if (isset($queryPieces['encquery'])) {
		return $queryPieces['encquery'];
	} else if (isset($queryPieces['wd'])) {
		return $queryPieces['wd'];
	} else if (isset($queryPieces['search_word'])) {
		return $queryPieces['search_word'];
	} else if (isset($queryPieces['qt'])) {
		return $queryPieces['qt'];
	} else if (isset($queryPieces['words'])) {
		return $queryPieces['words'];
	} else if (isset($queryPieces['rdata'])) {
		return $queryPieces['rdata'];
	} else if (isset($queryPieces['qs'])) {
		return $queryPieces['qs'];
	} else if (isset($queryPieces['szukaj'])) {
		return $queryPieces['szukaj'];
	} else if (isset($queryPieces['k'])) {
		return $queryPieces['k'];
    }

	return '';
}

function heat_trackr_searchEngine($searchEngine) {
	// Top 15 Most Popular Search Engines according to http://www.ebizmba.com/articles/search-engines
	/*$array = array('google.com','bing.com','yahoo.com','ask.com','aol.com','mywebsearch.com','lycos.com','dogpile.com','webcrawler.com','info.com','infospace.com','search.com','excite.com','goodsearch.com','altavista.com');
	foreach ($array as $value) {
		if (strpos($searchEngine, $value)) {
			return $value;
		}
	}*/
	
	if (strpos($searchEngine, 'google.com')) {
		return 'Google';
	} else if (strpos($searchEngine, 'bing.com')) {
		return 'Bing';
	} else if (strpos($searchEngine, 'yahoo.com')) {
		return 'Yahoo';
	} else if (strpos($searchEngine, 'ask.com')) {
		return 'Ask';
	} else if (strpos($searchEngine, 'aol.com')) {
		return 'Aol Search';
	} else if (strpos($searchEngine, 'mywebsearch.com')) {
		return 'MyWebSearch';
	} else if (strpos($searchEngine, 'lycos.com')) {
		return 'Lycos';
	} else if (strpos($searchEngine, 'dogpile.com')) {
		return 'DogPile';
	} else if (strpos($searchEngine, 'webcrawler.com')) {
		return 'WebCrawler';
	} else if (strpos($searchEngine, 'info.com')) {
		return 'Info';
	} else if (strpos($searchEngine, 'infospace.com')) {
		return 'Infospace';
	} else if (strpos($searchEngine, 'search.com')) {
		return 'Search';
	} else if (strpos($searchEngine, 'excite.com')) {
		return 'Excite';
	} else if (strpos($searchEngine, 'goodsearch.com')) {
		return 'GoodSearch';
	} else if (strpos($searchEngine, 'altavista.com')) {
		return 'AltaVista';
	}
	
	return 'Unknown';
}

add_action( 'wp_ajax_nopriv_heat_trackr_add_scroll', 'heat_trackr_add_scroll' );
add_action( 'wp_ajax_heat_trackr_add_scroll', 'heat_trackr_add_scroll' );

function heat_trackr_add_scroll() {
	if (get_option('heat_trackr_collect_scrolls') == "") exit;
	if (current_user_can('administrator')) exit; // Comment out this line to allow testing while logged in

	if ( get_magic_quotes_gpc() ) {
		$_POST = array_map( 'stripslashes_deep', $_POST );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . "heat_trackr_scrolls";

	// Get POST Variables from AJAX for update
	$scrollId = intval($_POST['scrollId']);
	$height = intval($_POST['height']);
	if ($scrollId >= 0) { // we have a new scroll value on the same document window
		$wpdb->update( $table_name, array( 'height' => $height ), array( 'id' => $scrollId ) );
		echo $scrollId;
		exit;
	}

	// Get POST Variables from AJAX
	$url = $_POST['url'];
	$count = intval($_POST['count']);
	$clickDate = date( 'Y-m-d H:i:s' );

	$data = array(
		'url' => $url,
		'height' => $height,
		'count' => $count,
		'clickDate' => $clickDate
	);

	$wpdb->insert($table_name, $data);
	$scrollId = $wpdb->insert_id;

	echo $scrollId;
	exit;
}

add_action( 'wp_ajax_nopriv_heat_trackr_get_clicks', 'heat_trackr_get_clicks' );
add_action( 'wp_ajax_heat_trackr_get_clicks', 'heat_trackr_get_clicks' );

function heat_trackr_get_clicks() {
	if ( get_magic_quotes_gpc() ) {
		$_POST = array_map( 'stripslashes_deep', $_POST );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . "heat_trackr_clicks";
	$url = $_POST['url'];
	$mapType = intval($_POST['mapType']);

	if ($mapType == 3) {
		$table_name2 = $wpdb->prefix . "heat_trackr_data";
		$queryStr = heat_trackr_buildGetClicksQuery($table_name, $url, $table_name2);
	} else {
		$queryStr = heat_trackr_buildGetClicksQuery($table_name, $url, "");
	}
	$results = $wpdb->get_results($queryStr);

	$pointArray = array();
	$dotSize = 32;
	$halfSize = $dotSize / 2;
	foreach ($results as $spot) {
		if ($mapType == 3) {
			$timeCategory = heat_trackr_makeTimeCategory($spot->timeToClick);
			$spotDetails = array( 'reference'=>$spot->reference, 'absPosX'=>$spot->absPosX, 'absPosY'=>$spot->absPosY, 'posX'=>$spot->posX, 'posY'=>$spot->posY,
				'dayOfWeek'=>$spot->dayOfWeek, 'timeOfDay'=>$spot->timeOfDay, 'timeToClick'=>$timeCategory,
				'visitor'=>$spot->visitor, 'referrer'=>$spot->referrer, 'searchTerms'=>$spot->searchTerms, 'searchEngine'=>$spot->searchEngine,
				'country'=>$spot->country, 'os'=>$spot->os, 'browser'=>$spot->browser, 'windowWidth'=>$spot->windowWidth );
		} else if ($mapType == 2 && get_option('heat_trackr_click_summary') == "1") {
			// Zero everything out as we only want the reference start x/y position
			$spotDetails = array( 'reference'=>$spot->reference, 'absPosX'=>0, 'absPosY'=>$halfSize, 'posX'=>0, 'posY'=>$halfSize, 'id'=>$spot->id );
		} else if (get_option('heat_trackr_display_source') == "absolute") {
			$spotDetails = array( 'reference'=>'', 'absPosX'=>$spot->absPosX, 'absPosY'=>$spot->absPosY, 'posX'=>$spot->posX, 'posY'=>$spot->posY, 'id'=>$spot->id );
		} else {
			$spotDetails = array( 'reference'=>$spot->reference, 'absPosX'=>$spot->absPosX, 'absPosY'=>$spot->absPosY, 'posX'=>$spot->posX, 'posY'=>$spot->posY, 'id'=>$spot->id );
		}
		$pointArray[] = $spotDetails;
	}

	echo json_encode($pointArray);
	exit;
}

function heat_trackr_makeTimeCategory($timeToClick) {
	if ($timeToClick < 2) return 'less than 2 seconds';
	if ($timeToClick < 3) return '2-3 seconds';
	if ($timeToClick < 4) return '3-4 seconds';
	if ($timeToClick < 5) return '4-5 seconds';
	if ($timeToClick < 6) return '5-6 seconds';
	if ($timeToClick < 8) return '6-8 seconds';
	if ($timeToClick < 10) return '8-10 seconds';
	if ($timeToClick < 15) return '10-15 seconds';
	if ($timeToClick < 20) return '15-20 seconds';
	if ($timeToClick < 25) return '20-25 seconds';
	if ($timeToClick < 30) return '25-30 seconds';
	if ($timeToClick < 45) return '30-45 seconds';
	if ($timeToClick < 60) return '45-60 seconds';
	if ($timeToClick < 90) return '60-90 seconds';
	if ($timeToClick < 120) return '90-120 seconds';
	if ($timeToClick < 180) return '2-3 minutes';
	if ($timeToClick < 240) return '3-4 minutes';
	if ($timeToClick < 300) return '4-5 minutes';
	if ($timeToClick < 600) return '5-10 minutes';
	if ($timeToClick < 900) return '10-15 minutes';
	if ($timeToClick < 1800) return '15-30 minutes';
	if ($timeToClick < 3600) return '30-60 minutes';
	
	return 'greater than 60 minutes';
}

add_action( 'wp_ajax_nopriv_heat_trackr_get_summary', 'heat_trackr_get_summary' );
add_action( 'wp_ajax_heat_trackr_get_summary', 'heat_trackr_get_summary' );

function heat_trackr_get_summary() {
	if ( get_magic_quotes_gpc() ) {
		$_POST = array_map( 'stripslashes_deep', $_POST );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . "heat_trackr_clicks";
	$url = $_POST['url'];
	$idValues = json_decode($_POST['idValues']);

	$table_name2 = $wpdb->prefix . "heat_trackr_data";
	$queryStr = heat_trackr_buildGetClicksQuery($table_name, $url, $table_name2);

	$pointArray = array();
	foreach ($idValues as $id) {
		$queryStr = heat_trackr_buildGetClicksQuery($table_name, $id, $table_name2);
		$queryStr = str_replace("url", $wpdb->prefix . "heat_trackr_clicks.id", $queryStr);
		$results = $wpdb->get_results($queryStr);

		foreach ($results as $spot) {
			$timeCategory = heat_trackr_makeTimeCategory($spot->timeToClick);
			$spotDetails = array( 'dayOfWeek'=>$spot->dayOfWeek, 'timeOfDay'=>$spot->timeOfDay, 'timeToClick'=>$timeCategory,
				'visitor'=>$spot->visitor, 'referrer'=>$spot->referrer, 'searchTerms'=>$spot->searchTerms, 'searchEngine'=>$spot->searchEngine,
				'country'=>$spot->country, 'os'=>$spot->os, 'browser'=>$spot->browser, 'windowWidth'=>$spot->windowWidth );
			$pointArray[] = $spotDetails;
		}
	}

	echo json_encode($pointArray);
	exit;
}

add_action( 'wp_ajax_nopriv_heat_trackr_get_scrolls', 'heat_trackr_get_scrolls' );
add_action( 'wp_ajax_heat_trackr_get_scrolls', 'heat_trackr_get_scrolls' );

function heat_trackr_get_scrolls() {
	if ( get_magic_quotes_gpc() ) {
		$_POST = array_map( 'stripslashes_deep', $_POST );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . "heat_trackr_scrolls";
	$url = $_POST['url'];
	$innerWidth = intval($_POST['innerWidth']);

	$queryStr = heat_trackr_buildGetClicksQuery($table_name, $url, "");
	$results = $wpdb->get_results($queryStr);

	$intervalVal = array("times"=>0);
	$intervalArray = array();
	$totalCount = 0;
	foreach ($results as $scroll) {
		$height = $scroll->height;
		$count = $scroll->count;
		if ($count == 0) $count = 1;
		if (!isset($intervalArray[$height])) {
			$intervalArray[$height] = $intervalVal;
		}
		$intervalArray[$height]["times"] += $count;
		$totalCount += $count;
	}

	$scrolls = "";
    foreach ($intervalArray as $height => $interval) {
		$height = $height . "px";
		$percent = intval($interval["times"] / $totalCount * 100);
		$count = $interval["times"] . " : " . $percent . "%";
		$x = ($innerWidth - (128)) . "px";
		$scrolls .= "<div class='ht-rectangle' style='right:150px; top:$height;'>$count</div>";
	}

	echo $scrolls;
	exit;
}

function heat_trackr_buildGetClicksQuery($table_name, $url, $table_name2) {
	if (get_option('heat_trackr_all_clicks') != "1") {
		$start = get_option('heat_trackr_period_start');
		if (($timestamp = strtotime($start)) === false) {
			$start = ''; // $start is bogus
		} else {
			$start = date('Y-m-d H:i', $timestamp);
		}

		$end = get_option('heat_trackr_period_end');
		if (($timestamp = strtotime($end)) === false) {
			$end = ''; // $end is bogus
		} else {
			$end = date('Y-m-d H:i', $timestamp);
		}
	}

	if ($table_name2 != "") {
		$queryStr = "SELECT * FROM $table_name, $table_name2 WHERE url = '$url' AND $table_name.dataId = $table_name2.id";
	} else {
		$queryStr = "SELECT * FROM $table_name WHERE url = '$url'";
	}
	
	if (get_option('heat_trackr_all_clicks') == "1" || ($start == '' && $end == '')) {
		// No other criteria
	} else if ($start != '' && $end == '') {
		$queryStr .= " AND clickDate >= '$start'";
	} else if ($start == '' && $end != '') {
		$queryStr .= " AND clickDate <= '$end'";
	} else {
		$queryStr .= " AND clickDate >= '$start' AND clickDate <= '$end'";
	}

	return $queryStr;
}

add_action( 'wp_ajax_nopriv_heat_trackr_display_heatmap', 'heat_trackr_display_heatmap' );
add_action( 'wp_ajax_heat_trackr_display_heatmap', 'heat_trackr_display_heatmap' );

function heat_trackr_display_heatmap() {
	// Get the data from the post
	$_POST = array_map( 'stripslashes_deep', $_POST );

	$mapType = intval($_POST['mapType']);
	$innerWidth = intval($_POST['innerWidth']);
	$innerHeight = intval($_POST['innerHeight']);
	$results = json_decode($_POST['send_spots']);

	$point = array("x"=>0, "y"=>0, "count"=>0, 'idValues' => array());
	$pointArray = array();
	$dotSize = 32;
	$halfSize = $dotSize / 2;

	foreach ($results as $spot) {
		$x = intval($spot->posX);
		$y = intval($spot->posY);

		if (($x + $halfSize) > $innerWidth) {
			$innerWidth = $x + $halfSize;
		}
		if (($y + $halfSize) > $innerHeight) {
			$innerHeight = $y + $halfSize;
		}

		// create an entry for the point, if one does not exist, increment the count
		$thispoint = $x."x".$y;
		if (!isset($pointArray[$thispoint])) {
			$pointArray[$thispoint] = $point;
			$pointArray[$thispoint]["x"] = $x;
			$pointArray[$thispoint]["y"] = $y;
		}
		$pointArray[$thispoint]["count"] += 1;
		if ($mapType == 2/* && get_option('heat_trackr_click_summary') == "1"*/)
			$pointArray[$thispoint]["idValues"][] = intval($spot->id);
	}
	
	if ($mapType == 1) {
		// doing a heat map
		$heatmap = new HeatmapDraw();
		$heatmap->SetupHM($innerWidth, $innerHeight, $dotSize, $pointArray);
        $heatmap->GenerateHeatMap();

		//$newNameEnd = "heatmap".strval(rand(1, 20)).".jpg";
		$newNameEnd = "heatmap".get_option('heat_trackr_next_number').".jpg";
		$nextNumber = intval(get_option('heat_trackr_next_number')) + 1;
		if ($nextNumber <= 20)
			update_option('heat_trackr_next_number', strval($nextNumber));
		else
			update_option('heat_trackr_next_number', "1");

		$newname = dirname(__FILE__)."/images/".$newNameEnd;
		$oldname = dirname(__FILE__)."/images/heatmap.jpg";
		rename($oldname, $newname); // rename file to avoid problems with WP caching

		$image = plugin_dir_url( __FILE__ )."images/" . $newNameEnd;
		echo $image;
		// end heat map code
	} else if ($mapType == 2) {
		// doing a clickmap or scattermap
		$colorArray = heat_trackr_makeColorArray($pointArray);
		//$sortPointArray = heat_trackr_array_sort($pointArray, 'count', SORT_ASC); // Not needed, using zindex values to keep "hotter" colors on top
		
		$spotsArray = array();
		foreach ($pointArray as $spot) {
			$x = $spot["x"] - $halfSize;
			$y = $spot["y"] - $halfSize;
			$count = $spot["count"];
			if (isset($colorArray[$count])) {
				$thiscolor = $colorArray[$count]["heat"];
				$thiszindex = $colorArray[$count]["zindex"];
				$spotsArray[] = array( 'x'=>$x, 'y'=>$y, 'count'=>$count, 'color'=>$thiscolor, 'zindex'=>$thiszindex, 'idValues'=>$spot['idValues'] );
			}
		}
		echo json_encode($spotsArray);
		// end click map code
	}

	exit;
}

function heat_trackr_makeColorArray($pointArray) {
	$colorVal = array("times"=>0, "heat"=>"", "zindex"=>0);
	$colorArray = array();
	foreach ($pointArray as $spot) {
		$count = $spot["count"];
		if (!isset($colorArray[$count])) {
			$colorArray[$count] = $colorVal;
		}
		$colorArray[$count]["times"] += $count;
	}
	krsort($colorArray);

	$heatArray = heat_trackr_getColorArray(count($colorArray));
	$heatCount = 0;
	$zindex = 11000; // Make the number big enough so that the circles will stay on top of any other overlays
	foreach ($colorArray as &$color) {
		$color["heat"] = $heatArray[$heatCount++];
		$color["zindex"] = $zindex--;
	}
	
	return $colorArray;
}

add_action( 'wp_ajax_nopriv_heat_trackr_get_colors', 'heat_trackr_get_colors' );
add_action( 'wp_ajax_heat_trackr_get_colors', 'heat_trackr_get_colors' );

function heat_trackr_get_colors() {
	// Get the data from the post
	$_POST = array_map( 'stripslashes_deep', $_POST );

	$count = intval($_POST['count']);
	$heatArray = heat_trackr_getColorArray($count);

	echo json_encode($heatArray);
	exit;
}

add_action( 'wp_footer', 'heat_trackr_heatmap_button');
function heat_trackr_heatmap_button() {
	if (current_user_can('administrator')) {
		if (get_option('heat_trackr_display') == "1") {
		?>
			<div class="ht-heatmap-off">
				<span class="heatswitch-span">Heatmap On</span>
				<div id="heatswitch" class="heatswitch" style="display:none; float:right; margin-top:2px;">
					<img id="img-heatswitch" src="<?php echo plugins_url('images/flames.gif', __FILE__); ?>" alt="Loading"/>
				</div>
			</div>
			<div class="ht-clickmap-off">
				<span class="clickswitch-span">Clickmap On</span>
				<div id="clickswitch" class="clickswitch" style="display:none; float:right; margin-top:2px;">
					<img id="img-clickswitch" src="<?php echo plugins_url('images/counter.gif', __FILE__); ?>" alt="Loading"/>
				</div>
			</div>
			<div class="ht-breakmap-off">
				<span class="breakswitch-span">Scattermap On</span>
				<div id="breakswitch" class="breakswitch" style="display:none; float:right; margin-top:2px;">
					<img id="img-breakswitch" src="<?php echo plugins_url('images/clock.gif', __FILE__); ?>" alt="Loading"/>
				</div>
			</div>
			<div class="ht-scrollmap-off">
				<span class="scrollswitch-span">Scrollmap On</span>
				<div id="scrollswitch" class="scrollswitch" style="display:none; float:right; margin-top:2px;">
					<img id="img-scrollswitch" src="<?php echo plugins_url('images/scroll.gif', __FILE__); ?>" alt="Loading"/>
				</div>
			</div>
		<?php
		}
	}
}
?>