<script type="text/javascript">
    jQuery(document).ready(function($) { 
        $('#pwtc-mapdb-reset-signups-div a.reset-signup').on('click', function(evt) {
            $('#pwtc-mapdb-reset-signups-div .reset-signup').hide();
            $('#pwtc-mapdb-reset-signups-div .reset-signup-confirm').show();
        });

        $('#pwtc-mapdb-reset-signups-div a.reset-signup-confirm').on('click', function(evt) {
            $('#pwtc-mapdb-reset-signups-div .reset-signup-confirm').hide();
            $('#pwtc-mapdb-reset-signups-div .reset-signup').show();
        });
        
        $('#pwtc-mapdb-reset-signups-div form').on('submit', function(evt) {
            $('#pwtc-mapdb-reset-signups-div button[type="submit"]').prop('disabled',true);
        });

        $('#pwtc-mapdb-reset-signups-div .reset-signup-confirm').hide();
        $('#pwtc-mapdb-reset-signups-div .reset-signup').show();
    });
</script>
<div id="pwtc-mapdb-reset-signups-div">
    <ul class="accordion" data-accordion data-allow-all-closed="true">
        <li class="accordion-item" data-accordion-item>
            <a href="#" class="accordion-title">Purge Sign-up Settings...</a>
            <div class="accordion-content" data-tab-content>
                <form method="POST">
                    <?php wp_nonce_field('signup-reset-form', 'nonce_field'); ?>
                    <div class="row column">
                        <div class="reset-signup callout small">To remove all of the riders currently signed-up for this ride, press the remove button below. This will also reset the ride to not allow sign-ups.</div>
                        <div class="reset-signup-confirm callout small alert">Warning: this action will remove all of the riders currently signed-up for this ride! Do you really want to do this?</div>
                    </div>
                    <div class="row column clearfix">
                        <a class="reset-signup dark button float-left">Remove Sign-ups</a>
                        <input type="hidden" name="reset_ride_signups" value="yes"/>
                        <button class="reset-signup-confirm accent button float-left" type="submit">OK</button>
                        <a class="reset-signup-confirm dark button float-right">Cancel</a>
                    </div>
                </form>
            </div>
        </li>
    </ul>
</div>
<?php 
