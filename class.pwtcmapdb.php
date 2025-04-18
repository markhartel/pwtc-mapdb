<?php

class PwtcMapdb {

	const MAP_POST_TYPE = 'ride_maps';
	const START_LOCATION_FIELD = 'start_location';
	const TERRAIN_FIELD_KEY = 'field_57bb6243726b8';
	const TERRAIN_FIELD = 'terrain';
	const LENGTH_FIELD_KEY = 'field_57bb613bff50c';
	const LENGTH_FIELD = 'length';
	const DESCRIPTION_FIELD_KEY = 'field_58050980e4d2e';
	const DESCRIPTION_FIELD = 'description';
	const MAX_LENGTH_FIELD_KEY = 'field_57bb61c9ff50d';
	const MAX_LENGTH_FIELD = 'max_length';
	const MAP_FIELD_KEY = 'field_57bb66366797b';
	const MAP_FIELD = 'maps';
	const MAP_TYPE_FIELD_KEY = 'field_57bb665f6797c';
	const MAP_TYPE_FIELD = 'type';
	const MAP_LINK_FIELD_KEY = 'field_57bb667e6797d';
	const MAP_LINK_FIELD = 'link';
	const MAP_FILE_FIELD_KEY = 'field_57bb668c6797e';
	const MAP_FILE_FIELD = 'file';
	const MAP_TYPE_QUERY = 'maps_0_type';
	const COPY_ANCHOR_LABEL = '<i class="fa fa-clipboard"></i>';
	//const FILE_ANCHOR_LABEL = '<i class="fa fa-download"></i>';
	//const LINK_ANCHOR_LABEL = '<i class="fa fa-link"></i>';
	const EDIT_ANCHOR_LABEL = '<i class="fa fa-pencil-square"></i>';
	const EDIT_CAPABILITY = 'edit_others_rides';

	const RIDE_CANCELED = 'is_canceled';
	const RIDE_CANCELED_KEY = 'field_canceled';
	const RIDE_LEADERS = 'ride_leaders';
	const RIDE_LEADERS_KEY = 'field_57bc9992f40cc';
	const RIDE_DATE = 'date';
	const RIDE_DATE_KEY = 'field_57bc992c2f7f5';
	const RIDE_ATTACH_MAP = 'attach_map';
	const RIDE_ATTACH_MAP_KEY = 'field_582aedf005158';
	const RIDE_MAPS = 'maps';
	const RIDE_MAPS_KEY = 'field_582aee3e19e75';
	const RIDE_TYPE = 'type';
	const RIDE_TYPE_KEY = 'field_57bc95890afc7';
	const RIDE_PACE = 'pace';
	const RIDE_PACE_KEY = 'field_57bc95bf0afc8';
	const RIDE_TERRAIN = 'terrain';
	const RIDE_TERRAIN_KEY = 'field_57bc97180afca';
	const RIDE_LENGTH = 'length';
	const RIDE_LENGTH_KEY = 'field_57bc978e0afcb';
	const RIDE_MAX_LENGTH = 'max_length';
	const RIDE_MAX_LENGTH_KEY = 'field_57bc97a40afcc';
	const RIDE_START_LOCATION = 'start_location';
	const RIDE_START_LOCATION_KEY = 'field_57bc95fb0afc9';
	const RIDE_START_LOC_COMMENT = 'start_location_comment';
	const RIDE_START_LOC_COMMENT_KEY = 'field_start_location_comment';
	const RIDE_DESCRIPTION = 'description';
	const RIDE_DESCRIPTION_KEY = 'field_57bc9553246a2';
	const RIDE_SIGNUP_LOCKED = '_signup_locked';
	const RIDE_SIGNUP_USERID = '_signup_user_id';
	const RIDE_SIGNUP_NONMEMBER = '_signup_nonmember_id';
	const RIDE_SIGNUP_MODE = '_signup_mode';
	const RIDE_SIGNUP_CUTOFF = '_signup_cutoff';
	const RIDE_SIGNUP_LIMIT = '_signup_limit';
	const RIDE_SIGNUP_MEMBERS_ONLY = '_signup_members_only';
	
	const TEMPLATE_LEADERS_KEY = 'field_57bcbeb986cb6';
	const TEMPLATE_ATTACH_MAP_KEY = 'field_57d1f61d65188';
	const TEMPLATE_MAPS_KEY = 'field_57bcc10b7286c';
	const TEMPLATE_TYPE_KEY = 'field_57bcbefd86cb7';
	const TEMPLATE_PACE_KEY = 'field_57bcbf4686cb8';
	const TEMPLATE_TERRAIN_KEY = 'field_57d1f901c850f';
	const TEMPLATE_LENGTH_KEY = 'field_57d1f948c8510';
	const TEMPLATE_MAX_LENGTH_KEY = 'field_57d1f96dc8511';
	const TEMPLATE_START_LOCATION_KEY = 'field_57d1fd23c8517';
	const TEMPLATE_START_LOC_COMMENT_KEY = 'field_start_location_comment2';
	const TEMPLATE_DESCRIPTION_KEY = 'field_57bcbf7086cb9';

	const USER_EMER_PHONE = 'emergency_contact_phone';
	const USER_EMER_NAME = 'emergency_contact_name';
	const USER_USE_EMAIL = 'use_contact_email';
	const USER_CONTACT_EMAIL = 'contact_email';
	const USER_CELL_PHONE = 'cell_phone';
	const USER_HOME_PHONE = 'home_phone';
	const USER_RIDER_ID = 'rider_id';
	const USER_RELEASE_ACCEPTED = 'release_accepted';

	const ROLE_CURRENT_MEMBER = 'current_member';
	const ROLE_EXPIRED_MEMBER = 'expired_member';
	const ROLE_RIDE_LEADER = 'ride_leader';
	//const ROLE_ROAD_CAPTAIN = 'ride_captain';
	const ROLE_ROAD_CAPTAIN = 'road_captain';
	const ROLE_STATISTICIAN = 'statistician';

	const POST_TYPE_RIDE = 'scheduled_rides';
	const POST_TYPE_TEMPLATE = 'ride_template';
	
	const ROAD_CAPTAIN_EMAIL = 'roadcaptain@portlandbicyclingclub.com';
	
	const ROAD_CAPTAIN_CAPS = [
        	'delete_others_rides',
        	'delete_private_rides',
        	'delete_published_rides',
        	'delete_rides',
        	'edit_others_rides',
        	'edit_private_rides',
        	'edit_published_rides',
        	'edit_rides',
        	'publish_rides',
        	'read_private_rides',
		'edit_rides_from_view',
    	];
		
    	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	// Initializes plugin WordPress hooks.
	private static function init_hooks() {
		self::$initiated = true;

		// Register action callbacks
		add_action('wp_enqueue_scripts', array('PwtcMapdb', 'load_report_scripts') );
		//add_action('woocommerce_account_dashboard', array('PwtcMapdb', 'add_mileage_stats_callback'));

		// Register ajax callbacks
		add_action('wp_ajax_pwtc_mapdb_lookup_ride_leaders', array('PwtcMapdb', 'lookup_ride_leaders_callback'));
		add_action('wp_ajax_pwtc_mapdb_lookup_current_members', array('PwtcMapdb', 'lookup_current_members_callback'));
		add_action('wp_ajax_pwtc_mapdb_lookup_riderid', array('PwtcMapdb', 'lookup_riderid_callback'));
		add_action('wp_ajax_pwtc_mapdb_lookup_schedule_dates', array('PwtcMapdb', 'lookup_schedule_dates_callback'));
		
		// Register shortcode callbacks
		add_shortcode('pwtc_mapdb_logged_in_content', array('PwtcMapdb', 'shortcode_logged_in_content'));
		add_shortcode('pwtc_mapdb_not_logged_in_content', array('PwtcMapdb', 'shortcode_not_logged_in_content'));
		add_shortcode('pwtc_mapdb_role_content', array('PwtcMapdb', 'shortcode_role_content'));
		add_shortcode('pwtc_mapdb_leader_contact', array('PwtcMapdb', 'shortcode_leader_contact'));
		add_shortcode('pwtc_mapdb_alert_contact', array('PwtcMapdb', 'shortcode_alert_contact'));
		add_shortcode('pwtc_mapdb_search_riders', array('PwtcMapdb', 'shortcode_search_riders'));
	}

	/******************* Action Functions ******************/
	
	public static function load_report_scripts() {
		wp_enqueue_style('pwtc_mapdb_report_css', 
			PWTC_MAPDB__PLUGIN_URL . 'reports-style-v2.css', array());
	}
	
	public static function add_mileage_stats_callback() {
		echo '<p>';
		echo do_shortcode('[pwtc_rider_report]');
		echo '</p>';
		echo '<p>Emergency contact information is used to contact a spouse or family member should you suffer an accident or other health related issue on a club ride. <a href="/rider-emergency-contact">Review and edit your emergency contact information.</a></p>';
		echo '<p>Statistics are maintained on the club rides that members attend. <a href="/your-ytd-rides">View the club rides that you have ridden so far this year.</a></p>';
		echo '<p>Online sign up is available for club rides at the discretion of the ride leader. <a href="/your-ride-signups">View the upcoming rides for which you are currently signed up.</a></p>';
	}
	
	/******************* Shortcode Functions ******************/

	// Generates the [pwtc_mapdb_logged_in_content] shortcode.
	public static function shortcode_logged_in_content($atts, $content) {
		$current_user = wp_get_current_user();
		if (0 == $current_user->ID) {
			return '';
		}
		return do_shortcode($content);
	}

	// Generates the [pwtc_mapdb_not_logged_in_content] shortcode.
	public static function shortcode_not_logged_in_content($atts, $content) {
		$current_user = wp_get_current_user();
		if (0 == $current_user->ID) {
			return do_shortcode($content);
		}
		return '';
	}
	
	// Generates the [pwtc_mapdb_role_content] shortcode.
	public static function shortcode_role_content($atts, $content) {
		$a = shortcode_atts(array('role' => '', 'not_role' => ''), $atts);
		$current_user = wp_get_current_user();
		if (0 == $current_user->ID) {
			if (!empty($a['not_role'])) {
				return do_shortcode($content);
			}
			else {
				return '';
			}
		}
		$user_info = get_userdata($current_user->ID);

		if (!empty($a['role']) and in_array($a['role'], $user_info->roles)) {
			return do_shortcode($content);
		}
		else if (!empty($a['not_role']) and !in_array($a['not_role'], $user_info->roles)) {
			return do_shortcode($content);
		}
		return '';
	}
	
	// Generates the [pwtc_mapdb_leader_contact] shortcode.
	public static function shortcode_leader_contact($atts) {
		$error = self::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}

		$current_user = wp_get_current_user();
		$userid = $current_user->ID;

		if (isset($_POST['use_contact_email']) and isset($_POST['voice_phone']) and isset($_POST['text_phone'])) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'leader-contact-form')) {
				wp_nonce_ays('');
			}
			if (0 == $current_user->ID) {
				wp_die('Authorization failed.', 403);
			}
			if ($_POST['use_contact_email'] == 'yes') {
				update_field(self::USER_USE_EMAIL, true, 'user_'.$userid);
			}
			else {
				update_field(self::USER_USE_EMAIL, false, 'user_'.$userid);
			}
			if (isset($_POST['contact_email'])) {
				update_field(self::USER_CONTACT_EMAIL, sanitize_email($_POST['contact_email']), 'user_'.$userid);
			}
			update_field(self::USER_CELL_PHONE, pwtc_members_format_phone_number($_POST['voice_phone']), 'user_'.$userid);
			update_field(self::USER_HOME_PHONE, pwtc_members_format_phone_number($_POST['text_phone']), 'user_'.$userid);

			wp_redirect(get_permalink(), 303);
			exit;
		}
		
		if (0 == $current_user->ID) {
			return '<div class="callout small warning"><p>You must be logged in edit your ride leader contact information.</p></div>';
		}

		$user_info = get_userdata($userid);
		if (!in_array(self::ROLE_RIDE_LEADER, $user_info->roles)) {
			return '<div class="callout small warning"><p>You must be a ride leader to edit your contact information.</p></div>';
		}

		$voice_phone = pwtc_members_format_phone_number(get_field(self::USER_CELL_PHONE, 'user_'.$userid));
		$text_phone = pwtc_members_format_phone_number(get_field(self::USER_HOME_PHONE, 'user_'.$userid));
		$contact_email = get_field(self::USER_CONTACT_EMAIL, 'user_'.$userid);
		$use_contact_email = get_field(self::USER_USE_EMAIL, 'user_'.$userid);

		ob_start();
		include('leader-contact-form.php');
		return ob_get_clean();
	}

	public static function shortcode_clear_cache($atts) {
		if (isset($_POST['clearcache'])) {
			WordKeeper\System\Purge::purge_all();
			wp_redirect(get_permalink(), 303);
			exit;
		}
		if (function_exists('wp_get_scheduled_event')) {
			$chron_job = wp_get_scheduled_event('pwtc_chron_clear_cache');
			if ($chron_job === false) {
				$report = 'pwtc_chron_clear_cache scheduled event not installed!';
			}
			else {
				$report = 'hook=' . $chron_job['hook'] . ', timestamp=' . $chron_job['timestamp'] . ', schedule=' . $chron_job['schedule'];
			}
		}
		else {
			$report = 'Function wp_get_scheduled_event does not exist!';
		}
		return '<div>' . $report . '</div><form method="POST"><input class="dark button" type="submit" name="clearcache" value="Clear Cache"/></form>';
	}
	
	// Generates the [pwtc_mapdb_alert_contact] shortcode.
	public static function shortcode_alert_contact($atts) {
		$error = self::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}

		$current_user = wp_get_current_user();
		$userid = $current_user->ID;

		if (isset($_POST['contact_phone']) and isset($_POST['contact_name'])) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'alert-contact-form')) {
				wp_nonce_ays('');
			}
			if (0 == $current_user->ID) {
				wp_die('Authorization failed.', 403);
			}
			update_field(self::USER_EMER_PHONE, pwtc_members_format_phone_number($_POST['contact_phone']), 'user_'.$userid);
			update_field(self::USER_EMER_NAME, sanitize_text_field($_POST['contact_name']), 'user_'.$userid);

			wp_redirect(get_permalink(), 303);
			exit;
		}
		
		if (0 == $current_user->ID) {
			return '<div class="callout small warning"><p>You must be logged in edit your emergency contact information.</p></div>';
		}

		$contact_phone = pwtc_members_format_phone_number(get_field(self::USER_EMER_PHONE, 'user_'.$userid));
		$contact_name = get_field(self::USER_EMER_NAME, 'user_'.$userid);

		ob_start();
		include('alert-contact-form.php');
		return ob_get_clean();
	}
	
	// Generates the [pwtc_mapdb_search_riders] shortcode.
	public static function shortcode_search_riders($atts) {
		$a = shortcode_atts(array('limit' => '10'), $atts);
		$limit = intval($a['limit']);
		
		$error = self::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}

		$current_user = wp_get_current_user();
		
		if (isset($_POST['rider_name']) and isset($_POST['rider_id']) and isset($_POST['offset'])) {
			if (0 == $current_user->ID) {
				wp_die('Authorization failed.', 403);
			}
			wp_redirect(add_query_arg(array(
				'rider' => urlencode(stripslashes(trim($_POST['rider_name']))),
				'riderid' => urlencode(trim($_POST['rider_id'])),
				'offset' => intval($_POST['offset'])
			), get_permalink()), 303);
			exit;
		}
		
		if (0 == $current_user->ID) {
			return '<div class="callout small warning"><p>You must be logged in to search rider contact information.</p></div>';
		}
		
		$userid = $current_user->ID;
		$user_info = get_userdata($userid);
		if (!in_array(self::ROLE_RIDE_LEADER, $user_info->roles)) {
			return '<div class="callout small warning"><p>You must be a ride leader to search rider contact information.</p></div>';
		}
		
		if (isset($_GET['rider'])) {
			$rider_name = $_GET['rider'];
		}
		else {
			$rider_name = '';
		}
		
		if (isset($_GET['riderid'])) {
			$rider_id = $_GET['riderid'];
		}
		else {
			$rider_id = '';
		}

		if (isset($_GET['offset'])) {
			$offset = intval($_GET['offset']);
		}
		else {
			$offset = 0;
		}

		$query_args = [
			'number' => $limit,
			'offset' => $offset,
			'meta_key' => 'last_name',
			'orderby' => 'meta_value',
			'order' => 'ASC',
			'role__in' => [self::ROLE_CURRENT_MEMBER, self::ROLE_EXPIRED_MEMBER],
			'search' => '*'.$rider_name.'*',
			'search_columns' => array('display_name')
		];
		if (!empty($rider_id)) {
			$query_args['meta_query'] = [];
    			$query_args['meta_query'][] = [
        			'key'     => 'rider_id',
        			'value'   => $rider_id,
        			'compare' => '='
    			];
		}
		$user_query = new WP_User_Query($query_args);
		$riders = $user_query->get_results();

		ob_start();
		include('search-riders-form.php');
		return ob_get_clean();
	}

	/******************* Utility Functions ******************/
	
	public static function output_pagination_html($limit, $offset, $total) {
		$is_more = ($limit > 0) && ($total > ($offset + $limit));
    		$is_prev = ($limit > 0) && ($offset > 0);
		$current_page = floor($offset/$limit);
		$total_pages = ceil($total/$limit);
		ob_start();
		?>
		<div class="row column clearfix">
		<?php if ($total_pages > 7) { ?>
            		<div class="button-group float-left">
				<button title="First Page" class="dark button" type="submit" name="offset" value="<?php echo 0; ?>" <?php if (!$is_prev) { ?>disabled<?php } ?>><i class="fa fa-fast-backward" aria-hidden="true"></i></button>
                		<button title="Previous Page" class="dark button" type="submit" name="offset" value="<?php echo $offset-$limit; ?>" <?php if (!$is_prev) { ?>disabled<?php } ?>><i class="fa fa-backward" aria-hidden="true"></i></button>
                		<button title="Next Page" class="dark button" type="submit" name="offset" value="<?php echo $offset+$limit; ?>" <?php if (!$is_more) { ?>disabled<?php } ?>><i class="fa fa-forward" aria-hidden="true"></i></button>
				<button title="Last Page" class="dark button" type="submit" name="offset" value="<?php echo ($total_pages-1)*$limit; ?>" <?php if (!$is_more) { ?>disabled<?php } ?>><i class="fa fa-fast-forward" aria-hidden="true"></i></button>
			</div>
			<label class="float-right">Page <?php echo $current_page+1; ?> of <?php echo $total_pages; ?></label>
		<?php } else { ?>
			<div class="button-group float-left">
			<?php for ($i = 0; $i < $total_pages; $i++) { ?>
				<button title="Page <?php echo $i+1; ?>" class="dark button" type="submit" name="offset" value="<?php echo $i*$limit; ?>" <?php if ($i == $current_page) { ?>disabled<?php } ?>><?php echo $i+1; ?></button>
			<?php } ?>
			</div>
		<?php } ?>
		</div>
		<?php 
		return ob_get_clean();
	}
	
	public static function output_pagination_html2($limit, $offset, $total) {
		$is_more = ($limit > 0) && ($total > ($offset + $limit));
    		$is_prev = ($limit > 0) && ($offset > 0);
		$current_page = floor($offset/$limit);
		$total_pages = ceil($total/$limit);
		ob_start();
		?>
		<div class="row column clearfix">
			<div class="button-group float-left">
				<button title="Page 1" class="dark button" type="submit" name="offset" value="0" <?php if (0 == $current_page) { ?>disabled<?php } ?>>1</button>
			<?php 
			if ($current_page > 5) {
			?>
				<span class="button clear"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></span>
			<?php
				$i = $current_page - 2;
			}
			else {
				$i = 1;
			}
			if ($current_page+2 > $total_pages-2) {
				$max = $total_pages-2;
				$seperator = false;
			}
			else {
				$max = $current_page+2;
				$seperator = true;
			}
			for (; $i <= $max; $i++) { 
			?>
				<button title="Page <?php echo $i+1; ?>" class="dark button" type="submit" name="offset" value="<?php echo $i*$limit; ?>" <?php if ($i == $current_page) { ?>disabled<?php } ?>><?php echo $i+1; ?></button>
			<?php 
			} 
			if ($seperator) {
			?>
				<span class="button clear"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></span>
		    <?php
			}
			?>
				<button title="Page <?php echo $total_pages; ?>" class="dark button" type="submit" name="offset" value="<?php echo ($total_pages-1)*$limit; ?>" <?php if (($total_pages-1) == $current_page) { ?>disabled<?php } ?>><?php echo $total_pages; ?></button>
			</div>
		</div>
		<?php 
		return ob_get_clean();
	}

	public static function check_plugin_dependency() {
		if (!defined('PWTC_MILEAGE__PLUGIN_DIR')) {
			return '<div class="callout small alert"><p>Cannot render shortcode, PWTC Mileage plugin is required.</p></div>';
		}

		if (!defined('PWTC_MEMBERS__PLUGIN_DIR')) {
			return '<div class="callout small alert"><p>Cannot render shortcode, PWTC Members plugin is required.</p></div>';
		}

		return '';
	}

	public static function get_leader_userids($postid) {
		$leaders = get_field(self::RIDE_LEADERS, $postid);
		$userids = [];
		foreach ($leaders as $leader) {
			$userids[] = $leader['ID'];
		}
		return $userids;		
	}

	public static function get_ride_start_time($postid) {
		$timezone = new DateTimeZone(pwtc_get_timezone_string());
		$ride_date = DateTime::createFromFormat('Y-m-d H:i:s', get_field(self::RIDE_DATE, $postid), $timezone);
		if ($ride_date === false) {
			$ride_date = new DateTime(null, $timezone);
		}
		return $ride_date;
	}

	public static function get_current_time() {
		$timezone = new DateTimeZone(pwtc_get_timezone_string());
		$now_date = new DateTime(null, $timezone);
		return $now_date;
	}

	public static function get_current_date() {
		$timezone = new DateTimeZone(pwtc_get_timezone_string());
		$now_time = new DateTime(null, $timezone);
		$now_date = DateTime::createFromFormat('Y-m-d H:i:s', $now_time->format('Y-m-d 00:00:00'), $timezone);
		return $now_date;
	}

	public static function get_rider_id($userid) {
		$rider_id = get_field(self::USER_RIDER_ID, 'user_'.$userid);
		return $rider_id;
	}
	
	public static function fetch_ride_leaders() {
		$query_args = [
			'meta_key' => 'last_name',
			'orderby' => 'meta_value',
			'order' => 'ASC',
			'role' => self::ROLE_RIDE_LEADER
		];
		$user_query = new WP_User_Query($query_args);
		$leaders = $user_query->get_results();
		return $leaders;
	}
	
	public static function get_map_link($postid) {
		$link = '';
		$maps = get_field(self::RIDE_MAPS, $postid);
		//error_log(print_r($maps, true));
		if (count($maps) > 0) {
			if ($maps[0][self::MAP_TYPE_FIELD] == 'link') {
				$link = '<a title="Display online ride route map." href="' . $maps[0][self::MAP_LINK_FIELD] . '" target="_blank"><i class="fa fa-link"></i> GPS</a>';
			}
			else if ($maps[0][self::MAP_TYPE_FIELD] == 'file') {
				$link = '<a title="Download ride route map file." href="' . $maps[0][self::MAP_FILE_FIELD]['url'] . '" target="_blank" download><i class="fa fa-download"></i> PDF</a>';
			}
			else if ($maps[0][self::MAP_TYPE_FIELD] == 'both') {
				$link = '<a title="Download ride route map file." href="' . $maps[0][self::MAP_FILE_FIELD]['url'] . '" target="_blank" download><i class="fa fa-download"></i> PDF</a> <a title="Display online ride route map." href="' . $maps[0][self::MAP_LINK_FIELD] . '" target="_blank"><i class="fa fa-link"></i> GPS</a>';
			}
		}
		return $link;
	}
	
	public static function build_terrain_str($terrain) {
		$terrain_str = '';
		foreach ($terrain as $item) {
			$terrain_str .= strtoupper($item);
		}
		return $terrain_str;
	}

	public static function build_distance_str($length, $max_length) {
		$distance_str = '';
		if ($max_length == '') {
			$distance_str = $length . ' miles';
		}
		else {
			$distance_str = $length . '-' . $max_length . ' miles';
		}
		return $distance_str;
	}

	/******* AJAX request/response callback functions *******/

	public static function lookup_ride_leaders_callback() {
		if (isset($_POST['search'])) {
			$limit = 0;
			$offset = 0;
			$search = trim($_POST['search']);
			$query_args = [
				'meta_key' => 'last_name',
				'orderby' => 'meta_value',
				'order' => 'ASC',
				'role' => self::ROLE_RIDE_LEADER,
				'search' => '*'.$search.'*',
				'search_columns' => array('display_name'),
				'fields' => ['ID', 'display_name']
			];
			if (isset($_POST['limit'])) {
				$limit = intval($_POST['limit']);
				$query_args['number'] = $limit;
				if (isset($_POST['offset'])) {
					$offset = intval($_POST['offset']);
					$query_args['offset'] = $offset;
				}
			}
			$select = 0;
			if (isset($_POST['select'])) {
				$select = intval($_POST['select']);
			}
			$user_query = new WP_User_Query($query_args);
			$members = $user_query->get_results();
			$users = array();
			$is_more = false;
			if (count($members) > 0) {
				$total = $user_query->get_total();
				$is_more = ($limit > 0) && ($total > ($offset + $limit));
				foreach ( $members as $member ) {
					$item = array(
						'userid' => $member->ID,
						'display_name' => $member->display_name
					);
					$users[] = $item;
				}
			}
			$response = array(
				'limit' => $limit,
				'offset' => $offset,
				'users' => $users
			);
			if ($is_more) {
				$response['more'] = 1;
			}
			if ($select > 0 and $offset == 0 and count($users) == 1) {
				$response['select'] = 1;
			}
			if (isset($_POST['count'])) {
				$response['count'] = intval($_POST['count']);
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for ride leader lookup.'
			);		
		}		
		echo wp_json_encode($response);
		wp_die();
	}

	public static function lookup_current_members_callback() {
		if (isset($_POST['search'])) {
			$limit = 0;
			$offset = 0;
			$search = trim($_POST['search']);
			$query_args = [
				'meta_key' => 'last_name',
				'orderby' => 'meta_value',
				'order' => 'ASC',
				'role' => 'current_member',
				'search' => '*'.$search.'*',
				'search_columns' => array('display_name'),
				'fields' => ['ID', 'display_name']
			];
			if (isset($_POST['limit'])) {
				$limit = intval($_POST['limit']);
				$query_args['number'] = $limit;
				if (isset($_POST['offset'])) {
					$offset = intval($_POST['offset']);
					$query_args['offset'] = $offset;
				}
			}
			$select = 0;
			if (isset($_POST['select'])) {
				$select = intval($_POST['select']);
			}
			$user_query = new WP_User_Query($query_args);
			$members = $user_query->get_results();
			$users = array();
			$is_more = false;
			if (count($members) > 0) {
				$total = $user_query->get_total();
				$is_more = ($limit > 0) && ($total > ($offset + $limit));
				foreach ( $members as $member ) {
					$item = array(
						'userid' => $member->ID,
						'display_name' => $member->display_name
					);
					$users[] = $item;
				}
			}
			$response = array(
				'limit' => $limit,
				'offset' => $offset,
				'users' => $users
			);
			if ($is_more) {
				$response['more'] = 1;
			}
			if ($select > 0 and $offset == 0 and count($users) == 1) {
				$response['select'] = 1;
			}
			if (isset($_POST['count'])) {
				$response['count'] = intval($_POST['count']);
			}
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for current_member lookup.'
			);		
		}		
		echo wp_json_encode($response);
		wp_die();
	}

	public static function lookup_riderid_callback() {
		if (isset($_POST['riderid'])) {
			$riderid = trim($_POST['riderid']);
			if (function_exists('pwtc_mileage_lookup_user')) {
				$users = pwtc_mileage_lookup_user($riderid);
				if (!empty($users)) {
					$userid = $users[0]->ID;
					$info = get_userdata($userid);
					if ($info) {
						$name = $info->first_name . ' ' . $info->last_name;
					}
					else {
						$name = 'Unknown';
					}
					$response = array(
						'riderid' => $riderid,
						'userid' => $userid,
						'name' => $name
					);
				}
				else {
					$response = array(
						'error' => 'Lookup failed, rider ID ' . $riderid . ' not found.'
					);
				}
			}
			else {
				$response = array(
					'error' => 'Lookup failed, PWTC Mileage plugin not installed.'
				);
			}
		}
		else {
			$response = array(
				'error' => 'Lookup failed, server post arguments missing.'
			);		
		}		
		echo wp_json_encode($response);
		wp_die();
	}
	
	public static function lookup_schedule_dates_callback() {
		if (isset($_POST['repeat']) and isset($_POST['from_date']) and isset($_POST['to_date'])) {
			$repeat = trim($_POST['repeat']);
			$from_date = trim($_POST['from_date']);
			$to_date = trim($_POST['to_date']);
			$timezone = new DateTimeZone(pwtc_get_timezone_string());
			$start = DateTime::createFromFormat('Y-m-d H:i:s', $from_date.' 00:00:00', $timezone);
			$end = DateTime::createFromFormat('Y-m-d H:i:s', $to_date.' 00:00:00', $timezone);
			if ($start === false) {
				$response = array(
					'error' => 'Lookup failed, invalid date format for from date.'
				);
			}
			else if ($end === false) {
				$response = array(
					'error' => 'Lookup failed, invalid date format for to date.'
				);
			}
			else if ($end < $start) {
				$response = array(
					'error' => 'Lookup failed, to date is earlier than from date.'
				);
			}
			else {
				if ($repeat == 'day') {
					$interval = new DateInterval('P1D');
				}
				else {
					$interval = new DateInterval('P7D');
				}
				$dates = array();
				while ($start <= $end) {
					$date = array(
						'date' => $start->format('Y-m-d'),
						'prettydate' => $start->format('D, F jS, Y')
					);
					$dates[] = $date;
					$start->add($interval);
				}
				$response = array(
					'dates' => $dates
				);
			}
		}
		else {
			$response = array(
				'error' => 'Lookup failed, server post arguments missing.'
			);			
		}
		echo wp_json_encode($response);
		wp_die();
	}

	/******* Plugin installation and removal functions  *********/
	
	public static function add_road_captain_role() {
		$road_captain = get_role('road_captain');
    		if ($road_captain === null) {
			$road_captain = add_role('road_captain', 'Road Captain', ['read' => true]);
		}
		if ($road_captain !== null) {
			foreach (self::ROAD_CAPTAIN_CAPS as $capability) {
				$road_captain->add_cap($capability);
			}
		}
	}

	public static function remove_road_captain_role() {
		$users = get_users(array('role' => 'road_captain'));
		if (count($users) > 0) {
			$road_captain = get_role('road_captain');
			foreach (self::ROAD_CAPTAIN_CAPS as $capability) {
				$road_captain->remove_cap($capability);
			}
		}
		else {
			$road_captain = get_role('road_captain');
			if ($road_captain !== null) {
				remove_role('road_captain');
			}
		}
	}

	public static function plugin_activation() {
		self::write_log( 'PWTC MapDB plugin activated' );
		if ( version_compare( $GLOBALS['wp_version'], PWTC_MAPDB__MINIMUM_WP_VERSION, '<' ) ) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die('PWTC MapDB plugin requires Wordpress version of at least ' . PWTC_MAPDB__MINIMUM_WP_VERSION);
		}
		self::add_road_captain_role();
	}

	public static function plugin_deactivation( ) {
		self::write_log( 'PWTC MapDB plugin deactivated' );
		self::remove_road_captain_role();
	}

	public static function plugin_uninstall() {
		self::write_log( 'PWTC MapDB plugin uninstall' );	
	}

    public static function write_log ( $log )  {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }

}
