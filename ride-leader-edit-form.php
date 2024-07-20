<style>
    .indicate-error {
        border-color: #900 !important;
        background-color: #FDD !important;
    }
</style>
<script type="text/javascript">
    jQuery(document).ready(function($) { 

        function show_warning(msg) {
            $('#pwtc-mapdb-leader-edit-ride-div .errmsg').html('<div class="callout small warning"><p>' + msg + '</p></div>');
        }

        function show_waiting() {
            $('#pwtc-mapdb-leader-edit-ride-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');
        }

        $('#pwtc-mapdb-leader-edit-ride-div form').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                evt.preventDefault(); 
                return false; 
            } 
        });	

        $('#pwtc-mapdb-leader-edit-ride-div form textarea').on('keypress', function(evt) {
            is_dirty = true;
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                evt.stopPropagation(); 
            } 
        });	

        $('#pwtc-mapdb-leader-edit-ride-div input[name="title"]').on('input', function() {
            is_dirty = true;
            $(this).removeClass('indicate-error');
        });
	    
        $('#pwtc-mapdb-leader-edit-ride-div input[name="ride_time"]').change(function() {
            is_dirty = true;
	    $(this).removeClass('indicate-error');
        });

        $('#pwtc-mapdb-leader-edit-ride-div input[name="start_location_comment"]').on('input', function() {
            is_dirty = true;
        });

        $('#pwtc-mapdb-leader-edit-ride-div form').on('submit', function(evt) {
            $('#pwtc-mapdb-leader-edit-ride-div input').removeClass('indicate-error');
            $('#pwtc-mapdb-leader-edit-ride-div textarea').removeClass('indicate-error');

            if ($('#pwtc-mapdb-leader-edit-ride-div input[name="title"]').val().trim().length == 0) {
                show_warning('The <strong>ride title</strong> cannot be blank.');
                $('#pwtc-mapdb-leader-edit-ride-div input[name="title"]').addClass('indicate-error');
                evt.preventDefault();
                return;
            }

            if ($('#pwtc-mapdb-leader-edit-ride-div textarea[name="description"]').val().trim().length == 0) {
                show_warning('The <strong>ride description</strong> cannot be blank.');
                $('#pwtc-mapdb-leader-edit-ride-div textarea[name="description"]').addClass('indicate-error');
                evt.preventDefault();
                return;
            }
		
            var time = $('#pwtc-mapdb-leader-edit-ride-div input[name="ride_time"]').val().trim();
            if (time.length == 0) {
                show_warning('The <strong>departure time</strong> must be set.');
                $('#pwtc-mapdb-leader-edit-ride-div input[name="ride_time"]').addClass('indicate-error');
                evt.preventDefault();
                return;			
            }
            var timergx = /^\d{2}:\d{2}$/;
            if (!timergx.test(time)) {
                show_warning('The <strong>departure time</strong> format is invalid. Your browser may not support time entry, try upgrading it to the latest version or use a different browser.');
                $('#pwtc-mapdb-leader-edit-ride-div input[name="ride_time"]').addClass('indicate-error');
                evt.preventDefault();
                return;					
            }
            is_dirty = false;
            show_waiting();
            $('#pwtc-mapdb-leader-edit-ride-div button[type="submit"]').prop('disabled',true);
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

        $(document).on( 'heartbeat-send', function( e, data ) {
            var send = {};
            send.post_id = '<?php echo $postid; ?>';
            data['pwtc-refresh-post-lock'] = send;
        });

        $(document).on( 'heartbeat-tick', function( e, data ) {
            if ( data['pwtc-refresh-post-lock'] ) {
                var received = data['pwtc-refresh-post-lock'];
                if ( received.lock_error ) {
                    show_warning('You can no longer edit this post. ' + received.lock_error.text);
                    $('#pwtc-mapdb-leader-edit-ride-div button[type="submit"]').prop('disabled',true);
                } 
                else if ( received.new_lock ) {
                }
            }
        });

        wp.heartbeat.interval( 15 );

    });
</script>
<div id='pwtc-mapdb-leader-edit-ride-div'>
    <?php echo $return_to_ride; ?>
    <div class="callout">
        <form method="POST" novalidate>
            <?php wp_nonce_field('ride-leader-edit-form', 'nonce_field'); ?>
            <div class="row column">
                <label>Ride Title
                    <input type="text" name="title" value="<?php echo esc_attr($title); ?>" <?php echo $edit_title ? '': 'readonly'; ?>/>
                    <input type="hidden" name="postid" value="<?php echo $postid; ?>"/>
                </label>
                <p class="help-text">Use creative names as they are more enticing to riders.  Weekly repeating rides can start the title with the day of the week but this isn’t required.  All ride names must start with a letter or number in order to be entered in the Mileage Database. </p>
            </div>
            <div class="row">
                <div class="small-12 medium-6 columns">
                    <label>Departure Time
                        <input type="time" name="ride_time" value="<?php echo $ride_time; ?>" <?php echo $edit_date ? '': 'readonly'; ?>/>	
                    </label>
                    <p class="help-text">To set the time, enter hours then minutes then AM or PM. For example, "10:00 AM".</p>
                </div>
            </div>
            <div class="row column">
                <label>Ride Description
                    <textarea name="description" rows="10"><?php echo $description; ?></textarea>
                </label>
		            <p class="help-text">Describe the ride in a way that helps people find rides that are appropriate for them, and provide any details that help manage riders&#39; expectations.  Provide information about planned stops for refreshments. Do not include any information that appears elsewhere on the ride page to avoid conflicting information and keep the description shorter and &#34;scannable&#34;.</p>
            </div>
            <div class="row column">
                <label>Start Location Comment
                    <input type="text" name="start_location_comment" value="<?php echo esc_attr($start_location_comment); ?>"/>
                </label>
		            <p class="help-text">Use this comment to provide additional instruction about the start location.</p>
            </div>
    	      <div class="row column errmsg"></div>
            <div class="row column clearfix">
                <div class="button-group float-left">
                    <input class="dark button" name="draft" value="Update" type="submit"/>
                </div>
            </div>
        </form>
    </div>
</div>
<?php 
