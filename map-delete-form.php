<script type="text/javascript">
    jQuery(document).ready(function($) { 

<?php if ($deleted) { ?>

        $('#pwtc-mapdb-delete-map-div .revert-action').on('click', function(evt) {
            $('#pwtc-mapdb-delete-map-div form').submit();
        });

        $('#pwtc-mapdb-delete-map-div form').on('submit', function(evt) {
            $('#pwtc-mapdb-delete-map-div .callout').html('<p><i class="fa fa-spinner fa-pulse"></i> please wait...</p>');
        })

<?php } else { ?>

        $('#pwtc-mapdb-delete-map-div form').on('submit', function(evt) {
            $('#pwtc-mapdb-delete-map-div .errmsg').html('<p><i class="fa fa-spinner fa-pulse"></i> please wait...</p>');
            $('#pwtc-mapdb-delete-map-div input[type="submit"]').prop('disabled',true);
        })

        $(document).on( 'heartbeat-send', function( e, data ) {
            var send = {};
            send.post_id = '<?php echo $postid; ?>';
            data['pwtc-refresh-post-lock'] = send;
        });

        $(document).on( 'heartbeat-tick', function( e, data ) {
            if ( data['pwtc-refresh-post-lock'] ) {
                var received = data['pwtc-refresh-post-lock'];
                if ( received.lock_error ) {
                    $('#pwtc-mapdb-delete-map-div').html('<div class="callout small alert">You cannot delete draft route map "<?php echo $map_title; ?>". ' + received.lock_error.text + '</div>');
                } 
                else if ( received.new_lock ) {
                }
            }
        });

        wp.heartbeat.interval( 15 );

<?php } ?>

    });
</script>
<div id="pwtc-mapdb-delete-map-div">
    <?php echo $return_to_map; ?>
    <form method="POST">
        <?php wp_nonce_field('map-delete-form', 'nonce_field'); ?>
        <input type="hidden" name="postid" value="<?php echo $postid; ?>"/>
<?php if ($deleted) { ?>
        <input type="hidden" name="undo_delete" value="yes"/>
        <div class="callout small success">
            <p>This route map has been successfully deleted. <a class="revert-action">Undo</a></p>
        </div>
<?php } else { ?>
        <input type="hidden" name="delete_map" value="yes"/>
        <div class="callout">
            <div class="row column">
                <p>To delete draft route map "<?php echo $map_title; ?>", press the delete button below.</p>
            </div>
            <div class="row column errmsg"></div>
            <div class="row column clearfix">
                <input class="dark button float-left" type="submit" value="Delete Map"/>
            </div>
        </div>
<?php } ?>
    </form>
</div>
<?php 