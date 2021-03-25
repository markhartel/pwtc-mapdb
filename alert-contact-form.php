<script type="text/javascript">
    jQuery(document).ready(function($) { 

        $('#pwtc-mapdb-alert-contact-div form input[type="text"]').on('input', function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-alert-contact-div form').on('submit', function(evt) {
            is_dirty = false;
            $('#pwtc-mapdb-alert-contact-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');
            $('#pwtc-mapdb-alert-contact-div input[type="submit"]').prop('disabled',true);
        });

        window.addEventListener('beforeunload', function(e) {
            if (is_dirty) {
                e.preventDefault();
                e.returnValue = 'If you leave this page, any data you have entered will not be saved.';
            }
            else {
                delete e['returnValue'];
            }
        });

        var is_dirty = false;

    });
</script>
<div id="pwtc-mapdb-alert-contact-div" class="callout">
    <form method="POST">
        <?php wp_nonce_field('alert-contact-form', 'nonce_field'); ?>
        <div class="row">
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
        </div>
        <div class="row column errmsg"></div>
        <div class="row column clearfix">
            <input class="dark button float-left" type="submit" value="Submit"/>
        </div>
    </form>
</div>
<?php 
