<script type="text/javascript">
    jQuery(document).ready(function($) { 
        $('#pwtc-mapdb-delete-ride-div a.action-delete').on('click', function(evt) {
            $('#pwtc-mapdb-delete-ride-div .delete-ride').hide();
            $('#pwtc-mapdb-delete-ride-div .delete-ride-confirm').show();
        });

        $('#pwtc-mapdb-delete-ride-div a.action-cancel').on('click', function(evt) {
            $('#pwtc-mapdb-delete-ride-div .delete-ride-confirm').hide();
            $('#pwtc-mapdb-delete-ride-div .delete-ride').show();
        });

        $('#pwtc-mapdb-delete-ride-div .delete-ride-confirm').hide();
        $('#pwtc-mapdb-delete-ride-div .delete-ride').show();

        $(document).on( 'heartbeat-send', function( e, data ) {
            var send = {};
            send.post_id = '<?php echo $postid; ?>';
            data['pwtc-refresh-post-lock'] = send;
        });

        $(document).on( 'heartbeat-tick', function( e, data ) {
            if ( data['pwtc-refresh-post-lock'] ) {
                var received = data['pwtc-refresh-post-lock'];
                if ( received.lock_error ) {
                    $('#pwtc-mapdb-delete-ride-div').html('<div class="callout small alert">You cannot delete draft ride "<?php echo $ride_title; ?>" on <?php echo $ride_date; ?>. ' + received.lock_error.text + '</div>');
                } 
                else if ( received.new_lock ) {
                }
            }
        });

        wp.heartbeat.interval( 15 );

    });
</script>
<div id="pwtc-mapdb-delete-ride-div">
    <form method="POST">
        <div class="row column">
            <div class="delete-ride callout small"><p>To delete draft ride "<?php echo $ride_title; ?>" on <?php echo $ride_date; ?>, press the delete button below. <?php echo $return_to_ride; ?></p></div>
            <div class="delete-ride-confirm callout small alert"><p>Warning: this action will permanently delete draft ride "<?php echo $ride_title; ?>" on <?php echo $ride_date; ?>! Do you really want to do this?</p></div>
        </div>
        <div class="row column clearfix">
            <a class="action-delete delete-ride dark button float-left">Delete Ride</a>
            <input class="delete-ride-confirm accent button float-left" type="submit" name="delete_ride" value="OK"/>
            <a class="action-cancel delete-ride-confirm dark button float-right">Cancel</a>
        </div>
    </form>
</div>
<?php 
