<?php

function pwtc_mapdb_get_signup() {
    $disable = false;
    $disable_nonmembers = true;
    $disable_edit = false;
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
    $signup_mode = PwtcMapdb::get_signup_mode($postid);
    $members_only = PwtcMapdb::get_signup_members_only($postid);
    $signup_locked = PwtcMapdb::get_signup_locked($postid);
    $start = PwtcMapdb::get_ride_start_time($postid);

    $result['view_signup_url'] = '/ride-view-signups/?post='.$postid;
    
    if ($disable_edit) {
        $result['edit_ride_url'] = false;
        $result['copy_ride_url'] = false;
    }
    else {
        if ($start > $now) {
            $result['edit_ride_url'] = '/ride-edit-fields/?post='.$postid.'&return=yes';
        }
        else {
            $result['edit_ride_url'] = false;
        }
        if (user_can($current_user,'edit_published_rides')) {
            $result['copy_ride_url'] = '/ride-edit-fields/?post='.$postid.'&action=copy&return=yes';
        }
        else {
            $result['copy_ride_url'] = false;
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
