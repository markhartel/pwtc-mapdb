<script type="text/javascript">
    jQuery(document).ready(function($) { 

        function show_waiting() {
            $('#pwtc-mapdb-rider-signup-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
        }

        <?php if ($accept_signup) { ?>
        $('#pwtc-mapdb-rider-signup-div form').on('submit', function(evt) {
            <?php if ($set_mileage) { ?>
            var mileage = $('#pwtc-mapdb-rider-signup-div form input[name="mileage"]').val().trim();
            if (mileage.length > 0) {
                mileage = Math.abs(parseInt(mileage, 10));
                if (mileage > <?php echo $ride_mileage; ?>) {
                    $('#pwtc-mapdb-rider-signup-div .errmsg').html('<div class="callout small warning"><p>You cannot log more than <?php echo $ride_mileage; ?> miles for this ride.</p></div>');
                    evt.preventDefault();
                    return;
                }
            }
            <?php } ?>
            $(this).find("select[name='accept_terms'] option:selected").each(function() {
                var accept_terms = $(this).val();
                if (accept_terms == 'no') {
                    $('#pwtc-mapdb-rider-signup-div .errmsg').html('<div class="callout small warning"><p>You must accept the Club&#39;s <a href="/terms-and-conditions" target="_blank">terms and conditions</a> to sign up for rides.</p></div>');
                    evt.preventDefault();
                }
                else {
                    show_waiting();
                    $('#pwtc-mapdb-rider-signup-div button[type="submit"]').prop('disabled',true);
                }
            });
        });
        <?php } else { ?>
        <?php if ($set_mileage) { ?>
        $('#pwtc-mapdb-rider-signup-div .mileage-frm').on('submit', function(evt) {
            var mileage = $('#pwtc-mapdb-rider-signup-div form input[name="mileage"]').val().trim();
            if (mileage.length > 0) {
                mileage = Math.abs(parseInt(mileage, 10));
                if (mileage > <?php echo $ride_mileage; ?>) {
                    $('#pwtc-mapdb-rider-signup-div .errmsg').html('<div class="callout small warning"><p>You cannot log more than <?php echo $ride_mileage; ?> miles for this ride.</p></div>');
                    evt.preventDefault();
                    return;
                }
            }
            show_waiting();
            $('#pwtc-mapdb-rider-signup-div button[type="submit"]').prop('disabled',true);
            $('#pwtc-mapdb-rider-signup-div input[type="submit"]').prop('disabled',true);
        });
        <?php } ?>
        $('#pwtc-mapdb-rider-signup-div .cancel-frm').on('submit', function(evt) {
            show_waiting();
            $('#pwtc-mapdb-rider-signup-div button[type="submit"]').prop('disabled',true);
            $('#pwtc-mapdb-rider-signup-div input[type="submit"]').prop('disabled',true);
        });
        <?php } ?>
        
        $('#pwtc-mapdb-rider-signup-div form').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                evt.preventDefault(); 
                return false; 
            } 
        });

    });
</script>
<div id='pwtc-mapdb-rider-signup-div'>
    <ul class="breadcrumbs"><li><a href="<?php echo $ride_link; ?>">Back to Ride</a></li></ul>
    <?php if ($accept_signup) { ?>
        <div class="callout">
            <p>
            Hello <?php echo $rider_name; ?>, to sign up for the ride "<?php echo $ride_title; ?>," please accept the Club's <a href="/terms-and-conditions" target="_blank">terms and conditions</a>, enter your emergency contact information<?php if ($set_mileage) { ?>, enter the mileage that you intend to ride<?php } ?> and press the accept button.
        <?php if ( $ride_signup_limit > 0) { ?>
            This ride is limited to <?php echo $ride_signup_limit; ?> riders, there are <?php echo ($ride_signup_limit - $ride_signup_count); ?> spaces left.
        <?php } ?>                
        <?php if ($set_mileage) { ?> 
            <em>If you don't want your mileage logged, leave the mileage field blank.</em>
        <?php } ?>
            </p>
            <form method="POST" novalidate>
                <?php wp_nonce_field('signup-member-form', 'nonce_field'); ?>
                <div class="row">
                    <div class="small-12 medium-6 columns">
                        <label>Accept Terms and Conditions
                            <select name="accept_terms">
                                <option value="no" <?php echo $release_accepted ? '': 'selected'; ?>>No</option>
                                <option value="yes" <?php echo $release_accepted ? 'selected': ''; ?>>Yes</option>
                            </select>
                        </label>
                    </div>
                    <div class="small-12 medium-6 columns">
                        <label><i class="fa fa-phone"></i> Emergency Contact Phone
                            <input type="text" name="contact_phone" value="<?php echo $contact_phone; ?>"/>
                        </label>
                    </div>
                    <div class="small-12 medium-6 columns">
                        <label>Emergency Contact Name
                            <input type="text" name="contact_name" value="<?php echo $contact_name; ?>"/>
                        </label>
                    </div>
        <?php if ($set_mileage) { ?>
                    <div class="small-12 medium-6 columns">
                        <label>Mileage
                            <input type="number" name="mileage" value="<?php echo $mileage; ?>" maxlength="3" />
                        </label>
                    </div>
        <?php } ?>
                </div>
                <div class="row column errmsg">
        <?php if ($expired) { ?>
                    <div class="callout small warning"><p>Your club membership has expired, please renew. While expired members may still sign up for rides, your mileage will not be logged.</p></div>
        <?php } ?>
                </div>
                <div class="row column clearfix">
                    <input type="hidden" name="accept_user_signup" value="yes"/>
                    <button class="dark button float-left" type="submit"><i class="fa fa-user-plus"></i> Accept Sign-up</button>
                </div>
            </form>
        </div>
    <?php } else { ?>
        <div class="callout">
            <p>Hello <?php echo $rider_name; ?>, you are currently signed up for the ride "<?php echo $ride_title; ?>."
        <?php if ( $ride_signup_limit > 0) { ?>
            This ride is limited to <?php echo $ride_signup_limit; ?> riders, there are <?php echo ($ride_signup_limit - $ride_signup_count); ?> spaces left.
        <?php } ?>
            </p>
        <?php if ($set_mileage) { ?>
            <p>To update your mileage, enter the new value below and press the update button.</p>
            <form method="POST" class="mileage-frm" novalidate>
                <?php wp_nonce_field('signup-member-form', 'nonce_field'); ?>
                <div class="row">
                    <div class="input-group small-12 medium-3 columns">
                        <span class="input-group-label">Mileage</span>
                        <input type="hidden" name="accept_user_signup" value="update"/>
                        <input class="input-group-field" type="number" name="mileage" value="<?php echo $mileage; ?>" maxlength="3" />
                        <div class="input-group-button">
                            <input type="submit" class="dark button" value="Update">
                        </div>
                    </div>
                </div>
            </form>
        <?php } ?>
            <p>To cancel your sign up, press the cancel button below.</p>
            <form method="POST" class="cancel-frm">
                <?php wp_nonce_field('signup-member-form', 'nonce_field'); ?>
                <div class="row column errmsg"></div>
                <div class="row column clearfix">
                    <input type="hidden" name="accept_user_signup" value="no"/>
                    <button class="dark button float-left" type="submit"><i class="fa fa-user-times"></i> Cancel Sign-up</button>
                </div>
            </form>
        </div>
    <?php } ?>
    <?php if ($ride_signup_count > 0) { ?>
        <p>The following persons are signed up to attend this ride:<br>
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
            $attended = $arr['attended'];
            if ($attended) {
        ?>
            <strong><?php echo $name; ?></strong>,  
        <?php } else { ?>
            <s><?php echo $name; ?></s>,
        <?php } } ?>
        <?php foreach($nonmember_signup_list as $item) { 
            $arr = json_decode($item, true);
            $name = $arr['name'];
            $attended = $arr['attended'];
            if ($attended) {
        ?>
            <strong><?php echo $name; ?></strong>,
        <?php } else { ?>
            <s><?php echo $name; ?></s>,
        <?php } } ?>
        </p>
    <?php } else { ?>
        <p>No one is currently signed up for this ride.</p>
    <?php } ?>
</div>
<?php 
