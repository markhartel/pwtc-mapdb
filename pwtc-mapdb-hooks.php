<?php

function pwtc_mapdb_get_signup() {
    $disable = false;
    $disable_nonmembers = true;
    $disable_edit = true;
    if ($disable) {
        $result['view_signup_url'] = false;
        $result['edit_ride_url'] = false;
        $result['copy_ride_url'] = false;
        $result['ride_signup_msg'] = false;
        $result['ride_signup_url'] = false;
        return $result;
    }
    $current_user = wp_get_current_user();
    $now = PwtcMapdb::get_current_time();
    $postid = get_the_ID();
    $signup_mode = PwtcMapdb_Signup::get_signup_mode($postid);
    $members_only = PwtcMapdb_Signup::get_signup_members_only($postid);
    $signup_locked = PwtcMapdb_Signup::get_signup_locked($postid);
    $start = PwtcMapdb::get_ride_start_time($postid);

    $result['view_signup_url'] = '/ride-view-signups/?post='.$postid;
    
    if ($disable_edit) {
        $result['edit_ride_url'] = false;
        $result['copy_ride_url'] = false;
    }
    else {
        $return_uri = urlencode($_SERVER['REQUEST_URI']);
        $result['edit_ride_url'] = false;
        if (user_can($current_user,'edit_published_rides') and $start > $now) {
            $result['edit_ride_url'] = esc_url('/ride-edit-fields/?post='.$postid.'&return='.$return_uri);
        }
        $result['copy_ride_url'] = false;
        if ($current_user->ID != 0) {
            $user_info = get_userdata($current_user->ID);
            if (user_can($current_user,'edit_published_rides') or in_array(PwtcMapdb::ROLE_RIDE_LEADER, $user_info->roles)) {
                $result['copy_ride_url'] = esc_url('/ride-edit-fields/?post='.$postid.'&action=copy&return='.$return_uri);
            }
        }
    }

    if ($signup_mode == 'no' or $signup_locked) {
        $result['ride_signup_msg'] = false;
        $result['ride_signup_url'] = false;
    }
    else {
        //$instruction_link = 'For more information on how to sign up online, click <a href="/online-signup-instructions-2" target="_blank" rel="noopener noreferrer">here</a>.';
        $instruction_link = '';
        if ($current_user->ID != 0) {
            $result['ride_signup_url'] = '/ride-online-signup/?post='.$postid;
            if ($signup_mode == 'paperless') {
                $result['ride_signup_msg'] = 'You <em>must</em> sign up online to attend this ride. ' . $instruction_link;
            }
            else {
                $result['ride_signup_msg'] = 'Online sign up is available for this ride. ' . $instruction_link;
            }
        }
        else if (!$members_only) {
            if ($disable_nonmembers) {
                $result['ride_signup_msg'] = 'Online sign up is available for this ride. Members must first log in <a href="/wp-login.php">here</a> to sign up.';
                $result['ride_signup_url'] = false;        
            }
            else {
                $result['ride_signup_url'] = '/ride-nonmember-signup/?post='.$postid;
                if ($signup_mode == 'paperless') {
                    $result['ride_signup_msg'] = 'You <em>must</em> sign up online to attend this ride.';
                }
                else {
                    $result['ride_signup_msg'] = 'Online sign up is available for this ride.';
                }
            }
        }
        else {
            $result['ride_signup_url'] = false;
            $result['ride_signup_msg'] = 'Only club members may attend this ride. Members must first log in <a href="/wp-login.php">here</a> to sign up.';
        }
    }

    return $result;
}