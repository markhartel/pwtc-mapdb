<script type="text/javascript">
    jQuery(document).ready(function($) { 

        $('#confirm-delete-modal .confirm-delete-btn').on('click', function(evt) {
            $('#confirm-delete-modal').foundation('close');
            $('#pwtc-mapdb-delete-file-div form').submit();
        });

        $('#pwtc-mapdb-delete-file-div form').on('submit', function(evt) {
            $('#pwtc-mapdb-delete-map-div .errmsg').html('<p><i class="fa fa-spinner fa-pulse"></i> please wait...</p>');
        });

    });
</script>
<div id="pwtc-mapdb-delete-file-div">
    <?php echo $return_to_page; ?>
    <form method="POST">
        <?php wp_nonce_field('file-delete-form', 'nonce_field'); ?>
        <input type="hidden" name="attach_id" value="<?php echo $attach_id; ?>"/>
        <div class="callout">
            <div class="row column">
                <p>To delete file attachment "<?php echo $title; ?>", press the delete button below.</p>
            </div>
            <div class="row column errmsg"></div>
            <div class="row column clearfix">
                <button class="dark button float-left" type="button" data-open="confirm-delete-modal">Delete File</button>
            </div>
        </div>
        <div class="reveal" id="confirm-delete-modal" data-reveal>
            <div class="row column">
                <p>This attachment and its uploaded file will be permanently deleted if you continue and cannot be undone. Do you really want to do this?</p>
            </div>
            <div class="row column clearfix">
                <button class="confirm-delete-btn dark button float-left" type="button">Yes, Delete File</button>
                <button class="dark button float-right" type="button" data-close>Cancel</button>
            </div>
        </div>
    </form>
</div>
<?php 
