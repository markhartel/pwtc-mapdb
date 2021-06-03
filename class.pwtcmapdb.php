<?php

class PwtcMapdb {

	const MAP_POST_TYPE = 'ride_maps';
	const START_LOCATION_FIELD = 'start_location';
	const TERRAIN_FIELD = 'terrain';
	const LENGTH_FIELD = 'length';
	const DESCRIPTION_FIELD = 'description';
	const MAX_LENGTH_FIELD = 'max_length';
	const MAP_FIELD = 'maps';
	const MAP_TYPE_FIELD = 'type';
	const MAP_LINK_FIELD = 'link';
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
	const ROLE_ROAD_CAPTAIN = 'ride_captain';

	const POST_TYPE_RIDE = 'scheduled_rides';
	
	const ROAD_CAPTAIN_EMAIL = 'roadcaptain@portlandbicyclingclub.com';
		
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

		// Register ajax callbacks
		add_action('wp_ajax_pwtc_mapdb_lookup_ride_leaders', array('PwtcMapdb', 'lookup_ride_leaders_callback'));
		add_action('wp_ajax_pwtc_mapdb_lookup_riderid', array('PwtcMapdb', 'lookup_riderid_callback'));
		
		// Register shortcode callbacks
		add_shortcode('pwtc_mapdb_leader_contact', array('PwtcMapdb', 'shortcode_leader_contact'));
		add_shortcode('pwtc_mapdb_alert_contact', array('PwtcMapdb', 'shortcode_alert_contact'));
		add_shortcode('pwtc_mapdb_search_riders', array('PwtcMapdb', 'shortcode_search_riders'));
	}

	/******************* Action Functions ******************/
	
	public static function load_report_scripts() {
		wp_enqueue_style('pwtc_mapdb_report_css', 
			PWTC_MAPDB__PLUGIN_URL . 'reports-style-v2.css', array());
	}
	
	/******************* Shortcode Functions ******************/

	// Generates the [pwtc_mapdb_leader_contact] shortcode.
	public static function shortcode_leader_contact($atts) {
		$error = self::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}

		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>You must be logged in edit your ride leader contact information.</p></div>';
		}
		$userid = $current_user->ID;

		if (isset($_POST['use_contact_email']) and isset($_POST['contact_email']) and isset($_POST['voice_phone']) and isset($_POST['text_phone'])) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'leader-contact-form')) {
				wp_nonce_ays('');
			}
			if ($_POST['use_contact_email'] == 'yes') {
				update_field(self::USER_USE_EMAIL, true, 'user_'.$userid);
			}
			else {
				update_field(self::USER_USE_EMAIL, false, 'user_'.$userid);
			}
			update_field(self::USER_CONTACT_EMAIL, sanitize_email($_POST['contact_email']), 'user_'.$userid);
			update_field(self::USER_CELL_PHONE, pwtc_members_format_phone_number($_POST['voice_phone']), 'user_'.$userid);
			update_field(self::USER_HOME_PHONE, pwtc_members_format_phone_number($_POST['text_phone']), 'user_'.$userid);

			wp_redirect(get_permalink(), 303);
			exit;
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
	
	// Generates the [pwtc_mapdb_alert_contact] shortcode.
	public static function shortcode_alert_contact($atts) {
		$error = self::check_plugin_dependency();
		if (!empty($error)) {
			return $error;
		}

		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>You must be logged in edit your emergency contact information.</p></div>';
		}
		$userid = $current_user->ID;

		if (isset($_POST['contact_phone']) and isset($_POST['contact_name'])) {
			if (!isset($_POST['nonce_field']) or !wp_verify_nonce($_POST['nonce_field'], 'alert-contact-form')) {
				wp_nonce_ays('');
			}
			update_field(self::USER_EMER_PHONE, pwtc_members_format_phone_number($_POST['contact_phone']), 'user_'.$userid);
			update_field(self::USER_EMER_NAME, sanitize_text_field($_POST['contact_name']), 'user_'.$userid);

			wp_redirect(get_permalink(), 303);
			exit;
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
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>You must be logged in to search rider contact information.</p></div>';
		}
		
		if (isset($_POST['rider_name']) and isset($_POST['rider_id']) and isset($_POST['offset'])) {
			wp_redirect(add_query_arg(array(
				'rider' => urlencode(trim($_POST['rider_name'])),
				'riderid' => urlencode(trim($_POST['rider_id'])),
				'offset' => intval($_POST['offset'])
			), get_permalink()), 303);
			exit;
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
				$link = '<a title="Display online ride route map." href="' . $maps[0][self::MAP_LINK_FIELD] . '" target="_blank"><i class="fa fa-link"></i></a>';
			}
			else if ($maps[0][self::MAP_TYPE_FIELD] == 'file') {
				$link = '<a title="Download ride route map file." href="' . $maps[0][self::MAP_FILE_FIELD]['url'] . '" target="_blank" download><i class="fa fa-download"></i></a>';
			}
		}
		return $link;
	}

	/******* AJAX request/response callback functions *******/

	public static function lookup_ride_leaders_callback() {
		if (isset($_POST['search'])) {
			$search = trim($_POST['search']);
			$query_args = [
				'meta_key' => 'last_name',
				'orderby' => 'meta_value',
				'order' => 'ASC',
				'role' => self::ROLE_RIDE_LEADER,
				'search' => '*'.$search.'*',
				'search_columns' => array('display_name')
			];
			$user_query = new WP_User_Query($query_args);
			$members = $user_query->get_results();
			$users = array();
			foreach ( $members as $member ) {
				$item = array(
					'userid' => $member->ID,
					'first_name' => $member->first_name,
					'last_name' => $member->last_name
				);
				$users[] = $item;
			}
			$response = array(
				'users' => $users
			);
		}
		else {
			$response = array(
				'error' => 'Server arguments missing for ride leader lookup.'
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

	/******* Plugin installation and removal functions  *********/

	public static function plugin_activation() {
		self::write_log( 'PWTC MapDB plugin activated' );
		if ( version_compare( $GLOBALS['wp_version'], PWTC_MAPDB__MINIMUM_WP_VERSION, '<' ) ) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die('PWTC MapDB plugin requires Wordpress version of at least ' . PWTC_MAPDB__MINIMUM_WP_VERSION);
		}
	}

	public static function plugin_deactivation( ) {
		self::write_log( 'PWTC MapDB plugin deactivated' );
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
