<script type="text/javascript">
    jQuery(document).ready(function($) { 
        $('#pwtc-mapdb-edit-ride-div .revert-action').on('click', function(evt) {
            $('#pwtc-mapdb-edit-ride-div form').submit();
        });

        $('#pwtc-mapdb-edit-ride-div form').on('submit', function(evt) {
            $('#pwtc-mapdb-edit-ride-div .callout').html('<i class="fa fa-spinner fa-pulse"></i> please wait...');
        })

        <?php if (empty($return)) { ?>
        var opener_win = window.opener;
        if (opener_win) {
            opener_win.location.reload();
        }
        <?php } ?>
    });
</script>
<div id='pwtc-mapdb-edit-ride-div'>
    <div class="callout small warning">
        <p>Published ride "<?php echo $ride_title; ?>" has already finished so you cannot edit it. Click <a class="revert-action">here</a> if you want to revert this ride back to draft. <?php echo $return_to_ride; ?></p>
    </div>
    <form method="POST">
        <input type="hidden" name="postid" value="<?php echo $postid; ?>"/>
        <input type="hidden" name="revert" value="draft"/>
    </form> 
</div>
<?php 