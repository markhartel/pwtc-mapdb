<style>
    .indicate-error {
        border-color: #900 !important;
        background-color: #FDD !important;
    }
</style>
<script type="text/javascript">
    jQuery(document).ready(function($) { 
        
        function show_warning(msg) {
            $('#pwtc-mapdb-edit-map-div .errmsg').html('<div class="callout small warning"><p>' + msg + '</p></div>');
        }

        function show_waiting() {
            $('#pwtc-mapdb-edit-map-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');
        }

        $('#pwtc-mapdb-edit-map-div form').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                evt.preventDefault(); 
                return false; 
            } 
        });	

        $('#pwtc-mapdb-edit-map-div input[name="title"]').on('input', function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-map-div form').on('submit', function(evt) {
            $('#pwtc-mapdb-edit-map-div input').removeClass('indicate-error');

            if ($('#pwtc-mapdb-edit-map-div input[name="title"]').val().trim().length == 0) {
                show_warning('The <strong>route map title</strong> cannot be blank.');
                $('#pwtc-mapdb-edit-map-div input[name="title"]').addClass('indicate-error');
                evt.preventDefault();
                return;
            }

            is_dirty = false;
            show_waiting();
            $('#pwtc-mapdb-edit-map-div button[type="submit"]').prop('disabled',true);
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

    <?php if ($postid != 0) { ?>
        $(document).on( 'heartbeat-send', function( e, data ) {
            var send = {};
            send.post_id = '<?php echo $postid; ?>';
            data['pwtc-refresh-post-lock'] = send;
        });

        $(document).on( 'heartbeat-tick', function( e, data ) {
            if ( data['pwtc-refresh-post-lock'] ) {
                var received = data['pwtc-refresh-post-lock'];
                if ( received.lock_error ) {
                    show_warning('You can no longer edit this route map. ' + received.lock_error.text);
                    $('#pwtc-mapdb-edit-map-div button[type="submit"]').prop('disabled',true);
                } 
                else if ( received.new_lock ) {
                }
            }
        });

        wp.heartbeat.interval( 15 );
    <?php } ?>		

    });
</script>
<div id='pwtc-mapdb-edit-map-div'>
    <?php echo $return_to_map; ?>
    <?php if (!empty($operation)) { ?>
    <div class="callout small success">
        <p>
        <?php if ($operation == 'update_draft') { ?>
        The draft route map was updated.
        <?php } else if ($operation == 'submit_review') { ?>
        The draft route map was submitted for review.
        <?php } else if ($operation == 'update_pending') { ?>
        The pending route map was updated.
        <?php } else if ($operation == 'published_draft') { ?>
        The draft route map was published.
        <?php } else if ($operation == 'published') { ?>
        The pending route map was published
        <?php if ($email_status == 'yes') { ?> and the author notified by email
        <?php } else if ($email_status == 'failed') { ?> but failed to notify author by email<?php } ?>.
        <?php } else if ($operation == 'rejected') { ?>
        The pending route map was rejected
        <?php if ($email_status == 'yes') { ?> and the author notified by email
        <?php } else if ($email_status == 'failed') { ?> but failed to notify author by email<?php } ?>.
        <?php } else if ($operation == 'update_published') { ?>
        The published route map was updated.
        <?php } else if ($operation == 'unpublished') { ?>
        The published route map was unpublished.
        <?php } else if ($operation == 'insert') { ?>
        The first draft of your route map was saved.
        <?php } else if ($operation == 'revert_draft') { ?>
        The route map was reverted back to draft
        <?php if ($email_status == 'yes') { ?> and the road captain notified by email
        <?php } else if ($email_status == 'failed') { ?> but failed to notify road captain by email<?php } ?>.
        <?php } ?>
        </p>
    </div>
    <?php } ?>
    <div>
        <p>
    <?php if ($postid != 0) { ?>
        This route map was authored by 
        <?php if ($author != $current_user->ID) { 
            echo '<a href="' . esc_url('mailto:' . $author_email) . '">' . $author_name . '</a>';
        } else { 
            echo $author_name;
        } ?> and is 
        <?php if ($status == 'draft') { ?>
        a draft. It can be updated or <?php if ($allow_leaders and !$is_road_captain) { ?>submitted for review<?php } else { ?>published<?php } ?> using the buttons at the bottom of the form.
        <?php } else if ($status == 'pending') { ?>
        pending review by a road captain. It can be updated, published or rejected using the buttons at the bottom of the form.
        <?php } else if ($status == 'publish') { ?>
        published and ready for use. It can be updated or unpublished using the buttons at the bottom of the form.
        <?php } ?>
    <?php } else { ?>
        <?php if ($copy_ride) { ?>
        This is a new route map copied from an existing route map, set the <em>ride date</em> in the form below (and modify any other desired fields) and press the save button at the bottom of the form.
        <?php } else { ?>
        This is a new route map, fill out the form below and press the save button at the bottom of the form.
        <?php } ?>
    <?php } ?>
        </p>
    </div>
    <div class="callout">
        <form method="POST" novalidate>
            <?php wp_nonce_field('map-edit-form', 'nonce_field'); ?>
            <div class="row column">
                <label>Route Map Title
                    <input type="text" name="title" value="<?php echo esc_attr($title); ?>"/>
                    <input type="hidden" name="postid" value="<?php echo $postid; ?>"/>
                    <input type="hidden" name="post_status" value="<?php echo $status; ?>"/>
                </label>
            </div>
            <div class="row">
                <div class="small-12 medium-6 columns">
                    <label>Distance
                        <input type="number" name="distance" value="<?php echo $distance; ?>"/>	
                    </label>
                </div>
                <div class="small-12 medium-6 columns">
                    <label>Max Distance
                        <input type="number" name="max_distance" value="<?php echo $max_distance; ?>"/>	
                    </label>
                </div>
                <div class="small-12 medium-6 columns">
                    <fieldset class="terrain-fst">
                        <legend>Terrain</legend>
                        <input type="checkbox" name="terrain[]" value="a" id="terrain-a" <?php echo in_array('a', $terrain) ? 'checked': ''; ?>><label for="terrain-a">A</label>
                        <input type="checkbox" name="terrain[]" value="b" id="terrain-b" <?php echo in_array('b', $terrain) ? 'checked': ''; ?>><label for="terrain-b">B</label>
                        <input type="checkbox" name="terrain[]" value="c" id="terrain-c" <?php echo in_array('c', $terrain) ? 'checked': ''; ?>><label for="terrain-c">C</label>
                        <input type="checkbox" name="terrain[]" value="d" id="terrain-d" <?php echo in_array('d', $terrain) ? 'checked': ''; ?>><label for="terrain-d">D</label>
                        <input type="checkbox" name="terrain[]" value="e" id="terrain-e" <?php echo in_array('e', $terrain) ? 'checked': ''; ?>><label for="terrain-e">E</label>
                    </fieldset>
                </div>
                <div class="small-12 medium-6 columns">
                    <fieldset>
                        <legend>Route Map Type</legend>
                        <input type="radio" name="map_type" value="file" id="type-file" <?php echo $map_type == 'file' ? 'checked': ''; ?>><label for="type-file">Download File</label>
                        <input type="radio" name="map_type" value="link" id="type-link" <?php echo $map_type == 'link' ? 'checked': ''; ?>><label for="type-link">URL Link</label>
                    </fieldset>
                </div>
            </div>
            <div class="row column errmsg"></div>
            <div class="row column clearfix">
            <?php if ($postid == 0) { ?>
                <button class="dark button float-left" type="submit">Save Draft</button>
            <?php } else if ($status == 'draft') { ?>
                <div class="button-group float-left">
                    <input class="dark button" name="draft" value="Update" type="submit"/>
                    <?php if ($allow_leaders and !$is_road_captain) { ?>
                    <input class="dark button" name="pending" value="Submit for Review" type="submit"/>
                    <?php } else { ?>
                    <input class="dark button" name="publish" value="Publish" type="submit"/>
                    <?php } ?>
                </div>
            <?php } else if ($status == 'pending') { ?>
                <div class="button-group float-left">
                    <input class="dark button" name="pending" value="Update" type="submit"/>
                    <input class="dark button" name="publish" value="Publish" type="submit"/>
                    <input class="dark button" name="draft" value="Reject" type="submit"/>
                </div>
            <?php } else { ?>
                <div class="button-group float-left">
                    <input class="dark button" name="publish" value="Update" type="submit"/>
                    <input class="dark button" name="draft" value="Unpublish" type="submit"/>
                </div>
            <?php } ?>
            </div>
        </form>
    </div>
</div>
<?php 
