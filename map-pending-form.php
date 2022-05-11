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
        <p>The draft route map "<?php echo $map_title; ?>" was submitted for review<?php if ($email_status == 'yes') { ?> and the road captain notified by email<?php } else if ($email_status == 'failed') { ?> but failed to notify road captain by email<?php } ?>.</p>
    </div>
    <div class="row column">
        <p>Did you submit this map by mistake? If so, <a class="revert-action">undo the submission.</a></p>
        <p>Do you wish to submit additional route maps or rides? If so, you may use the convenience links below.<ul>
            <li><a href="<?php echo $create_map_link; ?>">Submit another new route map.</a></li>
            <li><a href="<?php echo $create_ride_link; ?>">Submit a new ride.</a></li>
        </ul></p>
    </div>
    <form method="POST">
        <?php wp_nonce_field('map-edit-form', 'nonce_field'); ?>
        <input type="hidden" name="postid" value="<?php echo $postid; ?>"/>
        <input type="hidden" name="post_status" value="<?php echo $status; ?>"/>
        <input type="hidden" name="revert" value="draft"/>
    </form> 
</div>
<?php 
