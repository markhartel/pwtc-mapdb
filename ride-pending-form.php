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
    <div class="callout small success">
        <p>The draft ride "<?php echo $ride_title; ?>" was submitted for review, please <a href="<?php echo $notify_link; ?>">notify road captain by email.</a> Or, if you've changed your mind, <a class="revert-action">revert ride back to draft.</a></p>
    </div>
    <p><?php echo $return_to_ride; ?></p>
    <form method="POST">
        <input type="hidden" name="postid" value="<?php echo $postid; ?>"/>
        <input type="hidden" name="revert" value="draft"/>
    </form> 
</div>
<?php 
