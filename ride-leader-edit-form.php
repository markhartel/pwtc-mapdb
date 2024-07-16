<style>
</style>
<script type="text/javascript">
    jQuery(document).ready(function($) { 
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
