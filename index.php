<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Cross-Stitch Generator</title>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="style.css">
	<script>
	//on file change
	$(document).on('change', ':file', function() {
		var label = $(this).val().replace(/\\/g, '/').replace(/.*\//, '');//make filename
		$("#inputValue").val(label);//show it in a text field
	});

	//on text input change
	$(document).ready(function() {
		$('#inputValue').on('input',function() {
			reset($('#fileToUpload'));//delete file from form
		});
		
		$("#uploadButton").click(function() {
			$('.loading').fadeIn(500);
		});
	});

	window.reset = function(e) {
		e.wrap('<form>').closest('form').get(0).reset();
		e.unwrap();
	}
	</script>
</head>
<body>

	<div class="bg-darkGrey jumbotron text-center">
		<h1>Cross-Stitch Generator</h1>
		<p>Generate your own Cross-Stitch pattern!</p>
	</div>
	
	<div class="container text-center">
		<div class="row">
			<div class="colPaddings col-lg-6 col-sm-6 center-block">
				<form id="uploadForm" class="form-inline input-group" role="form" action="upload.php" method="post" enctype="multipart/form-data">
					<label class="input-group-btn">
						<span class="btn btn-primary">
							<span class="glyphicon glyphicon-folder-open"></span>Select a File<input type="file" id="fileToUpload" name="fileToUpload" style="display: none;">
						</span>
					</label>
					<input type="text" name="inputValue" id="inputValue" class="form-control" placeholder="... or paste URL here">
					<label class="input-group-btn">
						<button type="submit" name="submit" id="uploadButton" class="btn btn-primary">Upload Image<span class="glyphicon glyphicon-cloud-upload"></span></button>
					</label>
				</form>
			</div>
		</div>
		<?php
			if(isset($_GET['err'])){
				echo "<h4 class='text-danger'>";
				switch($_GET['err']) {
					case 1:
						echo "File is not an image.";
						break;
					case 2:
						echo "Sorry, your file is too large.";
						break;
					case 3:
						echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
						break;
				}
				echo "</h4>";
			}
		?>
	</div>
	
	<div class="loading">
		<div class="cssload-container">
			<div class="cssload-zenith"></div>
		</div>
	</div>
	
</body>
</html>