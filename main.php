<?php
	/*
	Plugin Name: Nearby Now Reviews and Audio Testimonials
	Plugin URI: http://servicepros.nearbynow.co/plugins/wordpress-plugins/
	Description: Nearby Now - Plugins for checkins, reviews, google reviews, heatmaps, photo and video galleries, and audio testimonials.
	Version: 2.0.3
	Author: Nearby Now
	Author URI: https://www.nearbynow.co
	*/

	class NearbyNow_ShortCode
	{
		static $add_scripts;

		static function init()
		{
			add_shortcode('recentreviews', array(__CLASS__, 'get_recent_reviews'));
			add_shortcode('serviceareamap', array(__CLASS__, 'get_service_area_map'));
			add_shortcode('serviceareareviewcombo', array(__CLASS__, 'get_service_area_review_combo_map'));
			add_shortcode('nationwidecombo', array(__CLASS__, 'get_nationwide_combo'));
			add_shortcode('nearbynowtestimonials', array(__CLASS__, 'get_testimonials'));
			add_shortcode('nearbynowphotogallery', array(__CLASS__, 'get_photogallery'));
			add_shortcode('faq', array(__CLASS__, 'get_faq'));
			add_shortcode('checkin', array(__CLASS__, 'get_checkin'));
			add_shortcode('review', array(__CLASS__, 'get_review'));
			add_shortcode('googlereviews', array(__CLASS__, 'get_google_reviews'));

			add_shortcode('heatmap', array(__CLASS__, 'get_heatmap'));
			add_shortcode('nntestimonials', array(__CLASS__, 'get_testimonials'));
			add_shortcode('nnphotogallery', array(__CLASS__, 'get_photogallery'));

			add_action('init', array(__CLASS__, 'register_scripts'));
			//add_action('wp_head', array(__CLASS__, 'register_open_graph'), 1);

			add_action('wp_footer',	array(__CLASS__, 'render_scripts'));
		}

		static function get_heatmap($atts)
		{
			self::$add_scripts = false;

			$url = self::ApiLocation() . "heatmap";

			$args = array(
				'method' => 'POST',
				'body' => self::get_comboparams($atts),
				'timeout' => 15
			);

			$response = wp_remote_post($url, $args);
			if( is_wp_error( $response ) ) {
			   return '';
			} else {
			   return $response['body'];
			}
		}

		static function register_open_graph() {
			$checkin_id = $_GET['usercheckin_id'];
			$survey_id = $_GET['css'];
			if (!empty($checkin_id) || !empty($survey_id)) {

				// Remove invalid SEO Yoast tags. These tags are invalid for dynamically rendered pages
				remove_action( 'wpseo_head', array( $GLOBALS['wpseo_og'], 'opengraph' ), 30 );
				remove_action('wp_head','rel_canonical');
				add_filter( 'wpseo_canonical', '__return_false',  10, 1 );
				remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);

				$options = get_option('nearbynow_options');
				$exclude_open_graph = $options['open_graph_exclude'];
				if (empty($exclude_open_graph) || !(bool)$exclude_open_graph) {
					$apitoken = $options['text_string'];
					$token = trim($apitoken);
					$url = "https://api.sidebox.com/plugin/opengraph?storefronttoken=$token&checkin_id=$checkin_id&review_id=$survey_id";
					$request = wp_remote_get($url);
					if ( is_wp_error( $request ) ) {
						return;
					}
					$body = wp_remote_retrieve_body( $request );
					if (!is_null($body)) {
						$json = json_decode( $body );
						if(!is_null($json)) {
							echo '<meta property="og:title" content="'.$json->title.'"/>';
							echo '<meta property="og:type" content="article"/>';
							echo '<meta property="og:url" content="'.$json->url.'"/>';
							echo '<meta property="og:image" content="'.$json->image.'"/>';
							echo '<meta property="og:site_name" content="'.$json->site_name.'"/>';
							echo '<meta property="og:description" content="'.$json->description.'"/>';
							echo '<meta property="fb:app_id" content="'.$json->app_id.'"/>';
						}
					}
				}
			}
		}

		static function get_checkin($atts)
		{
			self::$add_scripts = true;
			$id = $_GET['usercheckin_id'];
			if (!empty($id)) {
				$agent = urlencode($_SERVER['HTTP_USER_AGENT']);
				if (isset($atts['apikey'])) {
					$token = trim($atts['apikey']);
				}
				else {
					$options = get_option('nearbynow_options');
					$apitoken = $options['text_string'];
					$token = trim($apitoken);
				}

				$smm = "yes";
				$sp = "yes";

				if (isset($atts['showminimap']))
					$smm = trim($atts['showminimap']);

				if (isset($atts['showphotos']))
					$sp = trim($atts['showphotos']);

				$url = self::ApiLocation() . "usercheckin?storefronttoken=$token&id=$id&agent=$agent&showminimap=$smm&showphotos=$sp";
				$request = wp_remote_get($url, array( 'timeout' => 15));
				$body = wp_remote_retrieve_body( $request );
				if( is_wp_error( $request ) ) {
					return;
				} else {
					return $body;
				}
			}
		}

		static function get_review($atts)
		{
			self::$add_scripts = true;
			$id = $_GET['css'];
			if (!empty($id)) {
				$agent = urlencode($_SERVER['HTTP_USER_AGENT']);
				if (isset($atts['apikey'])) {
					$token = trim($atts['apikey']);
				}
				else {
					$options = get_option('nearbynow_options');
					$apitoken = $options['text_string'];
					$token = trim($apitoken);
				}

				$smm = "yes";

				if (isset($atts['showminimap']))
					$smm = trim($atts['showminimap']);

				$url = self::ApiLocation() . "survey?storefronttoken=$token&id=$id&agent=$agent&showminimap=$smm";
				$request = wp_remote_get($url, array( 'timeout' => 15));
				$body = wp_remote_retrieve_body( $request );
				if( is_wp_error( $request ) ) {
					return;
				} else {
					return $body;
				}
			}
		}

		static function get_recent_reviews($atts)
		{
			self::$add_scripts = true;

			$url = self::ApiLocation() . "nearbyreviews";
			$args = array(
				'method' => 'POST',
				'body' => self::get_pluginparams($atts),
				'timeout' => 15
			);
			$response = wp_remote_post($url, $args);
			if( is_wp_error( $response ) ) {
			   return '';
			} else {
			   return $response['body'];
			}
		}

		static function get_google_reviews($atts)
		{
			self::$add_scripts = true;

			$url = self::ApiLocation() . "googlereviews";
			$args = array(
				'method' => 'POST',
				'body' => self::get_pluginparams($atts),
				'timeout' => 15
			);
			$response = wp_remote_post($url, $args);
			if( is_wp_error( $response ) ) {
			   return '';
			} else {
			   return $response['body'];
			}
		}

		static function get_service_area_map($atts)
		{
			self::$add_scripts = true;

			$url = self::ApiLocation() . "nearbyservicearea";
			$args = array(
				'method' => 'POST',
				'body' => self::get_pluginparams($atts),
				'timeout' => 15
			);
			$response = wp_remote_post($url, $args);
			if( is_wp_error( $response ) ) {
			   return '';
			} else {
			   return $response['body'];
			}
		}

		static function get_service_area_review_combo_map($atts)
		{
			self::$add_scripts = true;

			$url = self::ApiLocation() . "nearbyserviceareareviewcombo";

			$args = array(
				'method' => 'POST',
				'body' => self::get_pluginparams($atts),
				'timeout' => 15
			);
			//print_r ($atts);
			//return print_r ($args);

			$response = wp_remote_post($url, $args);
			if( is_wp_error( $response ) ) {
				print_r ($response);
			   return '';
			} else {
				//print_r ($response);
			   return $response['body'];
			}
		}

		static function get_nationwide_combo($atts)
		{
			self::$add_scripts = true;

			$url = self::ApiLocation() . "nationwideserviceareareviewcombo";

			$args = array(
				'method' => 'POST',
				'body' => self::get_pluginparams($atts),
				'timeout' => 15
			);
			$response = wp_remote_post($url, $args);
			if( is_wp_error( $response ) ) {
			   return '';
			} else {
			   return $response['body'];
			}
		}

		static function get_testimonials($atts)
		{
			self::$add_scripts = true;
			$agent = urlencode($_SERVER['HTTP_USER_AGENT']);
			$start = isset($atts['start']) ? $atts['start'] : '';
			$count = isset($atts['count']) ? $atts['count'] : '';
			$playlist = isset($atts['playlist']) ? $atts['playlist'] : '';
			$showTranscription = isset($atts['showtranscription']) ? $atts['showtranscription'] : '';

			$token = '';
			if(isset($atts['apikey']) ) {
				$token = trim($atts['apikey']);
			} else {
				$options = get_option('nearbynow_options');
				$apitoken = $options['text_string'];
				$token = trim($apitoken);
			}

			$url = self::ApiLocation() . "testimonials?storefronttoken=$token&start=$start&count=$count&playlist=$playlist&showtranscription=$showTranscription&agent=$agent";
			$response = wp_remote_get($url, array( 'timeout' => 15));
			if( is_wp_error( $response ) ) {
			   return '';
			} else {
			   return $response['body'];
			}
		}

		static function get_photogallery($atts)
		{
			self::$add_scripts = true;
			$agent = urlencode($_SERVER['HTTP_USER_AGENT']);
			$start = isset($atts['start']) ? $atts['start'] : '';
			$count = isset($atts['count']) ? $atts['count'] : '';
			$tags = isset($atts['tags']) ? $atts['tags'] : '';
			$labels = isset($atts['labels']) ? $atts['labels'] : '';

			$token = '';
			if(isset($atts['apikey']) ) {
				$token = trim($atts['apikey']);
			} else {
				$options = get_option('nearbynow_options');
				$apitoken = $options['text_string'];
				$token = trim($apitoken);
			}

			$url = self::ApiLocation() . "photogallery?storefronttoken=$token&start=$start&count=$count&agent=$agent&tags=$tags&labels=$labels";
			$response = wp_remote_get($url, array( 'timeout' => 15));
			if( is_wp_error( $response ) ) {
			   return '';
			} else {
			   return $response['body'];
			}
		}

		static function get_faq($atts)
		{
			$agent = urlencode($_SERVER['HTTP_USER_AGENT']);
			$topic = isset($atts['topic']) ? trim($atts['topic']) : '';
			$count = isset($atts['count']) ? trim($atts['count']) : '';

			$token = '';
			if(isset($atts['apikey']) ) {
				$token = trim($atts['apikey']);
			} else {
				$options = get_option('nearbynow_options');
				$apitoken = $options['text_string'];
				$token = trim($apitoken);
			}

			$url = self::ApiLocation() . "faq?storefronttoken=$token&topic=$topic&count=$count&agent=$agent";
			$response = wp_remote_get($url, array( 'timeout' => 15));
			if( is_wp_error( $response ) ) {
			   return 'Error';
			} else {
			   return $response['body'];
			}
		}

		static function register_scripts()
		{
			$options = get_option('nearbynow_options');
			wp_register_style( 'nearbynow_css', 'https://d2gwjd5chbpgug.cloudfront.net/v4.2/css/nnplugin.min.css' );
			wp_register_script( 'nearbynow_heatmap', 'https://d2gwjd5chbpgug.cloudfront.net/v3/scripts/heatmap.min.js', null, null, true);
		}

		static function render_scripts()
		{
			if ( ! self::$add_scripts )
				return;

			wp_print_styles('nearbynow_css');
			wp_print_scripts('nearbynow_heatmap');
		}

		static function ApiLocation()
		{
			return "https://api.sidebox.com/plugin/";
		}

		static function get_comboparams($atts)
		{
			$params = array();

			// Server Variables
			$agent = urlencode($_SERVER['HTTP_USER_AGENT']);
			$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			$hostUrl = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			// Plugin options
			$token = ''; 
			if(isset($atts['apikey']) ) {
				$token = trim($atts['apikey']); //overrides apikey from nearbynow_options
			} else {
				$options = get_option('nearbynow_options');
				$apitoken = $options['text_string'];
				$token = trim($apitoken);
			}
			if (!isset($atts['theme'])) {
				$params['theme'] = 'masonry';
			}
			// Dynamic loading of options
			$params['agent'] = $agent;
			$params['referrer'] = $referrer;
			$params['hosturl'] = $hostUrl;
			$params['storefronttoken'] = $token;

			foreach ($atts as $key => $value) {
				$params += [$key => $value];
			}
			return $params;
		}

		static function get_pluginparams($atts)
		{
			// Server Variables
			$agent = urlencode($_SERVER['HTTP_USER_AGENT']);
			$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			$hostUrl = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			// Plugin options
			$token = '';
			if(isset($atts['apikey']) ) {
				$token = trim($atts['apikey']);
			} else {
				$options = get_option('nearbynow_options');
				$apitoken = $options['text_string'];
				$token = trim($apitoken);
			}

			// Common Parameters - Appearance
			$showMap = isset($atts['showmap']) ? $atts['showmap'] : '';
			$includeGoogleReviews = isset($atts['includeGoogleReviews']) ? $atts['includeGoogleReviews'] : '';
			$googleLocation_id = isset($atts['googleLocation_id']) ? $atts['googleLocation_id'] : '';
			$smm = "yes";
			$sp = "yes";
			if (isset($atts['showminimap']))
				$smm = trim($atts['showminimap']);
			if (isset($atts['showphotos']))
				$sp = trim($atts['showphotos']);

			$zoom = isset($atts['zoomlevel']) ? $atts['zoomlevel'] : '';
			$mapScrollWheel = isset($atts['mapscrollwheel']) ? $atts['mapscrollwheel'] : '';
			$serviceAreaName = null;
			if (isset($atts['serviceareaname'])) {
				$serviceAreaName = trim($atts['serviceareaname']);
			}

			// Common Parameters - Filtering
			$state = isset($atts['state']) ? $atts['state'] : '';
			$city = isset($atts['city']) ? $atts['city'] : '';
			$radius = isset($atts['radius']) ? $atts['radius'] : '';
			$showFavorites = isset($atts['showfavorites']) ? $atts['showfavorites'] : '';
			$techEmail = null;
			if (isset($atts['techemail'])) {
				$techEmail = trim($atts['techemail']);
			}
			$tags = null;
			if (isset($atts['tags'])) {
				$tags = trim($atts['tags']);
			}
			$labels = null;
			if (isset($atts['labels'])) {
				$labels = trim($atts['labels']);
			}

			// servicearea and recentreviews
			$start = isset($atts['start']) ? $atts['start'] : '';
			$count = isset($atts['count']) ? $atts['count'] : '';

			// serviceareareviewcombo
			$reviewStart = isset($atts['reviewstart']) ? $atts['reviewstart'] : '';
			$checkinStart = isset($atts['checkinstart']) ? $atts['checkinstart'] : '';
			$reviewCount = isset($atts['reviewcount']) ? $atts['reviewcount'] : '';
			$checkinCount = isset($atts['checkincount']) ? $atts['checkincount'] : '';
			$reviewCityUrl = null;
			if (isset($atts['reviewcityurl'])) {
				$reviewCityUrl = str_replace('\"', '', trim($atts['reviewcityurl']));
			}
			$mapSize = isset($atts['mapsize']) ? $atts['mapsize'] : '';

			// regional and nationwide
			$cluster = isset($atts['cluster']) ? $atts['cluster'] : '';
			$lat = isset($atts['lat']) ? $atts['lat'] : '';
			$long = isset($atts['long']) ? $atts['long'] : '';
			$reviewPinMax = isset($atts['$reviewpinmax']) ? $atts['$reviewpinmax'] : '';

			$includegooglereviews = "no";
			if (isset($atts['includegooglereviews'])) {
				$includegooglereviews = $atts['includegooglereviews'];
			}
			$googlelocation_id = isset($atts['googlelocation_id']) ? $atts['googlelocation_id'] : '';

			$body = array(
				'agent' => $agent,
				'referrer' => $referrer,
				'hosturl' => $hostUrl,
				'storefronttoken' => $token,
				'showmap' => $showMap,
				'showminimap' => $smm,
				'showphotos' => $sp,
				'zoomlevel' => $zoom,
				'mapscrollwheel' => $mapScrollWheel,
				'serviceareaname' => $serviceAreaName,
				'state' => $state,
				'city' => $city,
				'radius' => $radius,
				'showfavorites' => $showFavorites,
				'techemail' => $techEmail,
				'start' => $start,
				'count' => $count,
				'reviewstart' => $reviewStart,
				'checkinstart' => $checkinStart,
				'reviewcount' => $reviewCount,
				'checkincount' => $checkinCount,
				'reviewcityurl' => $reviewCityUrl,
				'mapsize' => $mapSize,
				'cluster' => $cluster,
				'lat' => $lat,
				'long' => $long,
				'reviewpinmax' => $reviewPinMax,
				'tags' => $tags,
				'labels' => $labels,
				'includegooglereviews' => $includegooglereviews,
				'googlelocation_id' => $googlelocation_id
			);

			return $body;
		}

	}

	NearbyNow_ShortCode::init();

	function nearbynow_admin()
	{
		$opt_name = array('api_token' => 'nbn_api_token');
		$hidden_field_name = 'nbn_submit_hidden';
		if(isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
			$opt_val = array('api_token' => $_POST[ $opt_name['api_token'] ]);
		}
	}

	function nearbynow_admin_actions()
	{
    	add_options_page("Nearby Now", "Nearby Now", "manage_options", "NearbyNow", "nearbynow_options_page");
	}

	add_action('admin_menu', 'nearbynow_admin_actions');
	add_action('admin_init', 'nearbynow_admin_init');

	function nearbynow_options_page()
	{
		?>

		<div>
			<form action="options.php" method="post">
				<?php settings_fields('nearbynow_options'); ?>
				<?php do_settings_sections('nearbynow'); ?>
				<input name="Submit" type="submit" value="<?php esc_attr_e('Save Settings'); ?>" />
			</form>
		</div>

		<?php
	}

	function nearbynow_admin_init()
	{
		register_setting(
			'nearbynow_options',
			'nearbynow_options',
			'nearbynow_options_validate'
		);

		add_settings_section(
			'nearbynow_main',
			'Nearby Now Settings',
			'nearbynow_section_text',
			'nearbynow'
		);

		add_settings_field(
			'nearbynow_text_string',
			'API Token',
			'nearbynow_setting_string',
			'nearbynow',
			'nearbynow_main'
		);

		// add_settings_field(
		// 	'disable_google_maps',
		// 	'Disable Google Map Services',
		// 	'nearbynow_google_maps_toggle',
		// 	'nearbynow',
		// 	'nearbynow_main'
		// );

		add_settings_field('nearbynow_open_graph_string', 'Exclude Open Graph Headers', 'nearbynow_open_graph_string', 'nearbynow', 'nearbynow_main');
	}

	function nearbynow_section_text()
	{
		echo '<p>To use the plugin, simply enter one of the plugin short-codes into any page or blog post. Check out some of the examples below to help get you started.</p><pre>[recentreviews city="Mesa" state="AZ" count="10" zoomlevel="9"]</pre><pre>[serviceareamap city="Scottsdale" state="AZ" count="10" zoomlevel="9"]</pre><pre>[serviceareareviewcombo city="Scottsdale" state="AZ" checkincount="10" reviewcount="10" zoomlevel="9"]</pre><pre>[nearbynowphotogallery count="10"]</pre><p>The API Token is required for the Nearby Now Reviews and Audio Testimonials plugin to function. If the token is missing or invalid the plugin will display an empty string. Enter your API key below and click save settings.</p>';
	}

	function nearbynow_setting_string()
	{
		$options = get_option('nearbynow_options');
		echo "<input id='nearbynow_text_string' name='nearbynow_options[text_string]' size='40' type='text' value='{$options['text_string']}' />";
	}

	function nearbynow_google_maps_toggle()
	{
		$options = get_option('nearbynow_options');
		$val = "0";

		if ($options['disable_google_maps'] == true)
			$val = "1";

		echo "<input id='disable_google_maps' name='nearbynow_options[disable_google_maps]' type='checkbox' value='1' " . checked(1, $options['disable_google_maps'], false) .  " />";
		echo "<p style='display: inline-block; margin-left: 8px'>(Check this flag if you already have a plugin that uses the Google Maps API)</p>";
	}

	function nearbynow_open_graph_string() {
		$options = get_option('nearbynow_options');
		$html = '<input type="checkbox" id="open_graph_exclude" name="nearbynow_options[open_graph_exclude]" value="1"' . checked( 1, isset($options['open_graph_exclude']), false ) . '/>';
		echo $html;
	}

	function nearbynow_options_validate($input)
	{
		return $input;
	}
?>
