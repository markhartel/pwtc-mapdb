<script type="text/javascript">
    jQuery(document).ready(function($) { 

        $('#pwtc-mapdb-leader-contact-div form select').change(function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-leader-contact-div form input[type="text"]').on('input', function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-leader-contact-div form').on('submit', function(evt) {
            is_dirty = false;
            $('#pwtc-mapdb-leader-contact-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');
            $('#pwtc-mapdb-leader-contact-div input[type="submit"]').prop('disabled',true);
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
<div id="pwtc-mapdb-leader-contact-div" class="callout">
    <form method="POST">
        <div class="row">
            <div class="small-12 medium-6 columns">
                <label>Use Contact Email?
                    <select name="use_contact_email">
                        <option value="no" <?php echo $use_contact_email ? '': 'selected'; ?>>No, use account email instead</option>
                        <option value="yes"  <?php echo $use_contact_email ? 'selected': ''; ?>>Yes</option>
                    </select>
                </label>
            </div>
            <div class="small-12 medium-6 columns">
                <label><i class="fa fa-envelope"></i> Contact Email
                    <input type="text" name="contact_email" value="<?php echo $contact_email; ?>"/>
                </label>
            </div>
            <div class="small-12 medium-6 columns">
                <label><i class="fa fa-phone"></i> Contact Voice Phone
                    <input type="text" name="voice_phone" value="<?php echo $voice_phone; ?>"/>
                </label>
            </div>
            <div class="small-12 medium-6 columns">
                <label><i class="fa fa-mobile"></i> Contact Text Phone
                    <input type="text" name="text_phone" value="<?php echo $text_phone; ?>"/>
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
