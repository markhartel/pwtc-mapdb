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
        
        $('#pwtc-mapdb-edit-map-div form textarea').on('keypress', function(evt) {
            is_dirty = true;
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                evt.stopPropagation(); 
            } 
        });		

        $('#pwtc-mapdb-edit-map-div input[name="title"]').on('input', function() {
            is_dirty = true;
        });
        
        $('#pwtc-mapdb-edit-map-div input[name="distance"]').on('input', function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-map-div input[name="max_distance"]').on('input', function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-edit-map-div input[type="checkbox"]').change(function() {
            is_dirty = true;
        });
        
        $('#pwtc-mapdb-edit-map-div input[name="map_type"]').change(function() {
            if (this.value == 'file') {
                $('#pwtc-mapdb-edit-map-div form .map-type-link').hide();
		$('#pwtc-mapdb-edit-map-div form .map-type-link input').prop('disabled',true);
                $('#pwtc-mapdb-edit-map-div form .map-type-file input').prop('disabled',false);
                $('#pwtc-mapdb-edit-map-div form .map-type-file').show();
            }
            else if (this.value == 'link') {
                $('#pwtc-mapdb-edit-map-div form .map-type-file').hide();
                $('#pwtc-mapdb-edit-map-div form .map-type-file input').prop('disabled',true);
                $('#pwtc-mapdb-edit-map-div form .map-type-link input').prop('disabled',false);
                $('#pwtc-mapdb-edit-map-div form .map-type-link').show();
            }
            is_dirty = true;
        });
        
        $('#pwtc-mapdb-edit-map-div input[name="map_file_upload"]').on('change', function(e) {
            var fileName = '';
            if (e.target.value) {
		fileName = e.target.value.split('\\').pop();
            }
	    if (fileName) {
		$('#pwtc-mapdb-edit-map-div .file-upload-lbl').html('Upload File: ' + fileName);
            }
	    else {
		$('#pwtc-mapdb-edit-map-div .file-upload-lbl').html('Upload File');
            }
	    is_dirty = true;
        });

        $('#pwtc-mapdb-edit-map-div form').on('submit', function(evt) {
            $('#pwtc-mapdb-edit-map-div input').removeClass('indicate-error');
            $('#pwtc-mapdb-edit-map-div textarea').removeClass('indicate-error');
            $('#pwtc-mapdb-edit-map-div .terrain-fst').removeClass('indicate-error');
	    $('#pwtc-mapdb-edit-map-div .file-upload-lbl').removeClass('indicate-error');

            if ($('#pwtc-mapdb-edit-map-div input[name="title"]').val().trim().length == 0) {
                show_warning('The <strong>route map title</strong> cannot be blank.');
                $('#pwtc-mapdb-edit-map-div input[name="title"]').addClass('indicate-error');
                evt.preventDefault();
                return;
            }
            
            var terrain_empty = $('#pwtc-mapdb-edit-map-div input[name="terrain[]"]:checked').length == 0;
            if (terrain_empty) {
                show_warning('You must choose at least one <strong>route map terrain</strong>.');
                $('#pwtc-mapdb-edit-map-div .terrain-fst').addClass('indicate-error');
                evt.preventDefault();
                return;						
            }
            var dist = $('#pwtc-mapdb-edit-map-div input[name="distance"]').val().trim();
            if (dist.length == 0) {
                show_warning('You must enter a <strong>route map distance</strong>.');
                $('#pwtc-mapdb-edit-map-div input[name="distance"]').addClass('indicate-error');
                evt.preventDefault();
                return;							
            }
            dist = parseInt(dist, 10);
            if (dist == NaN || dist < 0) {
                show_warning('You must enter a <strong>route map distance</strong> that is a non-negative number.');
                $('#pwtc-mapdb-edit-map-div input[name="distance"]').addClass('indicate-error');
                evt.preventDefault();
                return;							
            }
            var maxdist = $('#pwtc-mapdb-edit-map-div input[name="max_distance"]').val().trim();
            if (maxdist.length > 0) {
                maxdist = parseInt(maxdist, 10);
                if (maxdist == NaN || maxdist < 0) {
                    show_warning('You must enter a <strong>route map max distance</strong> that is a non-negative number.');
                    $('#pwtc-mapdb-edit-map-div input[name="max_distance"]').addClass('indicate-error');
                    evt.preventDefault();
                    return;							
                }
                if (maxdist <= dist) {
                    show_warning('The <strong>route map max distance</strong> must be greater than the <strong>ride distance</strong>.');
                    $('#pwtc-mapdb-edit-map-div input[name="max_distance"]').addClass('indicate-error');
                    evt.preventDefault();
                    return;									
                }					
            }
	    if ($('#pwtc-mapdb-edit-map-div input[name="map_type"]:checked').val() == 'link') {
                var url = $('#pwtc-mapdb-edit-map-div input[name="map_link"]').val().trim();
                if (url.length == 0) {
                    show_warning('The <strong>route map link</strong> must be set.');
                    $('#pwtc-mapdb-edit-map-div input[name="map_link"]').addClass('indicate-error');
                    evt.preventDefault();
                    return;
                }
                var elem = $('#pwtc-mapdb-edit-map-div input[name="map_link"]')[0];
                if (elem.validity) {
                    if (!elem.validity.valid) {
                        show_warning('The <strong>route map link</strong> has an invalid format.');
                        $('#pwtc-mapdb-edit-map-div input[name="map_link"]').addClass('indicate-error');
                        evt.preventDefault();
                        return;                     
                    }
                }
            }
            else {
                var attach_id = $('#pwtc-mapdb-edit-map-div input[name="map_file_id"]').val().trim();
                var file = $('#pwtc-mapdb-edit-map-div input[name="map_file_upload"]').val().trim();
                if (file.length == 0 && attach_id == '0') {
                    show_warning('The <strong>route map file</strong> must be set.');
                    $('#pwtc-mapdb-edit-map-div input[name="map_file_name"]').addClass('indicate-error');
                    evt.preventDefault();
                    return;
                }
                if (file.length > 0) {
                    var elem = $('#pwtc-mapdb-edit-map-div input[name="map_file_upload"]')[0];
                    if (elem.validity) {
                        if (!elem.validity.valid) {
                            show_warning('The <strong>upload file</strong> has an invalid format.');
			    $('#pwtc-mapdb-edit-map-div .file-upload-lbl').addClass('indicate-error');
                            evt.preventDefault();
                            return;                     
                        }
                    }
                }
            }

            is_dirty = false;
            show_waiting();
            $('#pwtc-mapdb-edit-map-div button[type="submit"]').prop('disabled',true);
        });
        
        <?php if ($map_type == 'file') { ?>
            $('#pwtc-mapdb-edit-map-div form .map-type-link').hide();
            $('#pwtc-mapdb-edit-map-div form .map-type-link input').prop('disabled',true);
            $('#pwtc-mapdb-edit-map-div form .map-type-file input').prop('disabled',false);
            $('#pwtc-mapdb-edit-map-div form .map-type-file').show();
        <?php } else { ?>
            $('#pwtc-mapdb-edit-map-div form .map-type-file').hide();
            $('#pwtc-mapdb-edit-map-div form .map-type-file input').prop('disabled',true);
            $('#pwtc-mapdb-edit-map-div form .map-type-link input').prop('disabled',false);
            $('#pwtc-mapdb-edit-map-div form .map-type-link').show();
        <?php } ?>

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
        <form method="POST" enctype="multipart/form-data" novalidate>
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
                    <label>Route Map Distance
                        <input type="number" name="distance" value="<?php echo $distance; ?>"/>	
                    </label>
                </div>
                <div class="small-12 medium-6 columns">
                    <label>Route Map Max Distance
                        <input type="number" name="max_distance" value="<?php echo $max_distance; ?>"/>	
                    </label>
                </div>
                <div class="small-12 medium-6 columns">
                    <fieldset class="terrain-fst">
                        <legend>Route Map Terrain</legend>
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
            <div class="row column map-type-file">
                <input type="hidden" name="map_file_id" value="<?php echo $map_file_id; ?>"/> 
                <label>Route Map File
            <?php if (!empty($map_file_url)) { ?>
                    <a href="<?php echo $map_file_url; ?>" title="Download route map file." target="_blank" download><i class="fa fa-download"></i></a>
            <?php } ?>
                    <input type="text" name="map_file_name" value="<?php echo esc_attr($map_file_name); ?>" readonly/>
                </label>
                <p class="help-text">You cannot edit the route map file directly, instead press the upload file button below to choose a new file to upload when this route map is saved.</p>
            </div>
            <div class="row column map-type-file">
                <label for="map-file-upload" class="dark button file-upload-lbl">Upload File</label>
                <input type="file" id="map-file-upload" class="show-for-sr" accept="image/*,.pdf" name="map_file_upload"/>
            </div>
            <div class="row column map-type-link">
                <label>Route Map Link
                    <input type="url" name="map_link" value="<?php echo $map_link; ?>"/>
                </label>
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
