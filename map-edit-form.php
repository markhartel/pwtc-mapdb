<script type="text/javascript">
    jQuery(document).ready(function($) { 

        $('#exampleFileUpload').on('change', function(e) {
            var fileName = '';
            if (e.target.value) {
				fileName = e.target.value.split('\\').pop();
            }
			if (fileName) {
				$('#pwtc-mapdb-edit-map-div form div').html(fileName);
            }
			else {
				$('#pwtc-mapdb-edit-map-div form div').html('');
            }
        });

    });
</script>
<div id='pwtc-mapdb-edit-map-div'>
    <form method="post" enctype="multipart/form-data">
        <label for="exampleFileUpload" class="dark button">Select File for Upload</label>
        <input type="file" id="exampleFileUpload" class="show-for-sr" multiple="false" name="map_file">
        <div></div>
        <input class="dark button" type="submit" name="upload_file" value="Upload File">
    </form>
</div>
<?php 
