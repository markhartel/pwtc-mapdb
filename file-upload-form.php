<style>
    .indicate-error {
        border-color: #900 !important;
        background-color: #FDD !important;
	color: black !important;
    }
</style>
<script type="text/javascript">
    jQuery(document).ready(function($) { 

        function show_warning(msg) {
            $('#pwtc-mapdb-upload-file-div .errmsg').html('<div class="callout small warning"><p>' + msg + '</p></div>');
        }

        function show_waiting() {
            $('#pwtc-mapdb-upload-file-div .errmsg').html('<div class="callout small"><i class="fa fa-spinner fa-pulse"></i> please wait...</div>');
        }

        $('#pwtc-mapdb-upload-file-div form').on('keypress', function(evt) {
            var keyPressed = evt.keyCode || evt.which; 
            if (keyPressed === 13) { 
                evt.preventDefault(); 
                return false; 
            } 
        });

        $('#pwtc-mapdb-upload-file-div form .clipboard-btn').on('click', function(evt) {
            var url = $('#pwtc-mapdb-upload-file-div input[name="attachment_url"]')[0];
            url.select();
            document.execCommand("copy");
        });

        $('#pwtc-mapdb-upload-file-div input[name="title"]').on('input', function() {
            is_dirty = true;
	    $(this).removeClass('indicate-error');
        });

        $('#pwtc-mapdb-upload-file-div input[name="file_upload"]').on('change', function(e) {
            var fileName = '';
            if (e.target.value) {
		fileName = e.target.value.split('\\').pop();
            }
	    if (fileName) {
		$('#pwtc-mapdb-upload-file-div .file-upload-lbl').html('Upload File: ' + fileName);
            }
	    else {
		$('#pwtc-mapdb-upload-file-div .file-upload-lbl').html('Upload File');
            }
            is_dirty = true;
	    $('#pwtc-mapdb-upload-file-div .file-upload-lbl').removeClass('indicate-error');
        });

        $('#pwtc-mapdb-upload-file-div form').on('submit', function(evt) {
            $('#pwtc-mapdb-upload-file-div input').removeClass('indicate-error');
            $('#pwtc-mapdb-upload-file-div .file-upload-lbl').removeClass('indicate-error');

            if ($('#pwtc-mapdb-upload-file-div input[name="title"]').val().trim().length == 0) {
                show_warning('The <strong>attachment title</strong> cannot be blank.');
                $('#pwtc-mapdb-upload-file-div input[name="title"]').addClass('indicate-error');
                evt.preventDefault();
                return;
            }

            var attach_id = $('#pwtc-mapdb-upload-file-div input[name="attach_id"]').val().trim();
            var file = $('#pwtc-mapdb-upload-file-div input[name="file_upload"]').val().trim();
            if (file.length == 0 && attach_id == '0') {
                show_warning('An initial <strong>upload file</strong> must be selected.');
                $('#pwtc-mapdb-upload-file-div .file-upload-lbl').addClass('indicate-error');
                evt.preventDefault();
                return;
            }
            if (file.length > 0) {
                var elem = $('#pwtc-mapdb-upload-file-div input[name="file_upload"]')[0];
                if (elem.validity) {
                    if (!elem.validity.valid) {
                        show_warning('The <strong>upload file</strong> has an invalid format.');
                        $('#pwtc-mapdb-upload-file-div .file-upload-lbl').addClass('indicate-error');
                        evt.preventDefault();
                        return;                     
                    }
                }
            }

            is_dirty = false;
            show_waiting();
            $('#pwtc-mapdb-upload-file-div button[type="submit"]').prop('disabled',true);
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
<div id='pwtc-mapdb-upload-file-div'>
    <?php echo $return_to_page; ?>
    <?php if (!empty($operation)) { ?>
    <div class="callout small success">
        <?php if ($operation == 'insert') { ?>
        <p>The new file attachment was saved.</p>
        <?php } else if ($operation == 'update') { ?>
        <p>The file attachment was updated.</p>
        <?php } else if ($operation == 'update_upload') { ?>
        <p>The file attachment was updated and a new file uploaded.</p>
        <?php } ?>
    </div>
    <?php } ?>
    <div>
    <?php if ($attach_id != 0) { ?>
        <p>To modify this file attachment, fill out the form below and press the update button at the bottom of the form.</p>
    <?php } else { ?>
        <p>This is a new file attachment, fill out the form below and press the save button at the bottom of the form.</p>
    <?php } ?>
    </div>
    <div class="callout">
        <form method="POST" enctype="multipart/form-data" novalidate>
            <?php wp_nonce_field('file-upload-form', 'nonce_field'); ?>
            <div class="row column">
                <label>Attachment Title
                    <input type="text" name="title" value="<?php echo esc_attr($title); ?>"/>
                </label>
                <p class="help-text">When naming this attachment, use a title that is descriptive of the file being uploaded.</p>
            </div>
            <div class="row column">
                <input type="hidden" name="attach_id" value="<?php echo $attach_id; ?>"/> 
                <label>Uploaded File URL
            <?php if (!empty($attachment_url)) { ?>
                    <a class="clipboard-btn" title="Copy URL to clipboard."><i class="fa fa-clipboard"></i></a>
            <?php } ?>
                    <input type="text" name="attachment_url" value="<?php echo $attachment_url; ?>" readonly/>
                </label>
                <p class="help-text">You cannot edit the uploaded file URL directly, instead press the upload file button below to choose a new file to upload when this attachment is saved.</p>
            </div>
            <div class="row column">
                <label for="file-upload" class="dark button file-upload-lbl">Upload File</label>
                <input type="file" id="file-upload" class="show-for-sr" accept=".pdf" name="file_upload"/>
                <p class="help-text">When uploading a file, it must first exist on your desktop and be in the PDF format.</p>
            </div>
            <div class="row column errmsg"></div>
            <div class="row column clearfix">
            <?php if ($attach_id == 0) { ?>
                <button class="dark button float-left" type="submit">Save</button>
            <?php } else { ?>
                <button class="dark button float-left" type="submit">Update</button>
            <?php } ?>
        </form>
    </div>
</div>
<?php 
