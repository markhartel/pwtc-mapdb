<style>
    #pwtc-mapdb-view-signup-div .pwtc-mapdb-rwd-table .absent td {
        text-decoration: line-through;
    }
</style>
<script type="text/javascript">
    jQuery(document).ready(function($) { 

        $.fn.setCursorPosition = function(pos) {
            this.each(function(index, elem) {
                if (elem.setSelectionRange) {
                    elem.setSelectionRange(pos, pos);
                } else if (elem.createTextRange) {
                    var range = elem.createTextRange();
                    range.collapse(true);
                    range.moveEnd('character', pos);
                    range.moveStart('character', pos);
                    range.select();
                }
            });
            return this;
        };

        function show_errmsg(message) {
            $('#pwtc-mapdb-view-signup-div .errmsg').html('<div class="callout small alert">' + message + '</div>');
        }

        function clear_errmsg() {
            $('#pwtc-mapdb-view-signup-div .errmsg').html('');
        }
        
        function show_errmsg2(message) {
            $('#pwtc-mapdb-view-signup-div .errmsg2').html('<div class="callout small alert">' + message + '</div>');
        }

        function show_errmsg2_success(message) {
            $('#pwtc-mapdb-view-signup-div .errmsg2').html('<div class="callout small success">' + message + '</div>');
        }
        
        function show_errmsg2_warning(message) {
            $('#pwtc-mapdb-view-signup-div .errmsg2').html('<div class="callout small warning">' + message + '</div>');
        }

        function show_errmsg2_wait() {
            $('#pwtc-mapdb-view-signup-div .errmsg2').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
        }

        function clear_errmsg2() {
            $('#pwtc-mapdb-view-signup-div .errmsg2').html('');
        }
        
        function show_errmsg3_wait() {
            $('#pwtc-mapdb-view-signup-div .errmsg3').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
        }
    
    <?php if ($ride_signup_mode != 'no') { ?>

        function reset_mileage_cell() {
            $('#pwtc-mapdb-view-signup-div table tbody td[mileage] input').each(function() {
                var cell = $(this).parent();
                cell.html('<span>Mileage</span>' + cell.attr('mileage'));
            });
        }

        function reset_attended_cell() {
            $('#pwtc-mapdb-view-signup-div table tbody td[attended] a').each(function() {
                $(this).remove();
            });				
        }

        function reset_waiting_icon() {
            $('#pwtc-mapdb-view-signup-div table tbody td[mileage] .waiting').each(function() {
                var cell = $(this).parent();
                cell.html('<span>Mileage</span>' + cell.attr('mileage'));
            });

            $('#pwtc-mapdb-view-signup-div table tbody td[attended] .waiting').each(function() {
                var cell = $(this).parent();
                $(this).remove();
                cell.find('i').remove();
                cell.parent().removeClass('absent');
                if (cell.attr('attended') == '0') {
                    //cell.find('span').after('<i class="fa fa-times"></i>');
                    cell.parent().addClass('absent');
                }
            });			
        }

        function change_signup_cb(response) {
            var res;
            try {
                res = JSON.parse(response);
            }
            catch (e) {
                show_errmsg(e.message);
                reset_waiting_icon();
                return;
            }
            if (res.error) {
                show_errmsg(res.error);
                reset_waiting_icon();
            }
            else {
                if (res.postid == <?php echo $postid ?>) {
                    var row = $('#pwtc-mapdb-view-signup-div table tbody tr[userid="' + res.userid + '"]');
                    var mcell = row.find('td[mileage]');
                    mcell.attr('mileage', function() {
                        return res.mileage;
                    });
                    mcell.html('<span>Mileage</span>' + res.mileage);
                    var acell = row.find('td[attended]');
                    acell.attr('attended', function() {
                        return res.attended;
                    });
                    acell.find('i').remove();
                    row.removeClass('absent');
                    if (res.attended == '0') {
                        //acell.find('span').after('<i class="fa fa-times"></i>');
                        row.addClass('absent');
                    }
                }
                else {
                    show_errmsg('Ride post ID does not match post ID returned by server.');
                    reset_waiting_icon();
                }
            }
        }
                    
        function change_nonmember_signup_cb(response) {
            var res;
            try {
                res = JSON.parse(response);
            }
            catch (e) {
                show_errmsg(e.message);
                reset_waiting_icon();
                return;
            }
            if (res.error) {
                show_errmsg(res.error);
                reset_waiting_icon();
            }
            else {
                if (res.postid == <?php echo $postid ?>) {
                    var row = $('#pwtc-mapdb-view-signup-div table tbody tr[signup_id="' + res.signup_id + '"]');
                    var acell = row.find('td[attended]');
                    acell.attr('attended', function() {
                        return res.attended;
                    });
                    acell.find('i').remove();
                    row.removeClass('absent');
                    if (res.attended == '0') {
                        //acell.find('span').after('<i class="fa fa-times"></i>');
                        row.addClass('absent');
                    }
                }
                else {
                    show_errmsg('Ride post ID does not match post ID returned by server.');
                    reset_waiting_icon();
                }
            }
        }
        
        function log_mileage_cb(response) {
            var res;
            try {
                res = JSON.parse(response);
            }
            catch (e) {
                show_errmsg2(e.message);
                return;
            }
            if (res.error) {
                show_errmsg2(res.error);
            }
            else {
                if (res.postid == <?php echo $postid ?>) {
                    var warning = false;
                    var msg = 'Mileage was logged successfully, ' + res.num_leaders + ' ride leaders were recorded and ' + res.num_riders + ' riders were recorded.';
                    if (res.expired_riders.length > 0) {
                        msg += '<br>The following riders were NOT recorded because of expired membership:';
                        res.expired_riders.forEach(function(item) {
                            msg += ' ' + item;
                        });
                        msg += '.';
                        warning = true;
                    }
                    if (res.missing_riders.length > 0) {
                        msg += '<br>The following riders were NOT recorded because of missing rider IDs:';
                        res.missing_riders.forEach(function(item) {
                            msg += ' ' + item;
                        });
                        msg += '.';
                        warning = true;
                    }
                    if (res.missing_leaders.length > 0) {
                        msg += '<br>The following ride leaders were NOT recorded because of missing rider IDs:';
                        res.missing_leaders.forEach(function(item) {
                            msg += ' ' + item;
                        });
                        msg += '.';
                        warning = true;
                    }
                    if (warning) {
                        show_errmsg2_warning(msg);
                    }
                    else {
                        show_errmsg2_success(msg);
                    }
                }
                else {
                    show_errmsg2('Ride post ID does not match post ID returned by server.');
                }
            }
        }

        function change_signup_setting(userid, oldmileage, mileage, oldattended, attended) {
            var action = "<?php echo admin_url('admin-ajax.php'); ?>";
            var data = {
                'action': 'pwtc_mapdb_edit_signup',
                'nonce': '<?php echo wp_create_nonce('pwtc_mapdb_edit_signup'); ?>',
                'postid': '<?php echo $postid ?>',
                'userid': userid,
                'oldmileage': oldmileage,
                'mileage': mileage,
                'oldattended': oldattended,
                'attended': attended
            };
            $.post(action, data, change_signup_cb);
        }
    
        function change_nonmember_signup_setting(signup_id, oldattended, attended) {
            var action = "<?php echo admin_url('admin-ajax.php'); ?>";
            var data = {
                'action': 'pwtc_mapdb_edit_nonmember_signup',
                'nonce': '<?php echo wp_create_nonce('pwtc_mapdb_edit_nonmember_signup'); ?>',
                'postid': '<?php echo $postid ?>',
                'signup_id': signup_id,
                'oldattended': oldattended,
                'attended': attended
            };
            $.post(action, data, change_nonmember_signup_cb);
        }
    
        <?php if ($set_mileage or $take_attendance) { ?>
        $('#pwtc-mapdb-view-signup-div table').on('click', function(evt) {
            reset_mileage_cell();
            clear_errmsg();
            reset_attended_cell();
        });
        <?php } ?>

        <?php if ($set_mileage) { ?>
        $('#pwtc-mapdb-view-signup-div table tbody td[mileage]').on('click', function(evt) {
            evt.stopPropagation();
            reset_mileage_cell();
            clear_errmsg();
            reset_attended_cell();
            var cell = $(this);
            if (cell.attr('mileage') != 'XXX') {
                var row = cell.parent();
                var attended = row.find('td[attended]').attr('attended');
                if (attended == '0') {
                    return;
                }
                cell.html('<span>Mileage</span><input type="number" value="' + cell.attr('mileage') + '" style="width:50%" maxlength="3" />');
                var input = cell.find('input');
                input.on('click', function(e) {
                    e.stopPropagation();
                });
                input.on('keypress', function(e) {
                        if (e.which == 13) {
                        change_signup_setting(
                            row.attr('userid'), 
                            cell.attr('mileage'), 
                            input.val(), 
                            row.find('td[attended]').attr('attended'), 
                            row.find('td[attended]').attr('attended'));
                        cell.html('<span>Mileage</span><i class="fa fa-spinner fa-pulse waiting"></i> ');
                    }
                });
                input.focus();
                //input.setCursorPosition(3);
            }
        });
        <?php } ?>

        <?php if ($take_attendance) { ?>
        $('#pwtc-mapdb-view-signup-div table tbody td[attended]').on('click', function(evt) {
            evt.stopPropagation();
            reset_mileage_cell();
            clear_errmsg();
            reset_attended_cell();
            var cell = $(this);
            var row = cell.parent();
            if (cell.attr('attended') == '1') {
                cell.append('<a><i class="fa fa-thumbs-down"></i></a>');
                var link = cell.find('a');
                link.on('click', function(e) {
                    e.stopPropagation();
                    link.remove();
                    if (row.attr('userid')) {
                        change_signup_setting(
                            row.attr('userid'), 
                            row.find('td[mileage]').attr('mileage'), 
                            row.find('td[mileage]').attr('mileage'), 
                            cell.attr('attended'), 
                            '0');
                    }
                    else {
                        change_nonmember_signup_setting(
                            row.attr('signup_id'), 
                            cell.attr('attended'), 
                            '0');
                    }						
                    cell.find('i').remove();
                    cell.find('span').after('<i class="fa fa-spinner fa-pulse waiting"></i>');
                });
            }
            else {
                cell.append('<a><i class="fa fa-thumbs-up"></i></a>');
                var link = cell.find('a');
                link.on('click', function(e) {
                    e.stopPropagation();
                    link.remove();
                    if (row.attr('userid')) {
                        change_signup_setting(
                            row.attr('userid'), 
                            row.find('td[mileage]').attr('mileage'), 
                            row.find('td[mileage]').attr('mileage'), 
                            cell.attr('attended'), 
                            '1');
                    }
                    else {
                        change_nonmember_signup_setting(
                            row.attr('signup_id'), 
                            cell.attr('attended'), 
                            '1');
                    }
                    cell.find('i').remove();
                    cell.find('span').after('<i class="fa fa-spinner fa-pulse waiting"></i>');
                });
            }
        });
        <?php } ?>
    
        $('#pwtc-mapdb-view-signup-div .log_mileage').on('click', function(evt) {
            var action = "<?php echo admin_url('admin-ajax.php'); ?>";
            var data = {
                'action': 'pwtc_mapdb_log_mileage',
                'nonce': '<?php echo wp_create_nonce('pwtc_mapdb_log_mileage'); ?>',
                'postid': '<?php echo $postid ?>'
            };
            $.post(action, data, log_mileage_cb);
            show_errmsg2_wait();
        });

        $('#pwtc-mapdb-view-signup-div .download_sheet').on('click', function(evt) {
            $('#pwtc-mapdb-view-signup-div .download-frm').submit();
        });
    
        <?php if ($paperless and !$signup_locked) { ?>
    
        $('#pwtc-mapdb-view-signup-div .show_more').on('click', function(evt) {
            $(this).hide();
            $('#pwtc-mapdb-view-signup-div .more_details').show();
        });

        $('#pwtc-mapdb-view-signup-div .show_less').on('click', function(evt) {
            $('#pwtc-mapdb-view-signup-div .more_details').hide();
            $('#pwtc-mapdb-view-signup-div .show_more').show();
        });
    
        function show_errmsg4_wait() {
            $('#pwtc-mapdb-view-signup-div .errmsg4').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
        }

        function show_errmsg4_warning(msg) {
            $('#pwtc-mapdb-view-signup-div .errmsg4').html('<div class="callout small warning">' + msg + '</div>');
        }

        function show_errmsg4_success(msg) {
            $('#pwtc-mapdb-view-signup-div .errmsg4').html('<div class="callout small success">' + msg + '</div>');
        }
    
        function show_errmsg5_wait() {
            $('#pwtc-mapdb-view-signup-div .errmsg5').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
        }

        function riderid_lookup_cb(response) {
            var res;
            try {
                res = JSON.parse(response);
            }
            catch (e) {
                show_errmsg4_warning(e.message);
                $('#pwtc-mapdb-view-signup-div .rider-signup-frm button[type="submit"]').prop('disabled',true);
                return;
            }
            if (res.error) {
                $('#pwtc-mapdb-view-signup-div .rider-signup-frm button[type="submit"]').prop('disabled',true);
                show_errmsg4_warning(res.error);
            }
            else {
                $('#pwtc-mapdb-view-signup-div .rider-signup-frm input[name="signup_userid"]').val(res.userid);
                show_errmsg4_success(res.name + ' found, press accept to sign up.');
                $('#pwtc-mapdb-view-signup-div .rider-signup-frm button[type="submit"]').prop('disabled',false);
            }
        }

        $('#pwtc-mapdb-view-signup-div .rider-signup-frm input[type="button"]').on('click', function(evt) {
            var riderid = $('#pwtc-mapdb-view-signup-div .rider-signup-frm input[name="signup_riderid"]').val().trim();
            if (riderid.length == 0) {
                show_errmsg4_warning('You must enter a Rider ID.');
                return;
            }
            var rideridrgx = /^\d{5}$/;
            if (!rideridrgx.test(riderid)) {
                show_errmsg4_warning('The Rider ID must be a 5 digit number.');
                return;
            }
            var action = "<?php echo admin_url('admin-ajax.php'); ?>";
            var data = {
                'action': 'pwtc_mapdb_lookup_riderid',
                'riderid': riderid
            };
            $.post(action, data, riderid_lookup_cb);
            show_errmsg4_wait();
        });
    
        $('#pwtc-mapdb-view-signup-div .rider-signup-frm').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                evt.preventDefault(); 
                return false; 
            } 
        });	

        $('#pwtc-mapdb-view-signup-div .rider-signup-frm input[name="signup_riderid"]').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                $('#pwtc-mapdb-view-signup-div .rider-signup-frm input[type="button"]').trigger( 'click');
            } 
        });

        $('#pwtc-mapdb-view-signup-div .rider-signup-frm').on('submit', function(evt) {
            show_errmsg4_wait();
            $('#pwtc-mapdb-view-signup-div button[type="submit"]').prop('disabled',true);
        });
    
        $('#pwtc-mapdb-view-signup-div .rider-cancel-frm').on('submit', function(evt) {
            show_errmsg5_wait();
            $('#pwtc-mapdb-view-signup-div button[type="submit"]').prop('disabled',true);
        });
    
        $("#pwtc-mapdb-view-signup-div .rider-cancel-frm select[name='cancel_userid']").change(function() {
            $(this).find('option:selected').each(function() {
                var userid = $(this).val();
                if (userid == '0') {
                    $('#pwtc-mapdb-view-signup-div .rider-cancel-frm button[type="submit"]').prop('disabled',true);
                }
                else {
                    $('#pwtc-mapdb-view-signup-div .rider-cancel-frm button[type="submit"]').prop('disabled',false);
                }
            });
        });
    
        <?php } ?>
    
    <?php } ?>
    
        $("#pwtc-mapdb-view-signup-div .signup-options-frm select[name='ride_signup_mode']").change(function() {
            $(this).find('option:selected').each(function() {
                var mode = $(this).val();
                var label = '(hours)';
                if (mode == 'paperless') {
                    label = '(hours after ride start)';
                } else if (mode == 'hardcopy') {
                    label = '(hours before ride start)';
                }
                $('#pwtc-mapdb-view-signup-div .cutoff_units').html(label);
                if (mode == 'no') {
                    $('#pwtc-mapdb-view-signup-div input[name="ride_signup_cutoff"]').prop('disabled',true);
                    $('#pwtc-mapdb-view-signup-div input[name="ride_signup_limit"]').prop('disabled',true);
                } else {
                    $('#pwtc-mapdb-view-signup-div input[name="ride_signup_cutoff"]').prop('disabled',false);
                    $('#pwtc-mapdb-view-signup-div input[name="ride_signup_limit"]').prop('disabled',false);
                }
            });
        });
    
        $('#pwtc-mapdb-view-signup-div .signup-options-frm').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                evt.preventDefault(); 
                return false; 
            } 
        });	
    
        $('#pwtc-mapdb-view-signup-div .signup-options-frm').on('submit', function(evt) {
            show_errmsg3_wait();
            $('#pwtc-mapdb-view-signup-div button[type="submit"]').prop('disabled',true);
            });

        $('#pwtc-mapdb-view-signup-div .action-btns form').on('submit', function(evt) {
            show_errmsg2_wait();
            $('#pwtc-mapdb-view-signup-div button[type="submit"]').prop('disabled',true);
            });

    });
</script>
<div id='pwtc-mapdb-view-signup-div'>
    <ul class="accordion" data-accordion data-allow-all-closed="true">
        <li class="accordion-item" data-accordion-item>
                    <a href="#" class="accordion-title">Set Sign-up Options...</a>
                    <div class="accordion-content" data-tab-content>
                <form class="signup-options-frm" method="POST" novalidate>
                    <?php wp_nonce_field('signup-view-form', 'nonce_field'); ?>
                    <div class="row">
                        <div class="small-12 medium-4 columns">
                            <label>Online Ride Sign-up
                                <select name="ride_signup_mode">
                                    <option value="no" <?php echo $ride_signup_mode == 'no' ? 'selected': ''; ?>>No Sign-up Allowed</option>
                                    <option value="hardcopy"  <?php echo $ride_signup_mode == 'hardcopy' ? 'selected': ''; ?>>Printed Hardcopy Sign-up</option>
                                    <option value="paperless"  <?php echo $ride_signup_mode == 'paperless' ? 'selected': ''; ?>>Paperless Sign-up</option>
                                </select>
                            </label>
                        </div>
                        <div class="small-12 medium-4 columns">
                            <label>Sign-up Cutoff <span class="cutoff_units"><?php echo $cutoff_units; ?></span>
                                <input type="number" name="ride_signup_cutoff" value="<?php echo $ride_signup_cutoff; ?>" <?php echo $ride_signup_mode == 'no' ? 'disabled': ''; ?>/>
                            </label>
                        </div>
                        <div class="small-12 medium-4 columns">
                            <label>Sign-up Count Limit (0 means unlimited)
                                <input type="number" name="ride_signup_limit" value="<?php echo $ride_signup_limit; ?>" <?php echo $ride_signup_mode == 'no' ? 'disabled': ''; ?>/>
                            </label>
                        </div>
                        <div class="small-12 medium-4 columns">
                            <label>Club Members Only
                                <select name="signup_members_only">
                                    <option value="no" <?php echo $signup_members_only ? '': 'selected'; ?>>No</option>
                                    <option value="yes"  <?php echo $signup_members_only ? 'selected': ''; ?>>Yes</option>
                                </select>
                            </label>
                        </div>
                    </div>
                    <div class="row column errmsg3"></div>
                    <div class="row column clearfix">
                        <button class="accent button float-left" type="submit">Submit</button>
                    </div>
                </form>
            </div>
        </li>
    <?php if ($paperless and !$signup_locked) { ?>
        <li class="accordion-item" data-accordion-item>
            <a href="#" class="accordion-title">Sign-up Rider...</a>
            <div class="accordion-content" data-tab-content>
                <form class="rider-signup-frm" method="POST" novalidate>
                    <?php wp_nonce_field('signup-view-form', 'nonce_field'); ?>
                    <input type="hidden" name="signup_userid" value="0"/>
                    <div class="help-text"><p>A rider should use their club member account to sign up for rides. However, if they don't have access to a computer, the ride leader can do it for them here. Enter their rider ID and press lookup. After a rider matching that ID is found, you can enter their mileage and press the accept sign-up button.</p></div>
                    <div class="row">
                        <div class="small-12 medium-4 columns">
                            <label>Rider ID
                                <div class="input-group">
                                    <input class="input-group-field" type="text" name="signup_riderid" value="">
                                    <div class="input-group-button">
                                        <input class="dark button" type="button" value="Lookup"/>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="small-12 medium-4 columns">
                            <label>Rider Mileage
                                <input type="number" name="signup_rider_mileage" value=""/>
                            </label>
                        </div>
                    </div>
                    <div class="row column errmsg4"></div>
                    <div class="row column clearfix">
                        <button class="accent button float-left" type="submit" disabled><i class="fa fa-user-plus"></i> Accept Sign-up</button>
                    </div>
                </form>
            </div>
        </li>
        <li class="accordion-item" data-accordion-item>
            <a href="#" class="accordion-title">Cancel Sign-up...</a>
            <div class="accordion-content" data-tab-content>
                <form class="rider-cancel-frm" method="POST" novalidate>
                    <?php wp_nonce_field('signup-view-form', 'nonce_field'); ?>
                    <div class="help-text"><p>A rider should use their club member account to cancel their own ride sign up. However, if they don't have access to a computer, the ride leader can do it for them here. Select the rider for which to cancel sign up and press the cancel sign-up button.</p></div>
                    <div class="row">
                        <div class="small-12 medium-4 columns">
                            <select name="cancel_userid">
                                <option value="0" selected>-- Select a Rider --</option>
                            <?php foreach ($signup_list as $item) { 
                                $arr = json_decode($item, true);
                                $userid = $arr['userid'];
                                $user_info = get_userdata($userid);
                                if ($user_info) {
                                    $name = $user_info->first_name.' '.$user_info->last_name;
                                }
                                else {
                                    $name = 'Unknown';
                                }
                            ?>
                                <option value="userid:<?php echo $userid; ?>"><?php echo $name; ?></option>
                            <?php } ?>
                            <?php foreach ($nonmember_signup_list as $item) { 
                                $arr = json_decode($item, true);
                                $signup_id = $arr['signup_id'];
                                $name = $arr['name'];
                            ?>
                                <option value="signupid:<?php echo $signup_id; ?>"><?php echo $name; ?> (nonmember)</option>
                            <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="row column errmsg5"></div>
                    <div class="row column clearfix">
                        <button class="accent button float-left" type="submit" disabled><i class="fa fa-user-times"></i> Cancel Sign-up</button>
                    </div>
                </form>
            </div>
        </li>
    <?php } ?>
    </ul>		
    <?php if ($ride_signup_mode == 'no') { ?>
        <div class="callout small"><p>Online sign up is not enabled for ride "<?php echo $ride_title; ?>." <?php echo $return_to_ride; ?></p></div>
    <?php } else { ?>
    <?php if (count($signup_list) > 0 or count($nonmember_signup_list) > 0) { ?>
        <p>The following riders are currently signed up for the ride "<?php echo $ride_title; ?>."
        <?php if ($paperless and !$signup_locked and ($set_mileage or $take_attendance)) { ?>
            <a class="show_more">more&gt;</a><span class="more_details" style="display: none">
            <?php if ($take_attendance) { ?>
            <strong>To mark a rider as absent:</strong> (1) press the rider&#39;s name, (2) press the <i class="fa fa-thumbs-down"></i> icon after it appears and (3) a strike through the name will mark the rider as absent. To reverse this, simply press the rider&#39;s name again and press the <i class="fa fa-thumbs-up"></i> icon after it appears.
            <?php } ?>
            <?php if ($set_mileage) { ?>
            <strong>To modify a rider&#39;s mileage:</strong> (1) press the rider&#39;s mileage, (2) type the new mileage into the entry field after it appears and (3) press the enter key to accept the change. 
            <?php } ?>
            <a class="show_less">&lt;less</a><span>
        <?php } ?>
        </p> 
        <div class="errmsg"></div>
        <table class="pwtc-mapdb-rwd-table"><thead><tr><th>Name</th><th>Rider ID</th><?php if ($paperless) { ?><th>Mileage</th><?php } ?><th>Emergency Contact</th></tr></thead><tbody>
        <?php foreach($signup_list as $item) { 
            $arr = json_decode($item, true);
            $userid = $arr['userid'];
            $user_info = get_userdata($userid);
            if ($user_info) {
                $name = $user_info->first_name . ' ' . $user_info->last_name;
            }
            else {
                $name = 'Unknown';
            }
            if ($paperless) {
                $mileage = $arr['mileage'];
                $mileage_label = $mileage;
                $attended = $arr['attended'];
                /*
                if (in_array(PwtcMapdb::ROLE_EXPIRED_MEMBER, (array) $user_info->roles)) {
                    $mileage = 'XXX';
                    $mileage_label = 'expired';
                }
                */
            }
            else {
                $attended = true;
            }
            $rider_id = PwtcMapdb::get_rider_id($userid);
            $contact = self::get_emergency_contact($userid, true);
        ?>
            <tr userid="<?php echo $userid; ?>" <?php if (!$attended) { ?>class="absent"<?php } ?>>
            <td attended="<?php echo $attended ? '1':'0'; ?>"><span>Name</span> <?php echo $name; ?> </td>
            <td><span>Rider ID</span><?php echo $rider_id; ?></td>
            <?php if ($paperless) { ?>
            <td mileage="<?php echo $mileage; ?>"><span>Mileage</span><?php echo $mileage_label; ?></td>
            <?php } ?>
            <td><span>Emergency Contact</span><?php echo $contact; ?></td>
            </tr>
        <?php } ?>
        <?php foreach($nonmember_signup_list as $item) { 
            $arr = json_decode($item, true);
            $signup_id = $arr['signup_id'];
            $name = $arr['name'];
            $contact_phone = $arr['contact_phone'];
            $contact_name = $arr['contact_name'];
            $contact = self::get_nonmember_emergency_contact($contact_phone, $contact_name, true);
            if ($paperless) {
                $mileage = 'XXX';
                $mileage_label = 'n/a';
                $attended = $arr['attended'];
            }
            else {
                $attended = true;
            }
        ?>
            <tr signup_id="<?php echo $signup_id; ?>" <?php if (!$attended) { ?>class="absent"<?php } ?>>
            <td attended="<?php echo $attended ? '1':'0'; ?>"><span>Name</span> <?php echo $name; ?> </td>
            <td><span>Rider ID</span>n/a</td>
            <?php if ($paperless) { ?>
            <td mileage="<?php echo $mileage; ?>"><span>Mileage</span><?php echo $mileage_label; ?></td>
            <?php } ?>
            <td><span>Emergency Contact</span><?php echo $contact; ?></td>
            </tr>
        <?php } ?>
        </tbody></table>
    <?php } else { ?>
        <div class="callout small"><p>There are currently no riders signed up for the ride "<?php echo $ride_title; ?>."</p></div>
    <?php } ?>
    <?php if ($signup_locked) { ?>
        <?php if ($mileage_logged) { ?>
            <div class="callout small success"><p>The rider mileage has been logged to the mileage database, contact the club statistician to make any changes.</p></div>
        <?php } else if ($paperless) { ?>
            <div class="callout small success"><p>Online sign up is closed, you may now log the rider mileage to the mileage database.</p></div>
        <?php } else { ?>
            <div class="callout small success"><p>Online sign up is closed, you may now download the ride sign-in sheet and print it.</p></div>
        <?php } ?>
    <?php } else { ?>
        <?php if ($now_date < $cutoff_date) { ?>
            <div class="callout small warning"><p>Online sign up is allowed until <?php echo $cutoff_date_str; ?>, you cannot close it until then.</p></div>
        <?php } else if ($paperless) { ?>
            <div class="callout small success"><p>The period for online sign up has expired, but you may continue to modify the mileages. Close the sign up after the rider mileages are finalized.</p></div>
        <?php } else { ?>
            <div class="callout small success"><p>The period for online sign up has expired, you may now close it.</p></div>
        <?php } ?>
    <?php } ?>
    <div class="errmsg2"></div>
    <div class="row column clearfix action-btns">
        <form method="POST">
            <?php wp_nonce_field('signup-view-form', 'nonce_field'); ?>
    <?php if ($signup_locked) { ?>
            <div class="button-group float-left">
        <?php if ($paperless) { ?>
            <a class="log_mileage dark button"><i class="fa fa-bicycle"></i> Log Mileage</a>
        <?php } else { ?>
            <a class="download_sheet dark button"><i class="fa fa-download"></i> Sign-in Sheet</a>
        <?php } ?>
            <input type="hidden" name="lock_signup" value="no"/>
            <button class="dark button" type="submit"><i class="fa fa-unlock"></i> Reopen Sign-up</button>
            </div>
    <?php } else { ?>
            <input type="hidden" name="lock_signup" value="yes"/>
            <button class="dark button float-left" type="submit" <?php echo $now_date < $cutoff_date ? 'disabled': ''; ?>><i class="fa fa-lock"></i> Close Sign-up</button>
    <?php } ?>
            <a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
        </form>
    </div>
    <form class="download-frm" method="POST">
        <input type="hidden" name="pwtc_mapdb_download_signup" value="yes"/>
        <input type="hidden" name="ride_id" value="<?php echo $postid; ?>"/>
        <input type="hidden" name="unused_rows" value="<?php echo $unused_rows; ?>"/>
    </form>
    <?php } ?>
</div>
<?php 
