<?php

function pwtc_mapdb_get_signup() {
    $disable = false;
    $disable_nonmembers = false;
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
    $postid = get_the_ID();
    $signup_mode = PwtcMapdb_Signup::get_signup_mode($postid);
    $members_only = PwtcMapdb_Signup::get_signup_members_only($postid);
    $signup_locked = PwtcMapdb_Signup::get_signup_locked($postid);

    $result['view_signup_url'] = '/ride-view-signups/?post='.$postid;
    
    if ($disable_edit) {
        $result['edit_ride_url'] = false;
        $result['copy_ride_url'] = false;
    }
    else {
        $return_uri = $_SERVER['REQUEST_URI'];
        $result['edit_ride_url'] = false;
        if (user_can($current_user,'edit_rides_from_view')) {
            $result['edit_ride_url'] = esc_url(PwtcMapdb_Ride::EDIT_RIDE_URI.'?post='.$postid.'&return='.urlencode($return_uri));
        }
        $result['copy_ride_url'] = false;
    }

    if ($signup_locked) {
        $result['ride_signup_msg'] = false;
        $result['ride_signup_url'] = false;
    }
    else if ($signup_mode == 'no') {
        if ($members_only) {
            $result['ride_signup_msg'] = 'Only club members may attend this ride.';
        }
        else {
            $result['ride_signup_msg'] = false;
        }
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
                $result['ride_signup_msg'] = 'Online sign up is available for this ride. Members must first <a href="/wp-login.php">log in</a> to sign up.';
                $result['ride_signup_url'] = false;        
            }
            else {
                $result['ride_signup_url'] = '/ride-nonmember-signup/?post='.$postid;
                if ($signup_mode == 'paperless') {
                    $result['ride_signup_msg'] = 'You <em>must</em> sign up online to attend this ride. ONLY non-members should sign up here. If you are a club member, first <a href="/wp-login.php">log in</a> before signing up for this ride.';
                }
                else {
                    $result['ride_signup_msg'] = 'Online sign up is available for this ride. ONLY non-members should sign up here. If you are a club member, first <a href="/wp-login.php">log in</a> before signing up for this ride.';
                }
            }
        }
        else {
            $result['ride_signup_url'] = false;
            $result['ride_signup_msg'] = 'Only club members may attend this ride. Members must first <a href="/wp-login.php">log in</a> to sign up.';
        }
    }

    return $result;
}

function pwtc_mapdb_get_map_metadata() {
    $disable = true;
    if ($disable) {
        $result['edit_map_url'] = false;
        return $result;
    }
    $current_user = wp_get_current_user();
    $postid = get_the_ID();
    $return_uri = $_SERVER['REQUEST_URI'];
    $result['edit_map_url'] = false;
    if (user_can($current_user,'edit_rides_from_view')) {
        $result['edit_map_url'] = esc_url(PwtcMapdb_Map::EDIT_MAP_URI.'?post='.$postid.'&return='.urlencode($return_uri));
    }
    return $result;
}   

function pwtc_mapdb_get_template_metadata() {
    $disable = true;
    if ($disable) {
        $result['edit_template_url'] = false;
        return $result;
    }
    $current_user = wp_get_current_user();
    $postid = get_the_ID();
    $return_uri = $_SERVER['REQUEST_URI'];
    $result['edit_template_url'] = false;
    if (user_can($current_user,'edit_rides_from_view')) {
        $result['edit_template_url'] = esc_url(PwtcMapdb_Ride::EDIT_TEMPLATE_URI.'?post='.$postid.'&return='.urlencode($return_uri));
    }
    return $result;
}   
