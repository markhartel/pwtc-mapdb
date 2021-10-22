<style>
</style>
<script type="text/javascript">
    jQuery(document).ready(function($) { 
    });
</script>
<div id='pwtc-mapdb-sched-template-div'>
    <div class="callout">
        <div class="row">
            <div class="small-12 medium-3 columns">
                <label>Departure Time
                    <input type="time" name="ride_time" value="">	
                </label>
                <p class="help-text">To set the time, enter hours then minutes then AM or PM. For example, "10:00 AM".</p>
            </div>
            <div class="small-12 medium-3 columns">
                <fieldset>
                    <legend>Repeat Every</legend>
                    <input type="radio" name="repeat_every" value="week" id="repeat-every-week" checked><label for="repeat-every-week">Week</label>
                    <input type="radio" name="repeat_every" value="day" id="repeat-every-day"><label for="repeat-every-day">Day</label>
                </fieldset>
            </div>
            <div class="small-12 medium-3 columns">
                <label>From Date
                    <input type="date" name="from_date" value="">
                </label>
                <p class="help-text">TBD...</p>
            </div>
            <div class="small-12 medium-3 columns">
                <label>To Date
                    <input type="date" name="to_date" value="">
                </label>
                <p class="help-text">TBD...</p>
            </div>
        </div>
    </div>
</div>
<?php 
