<script type="text/javascript">
    jQuery(document).ready(function($) { 
        $('#pwtc-mapdb-edit-map-div .revert-action').on('click', function(evt) {
            $('#pwtc-mapdb-edit-map-div form').submit();
        });

        $('#pwtc-mapdb-edit-map-div form').on('submit', function(evt) {
            $('#pwtc-mapdb-edit-map-div .callout').html('<i class="fa fa-spinner fa-pulse"></i> please wait...');
        })
    });
</script>
<div id='pwtc-mapdb-edit-map-div'>
    <?php echo $return_to_map; ?>
    <div class="callout small success">
        <p>The draft route map "<?php echo $map_title; ?>" was submitted for review
        <?php if ($email_status == 'yes') { ?> and the road captain notified by email
        <?php } else if ($email_status == 'failed') { ?> but failed to notify road captain by email<?php } ?>. 
        <a class="revert-action">Undo</a></p>
    </div>
    <div class="row column">
        <p class="help-text">Do you wish to create additional route maps or rides? If yes, you may use the convenience buttons below.</p>
    </div>
    <div class="row column clearfix">
        <div class="button-group float-left">
            <a class="dark button" href="<?php echo $create_map_link; ?>">Create Another Map</a>
            <a class="dark button" href="<?php echo $create_ride_link; ?>">Create New Ride</a>
        </div>
    </div>
    <form method="POST">
        <?php wp_nonce_field('map-edit-form', 'nonce_field'); ?>
        <input type="hidden" name="postid" value="<?php echo $postid; ?>"/>
        <input type="hidden" name="post_status" value="<?php echo $status; ?>"/>
        <input type="hidden" name="revert" value="draft"/>
    </form> 
</div>
<?php 
