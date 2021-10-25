<style>
    .indicate-error {
        border-color: #900 !important;
        background-color: #FDD !important;
    }
</style>
<script type="text/javascript">
    jQuery(document).ready(function($) { 

        function clear_lookup_msg() {
            $('#pwtc-mapdb-sched-template-div .lookup-errmsg').empty();
        }

        function show_lookup_waiting() {
            $('#pwtc-mapdb-sched-template-div .lookup-errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');
        }

        function show_lookup_warning(msg) {
            $('#pwtc-mapdb-sched-template-div .lookup-errmsg').html('<div class="callout small warning"><p>' + msg + '</p></div>');
        }

        function clear_submit_msg() {
            $('#pwtc-mapdb-sched-template-div form .errmsg').empty();
        }

        function show_submit_waiting() {
            $('#pwtc-mapdb-sched-template-div form .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');
        }

        function show_submit_warning(msg) {
            $('#pwtc-mapdb-sched-template-div form .errmsg').html('<div class="callout small warning"><p>' + msg + '</p></div>');
        }

        function hide_form() {
            $('#pwtc-mapdb-sched-template-div form').hide();
            $('#pwtc-mapdb-sched-template-div .schedule-dates-fst div').remove();
            $('#pwtc-mapdb-sched-template-div input[name="ride_time"]').val('');
            clear_submit_msg();
        }

        function dates_lookup_cb(response) {
            var res;
            try {
                res = JSON.parse(response);
            }
            catch (e) {
                show_lookup_warning(e.message);
                return;
            }
            if (res.error) {
                show_lookup_warning(res.error);
            }
            else {
                clear_lookup_msg();
                $('#pwtc-mapdb-sched-template-div .schedule-dates-fst div').remove();
                res.dates.forEach(function(item) {
                    $('#pwtc-mapdb-sched-template-div .schedule-dates-fst').append(
                        '<div><input type="checkbox" name="schedule_dates[]" value="' + item.date + '" id="' + item.date + '" checked><label for="' + item.date + '">' + item.prettydate + '</label></div>');
                });
                $('#pwtc-mapdb-sched-template-div input[type="checkbox"]').change(function() {
                    is_dirty = true;
                    $('#pwtc-mapdb-sched-template-div .schedule-dates-fst').removeClass('indicate-error');
                });
                $('#pwtc-mapdb-sched-template-div form').show();
            }
        }

        $('#pwtc-mapdb-sched-template-div input[name="repeat_every"]').change(function() {
            $('#pwtc-mapdb-sched-template-div input[name="from_date"]').val('');
            $('#pwtc-mapdb-sched-template-div input[name="to_date"]').val('');
            $('#pwtc-mapdb-sched-template-div input[name="to_date"]').prop('disabled',true);
            hide_form();
        });

        $('#pwtc-mapdb-sched-template-div input[name="from_date"]').change(function() {
            var date = $(this).val();
            var datergx = /^\d{4}-\d{2}-\d{2}$/;
            if (datergx.test(date)) {
                $('#pwtc-mapdb-sched-template-div input[name="to_date"]').val('');
                $('#pwtc-mapdb-sched-template-div input[name="to_date"]').attr('min', $(this).val());
                $('#pwtc-mapdb-sched-template-div input[name="to_date"]').prop('disabled',false);
            }
            else {
                $('#pwtc-mapdb-sched-template-div input[name="to_date"]').val('');
                $('#pwtc-mapdb-sched-template-div input[name="to_date"]').prop('disabled',true);
            }
            hide_form();
        });

        $('#pwtc-mapdb-sched-template-div input[name="to_date"]').change(function() {
            var to_date = $(this).val();
            var datergx = /^\d{4}-\d{2}-\d{2}$/;
            if (datergx.test(to_date)) {
                var repeat = $('#pwtc-mapdb-sched-template-div input[name="repeat_every"]:checked').val();
                var from_date = $('#pwtc-mapdb-sched-template-div input[name="from_date"]').val();
                var action = "<?php echo admin_url('admin-ajax.php'); ?>";
                var data = {
                    'action': 'pwtc_mapdb_lookup_schedule_dates',
                    'repeat': repeat,
                    'from_date': from_date,
                    'to_date': to_date
                };
                $.post(action, data, dates_lookup_cb);
                show_lookup_waiting();
            }
            hide_form();   
       });

        $('#pwtc-mapdb-sched-template-div input[name="ride_time"]').change(function() {
            is_dirty = true;
            $(this).removeClass('indicate-error');
        });

        $('#pwtc-mapdb-sched-template-div form').on('submit', function(evt) {
            $('#pwtc-mapdb-sched-template-div input').removeClass('indicate-error');
            $('#pwtc-mapdb-sched-template-div .schedule-dates-fst').removeClass('indicate-error');

            var dates_empty = $('#pwtc-mapdb-sched-template-div input[name="schedule_dates[]"]:checked').length == 0;
            if (dates_empty) {
                show_submit_warning('You must choose at least one date to schedule.');
                $('#pwtc-mapdb-sched-template-div .schedule-dates-fst').addClass('indicate-error');
                evt.preventDefault();
                return;						
            }

            var time = $('#pwtc-mapdb-sched-template-div input[name="ride_time"]').val();
            if (time.length == 0) {
                show_submit_warning('The <strong>departure time</strong> must be set.');
                $('#pwtc-mapdb-sched-template-div input[name="ride_time"]').addClass('indicate-error');
                evt.preventDefault();
                return;			
            }
            var timergx = /^\d{2}:\d{2}$/;
            if (!timergx.test(time)) {
                show_submit_warning('The <strong>departure time</strong> format is invalid.');
                $('#pwtc-mapdb-sched-template-div input[name="ride_time"]').addClass('indicate-error');
                evt.preventDefault();
                return;					
            }

            is_dirty = false;
            show_submit_waiting();
            $('#pwtc-mapdb-sched-template-div button[type="submit"]').prop('disabled',true);
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

    });
</script>
<div id='pwtc-mapdb-sched-template-div'>
    <div class="callout">
        <div class="row">
            <div class="small-12 medium-4 columns">
                <fieldset>
                    <legend>Repeat Every</legend>
                    <input type="radio" name="repeat_every" value="week" id="repeat-every-week" checked><label for="repeat-every-week">Week</label>
                    <input type="radio" name="repeat_every" value="day" id="repeat-every-day"><label for="repeat-every-day">Day</label>
                </fieldset>
            </div>
            <div class="small-12 medium-4 columns">
                <label>From Date
                    <input type="date" name="from_date" value="" min="<?php echo $min_date; ?>">
                </label>
                <p class="help-text">TBD...</p>
            </div>
            <div class="small-12 medium-4 columns">
                <label>To Date
                    <input type="date" name="to_date" value="" disabled>
                </label>
                <p class="help-text">TBD...</p>
            </div>
        </div>
        <div class="row column lookup-errmsg"></div>
        <form style="display:none" method="POST" novalidate>
            <?php wp_nonce_field('schedule-template-form', 'nonce_field'); ?>
            <div class="row">
                <div class="small-12 medium-4 columns">
                    <fieldset class="schedule-dates-fst">
                        <legend>Schedule These Dates</legend>
                    </fieldset>
                    <p class="help-text">Uncheck a date to not schedule it.</p>
                </div>
            </div>
            <div class="row">
                <div class="small-12 medium-4 columns">
                    <label>Departure Time
                        <input type="time" name="ride_time" value="">	
                    </label>
                    <p class="help-text">To set the time, enter hours then minutes then AM or PM. For example, "10:00 AM".</p>
                </div>
            </div>
            <div class="row column errmsg"></div>
            <div class="row column clearfix">
                <button class="dark button float-left" type="submit">Schedule</button>
            </div>
        </form>
    </div>
</div>
<?php 
