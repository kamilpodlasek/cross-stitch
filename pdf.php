<?php
require('fpdf.php');

$simulationBase64 = $_POST['simulation'];
$patternBase64 = $_POST['pattern'];
$patternLegendBase64 = preg_replace('#^data:image/[^;]+;base64,#', '', $_POST['patternLegend']);
$patternSquare = $_POST['patternSquare'];

$simulation = base64_decode($simulationBase64);
$pattern = base64_decode($patternBase64);
$patternLegend = base64_decode($patternLegendBase64);



//scaling simulation to full pdf width
$simulationGD = imagecreatefromstring($simulation);

$simulationWidth = imagesx($simulationGD);
$simulationHeight = imagesy($simulationGD);

$newWidth = 718;
$ratio = $newWidth / $simulationWidth;
$newHeight = $simulationHeight * $ratio;

if($newHeight > 910) {
	$newHeight = 910;
	$ratio = $newHeight / $simulationHeight;
	$newWidth = $simulationWidth * $ratio;
}

$simulationGD = imagescale($simulationGD , $newWidth, $newHeight);

ob_start();
imagepng($simulationGD, NULL, 9);
$simulation = ob_get_contents();
ob_end_clean();

imagedestroy($simulationGD);



//dividing pattern
$patternGD = imagecreatefromstring($pattern);

$patternWidth = imagesx($patternGD);
$patternHeight = imagesy($patternGD);

$squaresWidth = 64;
$squaresHeight = 83;

$partWidth = $patternSquare*$squaresWidth + $squaresWidth + 1;
$partsAmountWidth = ceil($patternWidth / $partWidth);

$partHeight = $patternSquare*$squaresHeight + $squaresHeight + 1;
$partsAmountHeight = ceil($patternHeight / $partHeight);

$partsAmount = $partsAmountWidth * $partsAmountHeight;

$parts = array();
//for each part
for($y=1; $y<=$partsAmountHeight; $y++) {
	$heightStart = ($y-1) * $partHeight;

	if($y!=1)
		$heightStart -= ($y - 1);

	if($y!=$partsAmountHeight)
		$currentPartHeight = $partHeight;
	else
		$currentPartHeight = $patternHeight%$partHeight + ($y - 1);

	for($x=1; $x<=$partsAmountWidth; $x++) {
		$widthStart = ($x-1) * $partWidth;

		if($x!=1)
			$widthStart -= ($x - 1);
		
		if($x!=$partsAmountWidth)
			$currentPartWidth = $partWidth;
		else
			$currentPartWidth = $patternWidth%$partWidth + ($x - 1);
		
		$part = imagecreatetruecolor($currentPartWidth, $currentPartHeight);

		imagecopy($part, $patternGD, 0, 0, $widthStart, $heightStart, $patternWidth, $patternHeight);
		
		ob_start();
		imagepng($part, NULL, 9);
		$parts[] = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($part);
	}
}
imagedestroy($patternGD);




//dividing legend
$patternLegendGD = imagecreatefromstring($patternLegend);

$patternLegendWidth = imagesx($patternLegendGD);
$patternLegendHeight = imagesy($patternLegendGD);

$partHeight = 888;

$legendPartsAmount = ceil($patternLegendHeight / $partHeight);

$legendParts = array();
//for each part
for($x=1; $x<=$legendPartsAmount; $x++) {
	if($x!=$legendPartsAmount)
		$currentPartHeight = $partHeight;
	else
		$currentPartHeight = $patternLegendHeight % $partHeight;

	$part = imagecreatetruecolor($patternLegendWidth, $currentPartHeight);
	$whiteBackground = imagecolorallocate($part, 255, 255, 255);
	imagefill($part, 0, 0, $whiteBackground);
	
	$heightStart = ($x-1) * $partHeight;

	imagecopy($part, $patternLegendGD, 0, 0, 0, $heightStart, $patternLegendWidth, $patternLegendHeight);
	
	ob_start();
	imagepng($part, NULL, 9);
	$legendParts[] = ob_get_contents();
	ob_end_clean();
	
	imagedestroy($part);
}

imagedestroy($patternLegendGD);




class PDF extends FPDF {
	function Header() {
		$this->SetFont('Helvetica','',16);
		$this->Cell(0, 8, "Cross-Stitch Generator", 0, 0, 'C');
		$this->Ln(16);
	}
}

$pdf = new PDF();
$pdf->AddPage();

$simulationTemp = substr(md5(rand()), 0, 7).".png";
if(file_put_contents($simulationTemp, $simulation) !== false) {
	$pdf->SetFont('Helvetica','',14);
	$pdf->Write(5,'Simulation:');
	$pdf->Ln(8);
	$pdf->Image($simulationTemp);
	$pdf->Ln(12);
	unlink($simulationTemp);
}

$pdf->AddPage();
$pdf->Write(5,'DMC colors table:');
$pdf->Ln(8);

foreach($legendParts as $part) {
	$partTemp = substr(md5(rand()), 0, 7).".png";
	if(file_put_contents($partTemp, $part) !== false) {
		$pdf->Image($partTemp);
		unlink($partTemp);
	}
}

$partsAmount = count($parts);
$multipleRows = ($partsAmount > $partsAmountWidth ? true : false);

foreach($parts as $key => $part) {
	$pdf->AddPage();
	$partTemp = substr(md5(rand()), 0, 7).".png";
	if(file_put_contents($partTemp, $part) !== false) {
		$partNo = $key+1;

		if($multipleRows) {
			$rowNo = floor($key / $partsAmountWidth) + 1;
			if($partNo > $partsAmountWidth) {
				$partNo -= ($rowNo-1) * $partsAmountWidth;
			}
			$pdf->Write(5,'Part '.$partNo.'/'.$partsAmountWidth.' in row '.$rowNo.'/'.$partsAmountHeight);
		} else
			$pdf->Write(5,'Part '.$partNo.'/'.$partsAmount);
			
		$pdf->Ln(8);
		$pdf->Image($partTemp);
		unlink($partTemp);
		$pdf->Ln(8);
	}
}

$pdf->Output('cross-stitch'.date('YmdHis').'.pdf','D');
?>