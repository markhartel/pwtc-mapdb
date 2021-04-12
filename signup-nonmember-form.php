<script type="text/javascript">
    jQuery(document).ready(function($) { 

        function clear_warnmsg() {
            $('#pwtc-mapdb-nonmember-signup-div .accept_div .warnmsg').html('');
        }

        function show_warnmsg(message) {
            $('#pwtc-mapdb-nonmember-signup-div .accept_div .warnmsg').html('<div class="callout small warning">' + message + '</div>');
        }

        function clear_errmsg() {
            $('#pwtc-mapdb-nonmember-signup-div .errmsg').html('');
        }

        function show_errmsg(message) {
            $('#pwtc-mapdb-nonmember-signup-div .errmsg').html('<div class="callout small alert">' + message + '</div>');
        }

        function show_waiting() {
            $('#pwtc-mapdb-nonmember-signup-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
        }

        function set_accept_form() {
            var form = $('#pwtc-mapdb-nonmember-signup-div .accept_div form');
            var your_name = window.localStorage.getItem('<?php echo self::LOCAL_SIGNUP_NAME; ?>');
            if (!your_name) {
                your_name = '';
            }
            form.find('input[name="your_name"]').val(your_name);
            var contact_phone = window.localStorage.getItem('<?php echo self::LOCAL_EMER_PHONE; ?>');
            if (!contact_phone) {
                contact_phone = '';
            }
            form.find('input[name="contact_phone"]').val(contact_phone);
            var contact_name = window.localStorage.getItem('<?php echo self::LOCAL_EMER_NAME; ?>');
            if (!contact_name) {
                contact_name = '';	
            }	
            form.find('input[name="contact_name"]').val(contact_name);			
        }

        function check_signup_cb(response) {
            var res;
            try {
                res = JSON.parse(response);
            }
            catch (e) {
                show_errmsg(e.message);
                return;
            }
            if (res.error) {
                show_errmsg(res.error);
            }
            else {
                if (res.postid == <?php echo $postid ?>) {
                    clear_errmsg();
                    if (res.found) {
                        $('#pwtc-mapdb-nonmember-signup-div .accept_div').hide();
                        $('#pwtc-mapdb-nonmember-signup-div .cancel_div .your_name').html(res.signup_name);
                        $('#pwtc-mapdb-nonmember-signup-div .cancel_div').show();
                    }
                    else {
                        $('#pwtc-mapdb-nonmember-signup-div .cancel_div').hide();
                        clear_warnmsg();
                        set_accept_form();
                        $('#pwtc-mapdb-nonmember-signup-div .accept_div').show();
                    }
                }
                else {
                    show_errmsg('Ride post ID does not match post ID returned by server.');
                }
            }
        }

        function accept_signup_cb(response) {
            var res;
            try {
                res = JSON.parse(response);
            }
            catch (e) {
                show_errmsg(e.message);
                return;
            }
            if (res.error) {
                show_errmsg(res.error);
            }
            else {
                if (res.postid == <?php echo $postid ?>) {
                    //TODO: verify that sign up IDs match!
                    clear_errmsg();
                    if (res.warning) {
                        show_warnmsg(res.warning);
                        $('#pwtc-mapdb-nonmember-signup-div .accept_div').show();
                    }
                    else {
                        window.localStorage.setItem('<?php echo self::LOCAL_SIGNUP_NAME; ?>', res.signup_name);
                        window.localStorage.setItem('<?php echo self::LOCAL_EMER_NAME; ?>', res.signup_contact_name);
                        window.localStorage.setItem('<?php echo self::LOCAL_EMER_PHONE; ?>', res.signup_contact_phone);
                        $('#pwtc-mapdb-nonmember-signup-div .accept_div').hide();
                        $('#pwtc-mapdb-nonmember-signup-div .cancel_div .your_name').html(res.signup_name);
                        $('#pwtc-mapdb-nonmember-signup-div .cancel_div').show();
                    }
                }
                else {
                    show_errmsg('Ride post ID does not match post ID returned by server.');
                }
            }
        }

        function cancel_signup_cb(response) {
            var res;
            try {
                res = JSON.parse(response);
            }
            catch (e) {
                show_errmsg(e.message);
                return;
            }
            if (res.error) {
                show_errmsg(res.error);
            }
            else {
                if (res.postid == <?php echo $postid ?>) {
                    //TODO: verify that sign up IDs match!
                    clear_errmsg();
                    $('#pwtc-mapdb-nonmember-signup-div .cancel_div').hide();
                    clear_warnmsg();
                    set_accept_form();
                    $('#pwtc-mapdb-nonmember-signup-div .accept_div').show();
                }
                else {
                    show_errmsg('Ride post ID does not match post ID returned by server.');
                }
            }
        }

        $('#pwtc-mapdb-nonmember-signup-div .accept_div form').on('submit', function(evt) {
            evt.preventDefault();
            var accept_terms = 'no';
            $(this).find("select[name='accept_terms'] option:selected").each(function() {
                accept_terms = $(this).val();
            });
            var your_name = $(this).find('input[name="your_name"]').val().trim();
            if (accept_terms == 'no') {
                show_warnmsg('You must accept the Club&#39;s <a href="/terms-and-conditions" target="_blank">terms and conditions</a> to sign up for rides.');
            }
            else if (your_name) {
                clear_warnmsg();
                var signup_id = window.localStorage.getItem('<?php echo self::LOCAL_SIGNUP_ID; ?>');
                var contact_phone = $(this).find('input[name="contact_phone"]').val().trim();
                var contact_name = $(this).find('input[name="contact_name"]').val().trim();
                var action = "<?php echo admin_url('admin-ajax.php'); ?>";
                var data = {
                    'action': 'pwtc_mapdb_accept_nonmember_signup',
                    'nonce': '<?php echo wp_create_nonce('pwtc_mapdb_accept_nonmember_signup'); ?>',
                    'postid': '<?php echo $postid ?>',
                    'signup_id': signup_id,
                    'signup_name': your_name,
                    'signup_contact_phone': contact_phone,
                    'signup_contact_name': contact_name,
                    'signup_limit': <?php echo $ride_signup_limit; ?>
                };
                $.post(action, data, accept_signup_cb);
                $('#pwtc-mapdb-nonmember-signup-div .accept_div').hide();
                show_waiting();
            }
            else {
                show_warnmsg('Your name must be specified.')
            }
        });

        $('#pwtc-mapdb-nonmember-signup-div .cancel_div form').on('submit', function(evt) {
            evt.preventDefault();
            var signup_id = window.localStorage.getItem('<?php echo self::LOCAL_SIGNUP_ID; ?>');
            var action = "<?php echo admin_url('admin-ajax.php'); ?>";
            var data = {
                'action': 'pwtc_mapdb_cancel_nonmember_signup',
                'nonce': '<?php echo wp_create_nonce('pwtc_mapdb_cancel_nonmember_signup'); ?>',
                'postid': '<?php echo $postid ?>',
                'signup_id': signup_id
            };
            $.post(action, data, cancel_signup_cb);
            $('#pwtc-mapdb-nonmember-signup-div .cancel_div').hide();
            show_waiting();
        });

        if (window.localStorage) {
            var signup_id = window.localStorage.getItem('<?php echo self::LOCAL_SIGNUP_ID; ?>');
            if (!signup_id) {
                signup_id = '<?php echo $timestamp ?>';
                window.localStorage.setItem('<?php echo self::LOCAL_SIGNUP_ID; ?>', signup_id);
            }

            var action = "<?php echo admin_url('admin-ajax.php'); ?>";
            var data = {
                'action': 'pwtc_mapdb_check_nonmember_signup',
                'nonce': '<?php echo wp_create_nonce('pwtc_mapdb_check_nonmember_signup'); ?>',
                'postid': '<?php echo $postid ?>',
                'signup_id': signup_id
            };
            $.post(action, data, check_signup_cb);
            show_waiting();
        }
        else {
            show_errmsg('You cannot sign up because your browser does not support local storage.');
        }
    });
</script>

<div id='pwtc-mapdb-nonmember-signup-div'>
    <div class="callout small warning"><p>ONLY non-members should use this page to sign up for rides. If you are a club member, first <a href="/wp-login.php">log in</a> before signing up for a ride.</p></div>
    <div class="errmsg"></div>
    <div class="accept_div callout" style="display: none">
        <p>To sign up for the ride "<?php echo $ride_title; ?>," please accept the Club's <a href="/terms-and-conditions" target="_blank">terms and conditions</a>, enter your name and emergency contact information and press the accept button.</p>
        <form method="POST">
            <div class="row">
                <div class="small-12 medium-6 columns">
                    <label>Accept Terms and Conditions
                        <select name="accept_terms">
                            <option value="no" selected>No</option>
                            <option value="yes">Yes</option>
                        </select>
                    </label>
                </div>
                <div class="small-12 medium-6 columns">
                    <label>Your Name
                        <input type="text" name="your_name" value=""/>
                    </label>
                </div>
                <div class="small-12 medium-6 columns">
                    <label><i class="fa fa-phone"></i> Emergency Contact Phone
                        <input type="text" name="contact_phone" value=""/>
                    </label>
                </div>
                <div class="small-12 medium-6 columns">
                    <label>Emergency Contact Name
                        <input type="text" name="contact_name" value=""/>
                    </label>
                </div>
            </div>
            <div class="warnmsg"></div>
            <div class="row column clearfix">
                <button class="dark button float-left" type="submit"><i class="fa fa-user-plus"></i> Accept Sign-up</button>
                <a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
            </div>
        </form>
    </div>
    <div class="cancel_div callout" style="display: none">
        <p>Hello <span class="your_name"></span>, you are currently signed up for the ride "<?php echo $ride_title; ?>." To cancel your sign up, please press the cancel button below.</p>
        <form method="POST">
            <div class="row column clearfix">
                <button class="dark button float-left" type="submit"><i class="fa fa-user-times"></i> Cancel Sign-up</button>
                <a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
            </div>
        </form>
    </div>
</div>
<?php 
