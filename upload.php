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
	<script src="html2canvas.js"></script>
	<script>
		$(document).ready(function(){
			createImages();
			$("#generate").on("click", function(){
				$('.loading').fadeIn(500);
				createImages();
			});
		});

		function createImages() {
			$.ajax({
				type: "POST",
				url: "createImages.php",
				dataType: 'text',
				data: {
					squareSize: $("input[name='squareSize']:checked").val(),
					colorsAmount: $("input[name='colorsAmount']").val(),
					target_file: $("input[name='target_file']").val(),
					methodName: "createImages",
				},
				success: function(data) {
					dataArray = $.parseJSON(data);
					$('#legend').html('');
					$('#simulation').attr('src','data:image/png;base64,'+dataArray.simulation);
					$('#pattern').attr('src','data:image/png;base64,'+dataArray.pattern);

					$('#colAmount').html('<strong>'+dataArray.colors+'</strong> colors');
					$('#stitchesAmount').html('<strong>'+dataArray.stitchesAmount+'</strong> stitches');
					$('#executionTime').html('Generated in <strong>'+dataArray.executionTime.toFixed(2)+'s</strong>');
					
					//for pdf
					$('#simulationHidden').val(dataArray.simulation);
					$('#patternHidden').val(dataArray.pattern);		
					$('#patternSquareHidden').val(dataArray.patternSquare);		
					
					
					var i = 1;
					jQuery.each(dataArray.patternLegend, function(index, item) {
						r = (item.color >> 16) & 0xFF;
						g = (item.color >> 8) & 0xFF;
						b = item.color & 0xFF;
						$('#legend').append('<tr><td>'+i+'.</td><td><img class="legendSquare" src="data:image/png;base64,'+item.image+'"></td><td class="vert-align"><div class="legendSquare" style="background-color: rgb('+r+','+g+','+b+'); width: '+dataArray.legendSquare+'px; height: '+dataArray.legendSquare+'px;"></div></td><td>'+item.ID+'</td><td>'+item.name+'</td></tr>');
						i++;
					});
					
					
					html2canvas($('.legendView'), {
						onrendered: function(canvas) {
							$('#patternLegendHidden').val(canvas.toDataURL());
						}
					});
					
					$('.loading').fadeOut(500);
				}
			});
		}
	</script>
</head>
<body>
	<div class="bg-darkGrey jumbotron text-center">
		<h1>Cross-Stitch Generator</h1>
		<p>Generate your own Cross-Stitch pattern!</p>
	</div>

	<div class="loading" style="display: block;">
		<div class="cssload-container">
			<div class="cssload-zenith"></div>
		</div>
	</div>
	
	<?php
	$imageFileType = pathinfo(basename($_FILES["fileToUpload"]["name"]),PATHINFO_EXTENSION);
	
	//if image is uploaded via URL
	if($imageFileType=="" && $_POST["inputValue"]!="") {
		$imageSrc = $_POST["inputValue"];
		$imageFileType = pathinfo(basename($_FILES["fileToUpload"]["tmp_name"]),PATHINFO_EXTENSION);
		$imageSize = get_headers($_POST["inputValue"],1)['Content-Length'];
	} else {
		$imageSrc = $_FILES["fileToUpload"]["tmp_name"];
		$imageSize = $_FILES["fileToUpload"]["size"];
	}
	
	$error = 0;
	
	// Check if image file is a actual image or fake image
	if(isset($_POST["submit"])) {
		$check = getimagesize($imageSrc);
		if($check == false)
			$error = 1;
	}

	// Check file size
	define('MB', 1048576);
	if($error == 0 && $imageSize > 5*MB)
		$error = 2;

	if($error != 0)
		header("location: index.php?err=".$error);
	else {
		$imageData = file_get_contents($imageSrc);
		$base64Source = base64_encode($imageData);
		
		if(isset($_POST["squareSize"]))
			$squareSize = $_POST["squareSize"];
		else
			$squareSize = 12;//start value
		
		if(isset($_POST["colorsAmount"]))
			$colorsAmount = $_POST["colorsAmount"];
		else
			$colorsAmount = 50;//start value
		
		echo '<div class="container-fluid">';
			echo '<div class="row">';
				echo '<div class="colPaddings col-sm-3 bg-lightGrey">';
					echo '<h2>Pattern settings:</h2>';
					
					echo '<h4>Stitch size:</h4>';
					echo '<div class="squareSizeSelect">';
						for($i=32; $i>16; $i-=4) {
							if($i==$squareSize)
								$checked = ' checked="checked"';
							else
								$checked = '';
							echo '<input'.$checked.' id="squareSize'.$i.'" type="radio" name="squareSize" value="'.$i.'" /><label class="labelSquareSizeSelect" for="squareSize'.$i.'" style="width: '.$i.'px; height: '.$i.'px;"></label>';
						}
						for($i=16; $i>8; $i-=2) {
							if($i==$squareSize)
								$checked = ' checked="checked"';
							else
								$checked = '';
							echo '<input'.$checked.' id="squareSize'.$i.'" type="radio" name="squareSize" value="'.$i.'" /><label class="labelSquareSizeSelect" for="squareSize'.$i.'" style="width: '.$i.'px; height: '.$i.'px;"></label>';
						}
						for($i=8; $i>4; $i--) {
							if($i==$squareSize)
								$checked = ' checked="checked"';
							else
								$checked = '';
							echo '<input'.$checked.' id="squareSize'.$i.'" type="radio" name="squareSize" value="'.$i.'" /><label class="labelSquareSizeSelect" for="squareSize'.$i.'" style="width: '.$i.'px; height: '.$i.'px;"></label>';
						}
					echo '</div>';
					
					echo '<h4>Colors amount:</h4>';
					echo '<input type="range" name="colorsAmount" min="3" max="256" value="'.$colorsAmount.'">';
					
					echo '<input type="hidden" name="target_file" value="'.$base64Source.'">';
					
					echo '<input type="button" class="btn btn-primary btn-lg center-block" value="Generate" id="generate">';
					
					echo '<br><br><h3 id="colAmount"></h3>';
					echo '<h3 id="stitchesAmount"></h3>';
					echo '<h3 id="executionTime"></h3>';
					echo '<h3>Image size: <strong>'.round($imageSize / MB, 2).'MB</strong></h3>';
					
					echo '<form action="pdf.php" method="post">';
					echo '<input type="hidden" id="simulationHidden" name="simulation">';
					echo '<input type="hidden" id="patternHidden" name="pattern">';
					echo '<input type="hidden" id="patternSquareHidden" name="patternSquare">';
					echo '<input type="hidden" id="patternLegendHidden" name="patternLegend">';
					echo '<h3><button type="submit" class="btn btn-default center-block">Download PDF <span class="glyphicon glyphicon-download-alt"></span></button></h3></form>';
				echo '</div>';
				echo '<div class="colPaddings col-sm-9">';
					echo '<h2 class="text-center">Your image:</h2>';
					echo "<img src='data:image/png;base64,".$base64Source."' alt='img' id='image' class='img-responsive center-block'>";
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}
	?>
	<div class="containerPaddings container-fluid text-center bg-grey">
		<h2>Cross-Stitch simulation:</h2>
		<img id="simulation" class="img-responsive center-block">
	</div>
	
	<div class="containerPaddings container-fluid text-center">
		<h2>Cross-Stitch pattern:</h2>
		<img id="pattern" class="img-responsive center-block">
	</div>
	
	<div class="containerPaddings container-fluid text-center bg-grey">
		<h2>DMC colors table:</h2>
		<div class="col-sm-6 col-centered legendView">
			<div class="table-responsive">
				<table class="table table-condensed table-bordered">
					<thead>
						<tr>
							<th class="text-center"></th>
							<th class="text-center">Icon</th>
							<th class="text-center">Color</th>
							<th class="text-center">ID</th>
							<th class="text-center">Name</th>
						</tr>
					</thead>
					<tbody id="legend" class="text-center">
					</tbody>
				</table>
			</div>
		</div>
		
		<footer class="text-right"><small>&#169 Copyright <span id="year"></span> <a href="http://kamilpodlasek.pl/" target="_blank">Kamil Podlasek</a></small></footer>
		<script>
			$("#year").text(new Date().getFullYear());
		</script>

	</div>
</body>
</html>