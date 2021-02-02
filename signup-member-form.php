<script type="text/javascript">
    jQuery(document).ready(function($) { 

        function show_waiting() {
            $('#pwtc-mapdb-rider-signup-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse waiting"></i> please wait...</div>');
        }

        $('#pwtc-mapdb-rider-signup-div form').on('submit', function(evt) {
            <?php if ($accept_signup) { ?>
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
            <?php } else { ?>
            show_waiting();
            $('#pwtc-mapdb-rider-signup-div button[type="submit"]').prop('disabled',true);
            <?php } ?>
            });
        
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
    <?php if ($accept_signup) { ?>
        <div class="callout">
            <p>
            Hello <?php echo $rider_name; ?>, to sign up for the ride "<?php echo $ride_title; ?>," please accept the Club's <a href="/terms-and-conditions" target="_blank">terms and conditions</a>, enter your emergency contact information<?php if ($set_mileage) { ?>, enter the mileage that you intend to ride<?php } ?> and press the accept button.
        <?php if ($set_mileage) { ?> 
            <em>You may ask the leader to change your mileage at ride start if desired. If you don't want your mileage logged, leave the mileage field blank.</em>
        <?php } ?>
            </p>
            <form method="POST">
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
                    <a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
                </div>
            </form>
        </div>
    <?php } else { ?>
        <div class="callout">
            <p>Hello <?php echo $rider_name; ?>, you are currently signed up for the ride "<?php echo $ride_title; ?>." To cancel your sign up, please press the cancel button below.</p>
            <form method="POST">
                <div class="row column errmsg"></div>
                <div class="row column clearfix">
                    <input type="hidden" name="accept_user_signup" value="no"/>
                    <button class="dark button float-left" type="submit"><i class="fa fa-user-times"></i> Cancel Sign-up</button>
                    <a href="<?php echo $ride_link; ?>" class="dark button float-right"><i class="fa fa-chevron-left"></i> Back to Ride</a>
                </div>
            </form>
        </div>
    <?php } ?>
</div>
<?php 