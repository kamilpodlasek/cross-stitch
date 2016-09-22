<?php
include 'DMClist.php';
//require('fpdf.php');

if(isset($_POST['methodName']))
	if($_POST['methodName'] == "createImages")
		createImages($_POST['squareSize'], $_POST['colorsAmount'], $_POST['target_file']);

function createImages($squareSize, $colorsAmount, $target_file) {
	global $DMClist;
	
	$data = base64_decode($target_file);
	$source = imagecreatefromstring($data);
	
	$source_width = imagesx($source);
	$source_height = imagesy($source);
	
	$newWidth = $source_width - $source_width%$squareSize;//deleting redundant pixels
	$newHeight = $source_height - $source_height%$squareSize;
	
	$squaresWidth = $newWidth/$squareSize;
	$squaresHeight = $newHeight/$squareSize;
	
	
	//creating scaled copy of input image
	$sourceResized = imagecreatetruecolor($newWidth, $newHeight);
	//making white background for images with transparency
	$whiteBackground = imagecolorallocate($sourceResized, 255, 255, 255);
	imagefill($sourceResized, 0, 0, $whiteBackground);
	imagecopyresampled($sourceResized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $source_width, $source_height);
	imagedestroy($source);
	
	
	//input image turned into squares
	$checkeredInput = imagecreatetruecolor($newWidth, $newHeight);
	
	for($row = 0; $row<$squaresHeight; $row++) {
		for($col = 0; $col<$squaresWidth; $col++) {
			$square = @imagecreatetruecolor($squareSize, $squareSize);
			imagecopyresized($square, $sourceResized, 0, 0, $col * $squareSize, $row * $squareSize, $squareSize, $squareSize, $squareSize, $squareSize);
			
			$scaled = @imagecreatetruecolor(1, 1);
			imagecopyresampled($scaled, $square, 0, 0, 0, 0, 1, 1, $squareSize, $squareSize);
			$meanColor = imagecolorat($scaled, 0, 0);
			imagedestroy($scaled);
			
			//filling checkeredInput
			$square = @imagecreatetruecolor($squareSize, $squareSize);
			imagefill($square, 0, 0, $meanColor);
			imagecopymerge($checkeredInput, $square, $col * $squareSize, $row * $squareSize, 0, 0, $squareSize, $squareSize, 100);
			
			imagedestroy($square);
		}
	}
	
	ImageTrueColorToPalette($checkeredInput, false, $colorsAmount);
	ImageColorMatch($sourceResized, $checkeredInput);//improving colors

	imagedestroy($sourceResized);
	
	
	//creating colors array
	$colors = array();
	for($row = 0; $row<$squaresHeight; $row++) {
		for($col = 0; $col<$squaresWidth; $col++) {
			$square = @imagecreatetruecolor($squareSize, $squareSize);
			imagecopyresized($square, $checkeredInput, 0, 0, $col * $squareSize, $row * $squareSize, $squareSize, $squareSize, $squareSize, $squareSize);
			$colors[] = imagecolorat($square, 0, 0);
			
			imagedestroy($square);
		}
	}
	
	imagedestroy($checkeredInput);
	
	
	//changing colors to DMC
	$specifiedColors = array_keys(array_count_values($colors));
	$usedDMC = array();
	
	foreach($specifiedColors as $key => $color) {
		$r = ($color >> 16) & 0xFF;
		$g = ($color >> 8) & 0xFF;
		$b = $color & 0xFF;
		
		$distArr = array();
		
		foreach($DMClist as $DMCkey => $DMCcolor) {
			$DMCr = $DMCcolor[2];
			$DMCg = $DMCcolor[3];
			$DMCb = $DMCcolor[4];
			$distArr[$DMCkey] = sqrt(pow($r - $DMCr, 2) + pow($g - $DMCg, 2) + pow($b - $DMCb, 2));
		}
		
		asort($distArr);
		$DMCcolorKey = key($distArr);
		
		$newColors = array();
		foreach($colors as $key => $colorOnImage) {
			if($colorOnImage == $color)
				$newColors[] = (($DMClist[$DMCcolorKey][2])<<16)|(($DMClist[$DMCcolorKey][3])<<8)|($DMClist[$DMCcolorKey][4]);
			else
				$newColors[] = $colorOnImage;
		}
		$colors = $newColors;
		
		$usedDMC[] = $DMClist[$DMCcolorKey];
	}
	
	
	//simulation of cross-stich
	$simulationSquare = 10;
	$simulation = imagecreatetruecolor($squaresWidth*$simulationSquare, $squaresHeight*$simulationSquare);
	
	$i=0;
	for($row = 0; $row<$squaresHeight; $row++) {
		for($col = 0; $col<$squaresWidth; $col++) {
			$r = ($colors[$i] >> 16) & 0xFF;
			$g = ($colors[$i] >> 8) & 0xFF;
			$b = $colors[$i] & 0xFF;
			
			$square = @imagecreatetruecolor($simulationSquare, $simulationSquare);
			//filling simulation
			$square = colorizeBasedOnAlphaChannnel("icons/cross.png", $r, $g, $b, $simulationSquare);
			imagecopymerge($simulation, $square, $col * $simulationSquare, $row * $simulationSquare, 0, 0, $simulationSquare, $simulationSquare, 100);

			imagedestroy($square);
			$i++;
		}
	}
	
	ob_start();
	imagepng($simulation, NULL, 9);
	$image_data = ob_get_contents();
	ob_end_clean();
	
	imagedestroy($simulation);
	
	$base64Simulation = base64_encode($image_data);
	
	
	//icons for legend
	$specifiedColors = array_keys(array_count_values($colors));
	$specifiedColors = sortColorsByColor($specifiedColors);
	
	$icons = array();
	$prevColor1 = 0;
	$prevColor2 = 0;
	$prevColor3 = 0;
	$icons[0] = 0;
	foreach($specifiedColors as $key => $color) {
		if(!isset($specifiedColors[18]) || $key<18){//if there is enough icons
			do{
				$icon = rand(1, 18);
			}while(in_array($icon, $icons));//random without repetitions
		} else {
			do{
				$icon = rand(1, 18);
			}while($icons[$prevColor1]==$icon || $icons[$prevColor2]==$icon || $icons[$prevColor3]==$icon);//random until icon will be different than for three previous colors
		}
		$icons[$color] = $icon;
		
		$prevColor1 = $prevColor2;
		$prevColor2 = $prevColor3;
		$prevColor3 = $color;
	}
	
	
	//legend for pattern
	$patternLegend = array();
	$legendSquare = 12;
	foreach($specifiedColors as $key => $color) {
		$square = @imagecreatetruecolor($legendSquare, $legendSquare);
		imagefill($square, 0, 0, $color);
		
		$icon = @imagecreatefrompng("icons/".$icons[$color].".png");
		$iconResized = imagecreatetruecolor($legendSquare, $legendSquare);
		imagecopyresampled($iconResized, $icon, 0, 0, 0, 0, $legendSquare, $legendSquare, 15, 15);
		imagedestroy($icon);
		
		$r = ($color >> 16) & 0xFF;
		$g = ($color >> 8) & 0xFF;
		$b = $color & 0xFF;

		if(lightness($r, $g, $b) < 0.25)//if color is too dark
			$colorTransparent = imagecolorallocate($iconResized, 0, 0, 0);//black icon dissapears
		else
			$colorTransparent = imagecolorallocate($iconResized, 255, 255, 255);//white background dissapears 
		
		imagecolortransparent($iconResized, $colorTransparent);
		
		imagecopymerge($square, $iconResized, 0, 0, 0, 0, $legendSquare, $legendSquare, 75);
		
		imagedestroy($iconResized);
		
		ob_start();
		imagepng($square, NULL, 9);
		$image_data = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($square);
		
		//color ID and name
		foreach($usedDMC as $DMCkey => $DMCcolor) {
			if($r == $DMCcolor[2] && $g == $DMCcolor[3] && $b == $DMCcolor[4]) {
				$ID = $DMCcolor[0];
				$name = $DMCcolor[1];
			}
		}
		
		$patternLegend[] = array('color'=>$color, 'image'=>base64_encode($image_data), 'ID'=>$ID, 'name'=>$name);
	}
	
	$IDs = array();
	foreach($patternLegend as $key => $row) {
		$IDs[$key] = $row['ID'];
	}
	array_multisort($IDs, SORT_ASC, $patternLegend);
	
	
	//pattern of cross-stich
	$patternSquare = 10;
	$pattern = imagecreatetruecolor($squaresWidth*$patternSquare + $squaresWidth + 1, $squaresHeight*$patternSquare + $squaresHeight + 1);
	
	$i=0;
	for($row = 0; $row<$squaresHeight; $row++) {
		for($col = 0; $col<$squaresWidth; $col++) {
			//filling pattern
			$square = @imagecreatetruecolor($patternSquare, $patternSquare);
			imagefill($square, 0, 0, $colors[$i]);
			
			$icon = @imagecreatefrompng("icons/".$icons[$colors[$i]].".png");
			$iconResized = imagecreatetruecolor($patternSquare, $patternSquare);
			imagecopyresampled($iconResized, $icon, 0, 0, 0, 0, $patternSquare, $patternSquare, 15, 15);
			imagedestroy($icon);
			
			$r = ($colors[$i] >> 16) & 0xFF;
			$g = ($colors[$i] >> 8) & 0xFF;
			$b = $colors[$i] & 0xFF;

			if(lightness($r, $g, $b) < 0.25)//if color is too dark
				$colorTransparent = imagecolorallocate($iconResized, 0, 0, 0);//black icon dissapears
			else
				$colorTransparent = imagecolorallocate($iconResized, 255, 255, 255);//white background dissapears 
			
			imagecolortransparent($iconResized, $colorTransparent);
			
			imagecopymerge($square, $iconResized, 0, 0, 0, 0, $patternSquare, $patternSquare, 75);
			
			imagedestroy($iconResized);
			
			imagecopymerge($pattern, $square, $col * $patternSquare + $col + 1, $row * $patternSquare + $row + 1, 0, 0, $patternSquare, $patternSquare, 100);

			imagedestroy($square);
			$i++;
		}
	}
	
	ob_start();
	imagepng($pattern, NULL, 9);
	$image_data = ob_get_contents();
	ob_end_clean();
	
	imagedestroy($pattern);
	
	$base64Pattern = base64_encode($image_data);

	$executionTime = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
	
	echo json_encode(array('stitchesAmount'=>$squaresHeight * $squaresWidth, 'legendSquare'=>$legendSquare, 'executionTime'=>$executionTime, 'squareSize'=>$squareSize, 'patternLegend'=>$patternLegend, 'patternSquare'=>$patternSquare, 'colors'=>sizeof(array_count_values($colors)), 'simulation'=>$base64Simulation, 'pattern'=>$base64Pattern));
}

function sortColorsByColor($rgblist) {
	$sort = array();
	foreach($rgblist as $rgb) {
		$hsl = rgbToHsl(($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF);
		$sort[] = $hsl['h'];
	}
	array_multisort($sort, SORT_ASC, $rgblist);
	return $rgblist;
}

function rgbToHsl($r, $g, $b) {
    $r /= 255; 
    $g /= 255; 
    $b /= 255;
    $max = max($r, $g, $b);
        $min = min($r, $g, $b);
    $h = 0;
    $s = 0;
    $l = ($max + $min) / 2;
 
    if($max == $min){
        $h = $s = 0; // achromatic
    }else{
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        switch($max){
            case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
            case $g: $h = ($b - $r) / $d + 2; break;
            case $b: $h = ($r - $g) / $d + 4; break;
        }
        $h /= 6;
    }
 
    return array('h'=>$h, 's'=>$s, 'l'=>$l);
}

function lightness($R = 255, $G = 255, $B = 255) {
	return (max($R, $G, $B) + min($R, $G, $B)) / 510.0; // HSL algorithm
}

function colorizeBasedOnAlphaChannnel($file, $targetR, $targetG, $targetB, $squareSize) {
	$im = imagecreatefrompng($file);

	$im_src = imagecreatetruecolor($squareSize, $squareSize);
	imagealphablending($im_src, false);
	imagesavealpha($im_src, true);
	imagecopyresampled($im_src, $im, 0, 0, 0, 0, $squareSize, $squareSize, 15, 15);

    $im_dst = imagecreatetruecolor($squareSize, $squareSize);
	imagecopyresampled($im_dst, $im, 0, 0, 0, 0, $squareSize, $squareSize, 15, 15);

    // Note this:
    // Let's reduce the number of colors in the image to ONE
    imagefilledrectangle($im_dst, 0, 0, $squareSize, $squareSize, 0xFFFFFF);

    for($x=0; $x<$squareSize; $x++) {
        for($y=0; $y<$squareSize; $y++) {

            $alpha = ( imagecolorat( $im_src, $x, $y ) >> 24 & 0xFF );

            $col = imagecolorallocatealpha( $im_dst,
                $targetR - (int) ( 1.0 / 255.0  * $alpha * (double) $targetR ),
                $targetG - (int) ( 1.0 / 255.0  * $alpha * (double) $targetG ),
                $targetB - (int) ( 1.0 / 255.0  * $alpha * (double) $targetB ),
                $alpha
                );

            if ( false === $col ) {
                die( 'sorry, out of colors...' );
            }

            imagesetpixel( $im_dst, $x, $y, $col );

        }

    }
    
	return $im_dst;
	
    imagedestroy($im_dst);
}
?>