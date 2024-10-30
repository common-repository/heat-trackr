<?php
function heat_trackr_getColorArray($numColors) {
	$colorArray = array();
	if ($numColors == 0) return $colorArray;

	$spectrumFile = dirname(__FILE__)."/images/colors.png";
	list($width, $height, $type, $attr) = getimagesize($spectrumFile);
	$im = imagecreatefrompng($spectrumFile);
	
	$upperLimit = round($height*0.1); // Blue
	$lowerLimit = round($height*0.9); // Red - start with this value
	// If $numColors == 1 make step = 0 to avoid divide by zero
	$step = $numColors == 1 ? 0 : ($lowerLimit - $upperLimit) / ($numColors - 1);

	$thisX = round($width/2);
	$thisY = $lowerLimit;
    for ($x = 0; $x < $numColors; $x++) {
		// Get the spectrum color from thisX and thisY position, use $step*$x to avoid rounding errors
		$rgb = imagecolorat($im, $thisX, ($thisY-$step*$x));
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;

		// Convert to hexadecimal
		$r = dechex($r);
		$g = dechex($g);
		$b = dechex($b);

		// Prepend '0' if only one digit value
		$r = strlen($r) == 1 ? '0' . $r : $r;
		$g = strlen($g) == 1 ? '0' . $g : $g;
		$b = strlen($b) == 1 ? '0' . $b : $b;
		$colorArray[] = $r.$g.$b;
	}

	return $colorArray;
}

class HeatmapDraw {
    private $pointsArray = array();
    private $imagepath = "";
    private $heatmap = "heatmap.jpg";
    private $mapImageWidth = 0;
    private $mapImageHeight = 0;
    private $dotsize = 32;           // default
    private $heatImage = null;       // GD image object for the final heatmap produced
    private $pointsholder = array(); // holds points data for overlays

	// input image width, image height & array of subarrays with "x" and "y" integer values
	public function SetupHM($x, $y, $dotsize, $dataArray) {
		$this->imagepath = dirname(__FILE__)."/images/";
		$this->mapImageWidth = intval($x);
		$this->mapImageHeight = intval($y);
		$this->dotsize = intval($dotsize);

		// Initialise the pointsArray array and set initial min & max values
		$this->pointsArray = array("min"=>0, "max"=>0, "points"=>array());
		foreach ($dataArray as $firstpoint) {
			$count = $firstpoint["count"];
			$this->pointsArray["min"] = $count;
			$this->pointsArray["max"] = $count;
			break; // process the rest of the array next time
		}

		// Get min & max for intensity adjustments and setup the points
		foreach ($dataArray as $thispoint) {
			$count = $thispoint["count"];
			if ($count < $this->pointsArray["min"]) $this->pointsArray["min"] = $count;
			if ($count > $this->pointsArray["max"]) $this->pointsArray["max"] = $count;

			if (!isset($this->pointsArray["points"][$count])) $this->pointsArray["points"][$count] = array();
			$this->pointsArray["points"][$count][] = array("x"=>$thispoint["x"], "y"=>$thispoint["y"]);
		}	
		
		return true;		
	}
	
    private function LoadColorMap($image) {
        $paletteImage = imagecreatefrompng($image);

        for ($coordY=0; $coordY < 256; $coordY++) {
            $coordX = 1;
            $ColorRGB = imagecolorat($paletteImage, $coordX, $coordY);
            
            $ColorizationArray[$coordY]["r"] = ($ColorRGB >> 16) & 0xFF;
            $ColorizationArray[$coordY]["g"] = ($ColorRGB >> 8) & 0xFF;
            $ColorizationArray[$coordY]["b"] = $ColorRGB & 0xFF;
        }
        
        imagedestroy($paletteImage); // clean resources       
        return $ColorizationArray;
    }

    /* FIX imagecopymerge on opacity images
     * FIX FOR ERROR IN PHP */
    function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
        $cut = imagecreatetruecolor($src_w, $src_h); // creating a cut resource
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
        imagedestroy($cut); // clean resources
    }
    
    public function GenerateHeatMap() {
		if (!file_exists($this->imagepath . "dot.png")) {
			trigger_error("The file dot.png cannot be found, the heatmap image cannot be generated.", E_USER_ERROR); return false;
		}
		
		if (!file_exists($this->imagepath . "colors.png")) {
			trigger_error("The file colors.png cannot be found, the heatmap image cannot be generated.", E_USER_ERROR); return false;
		}
        
        $dotImageName = $this->imagepath . 'dot.png';
        $grayscaleImageName = $this->imagepath . "bw." . $this->heatmap;
        $grayscaleJpgImageQuality=100; // For temporary result
        
        //$time_start = microtime(true); // timing

        $max = $this->pointsArray["max"];
        $min = $this->pointsArray["min"];
        $range = $max - $min + 1; // Add 1 to get the true number of points in the range

        $heatMapImageLocal = imagecreatetruecolor($this->mapImageWidth, $this->mapImageHeight);
        $fillColorWhite = imagecolorallocate($heatMapImageLocal, 255, 255, 255); // WHITE COLOR
        imagefill($heatMapImageLocal, 0, 0, $fillColorWhite);  // can be white background
        imagealphablending($heatMapImageLocal, true);

        $dotImage = imagecreatefrompng($dotImageName); // load dot image
        imagealphablending($dotImage, true);

        $width = $this->dotsize;
        $height = $this->dotsize;
        $dotImageNew = imagecreatetruecolor($width, $height);

        $fillColorTransparentNew = imagecolorallocatealpha($heatMapImageLocal, 0, 0, 0, 127);
        imagefill($dotImageNew, 0, 0, $fillColorTransparentNew);

		// COPY TO NEW TMP IMAGE FROM OLD - for some reason it is needed
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorsforindex($dotImage, imagecolorat($dotImage, $x, $y));
                $alpha = $color['alpha'];
                $newcolor = imagecolorallocatealpha($dotImageNew, $color['red'], $color['green'], $color['blue'], $alpha);
                imagesetpixel($dotImageNew, $x, $y, $newcolor);
            }
        }

        imagealphablending($dotImageNew, true);
        imagesavealpha($dotImageNew, false);

        // overlay all the dots
        foreach ($this->pointsArray["points"] as $count => $points) {
            foreach ($points as $point) {
                $x = $point['x'];
                $y = $point['y'];

                // THESE ARE THE PARAMETERS FOR IMAGE LOOK Tweak - They are not straight forward
                $base_intensity = 30;
                $add = ($count / $max) * 70;
                $resultDotIntensitivity = $base_intensity + $add;
                if ($resultDotIntensitivity > 100)
                    $resultDotIntensitivity = 100; // In general this is opacity of dot image
                
				// GET CENTERED POSTION OF DOT
                $x = $x - ($width>>1);
                $y = $y - ($height>>1);
                
                $this->imagecopymerge_alpha($heatMapImageLocal, $dotImageNew, $x, $y, 0, 0, $width, $height, $resultDotIntensitivity);
            }
        }

        //---------------------------------------------------------------------
        imagealphablending($heatMapImageLocal, false);
        imagesavealpha($heatMapImageLocal, true); // save the alpha
        
        imagejpeg($heatMapImageLocal, $grayscaleImageName, $grayscaleJpgImageQuality);
        
        imagedestroy($heatMapImageLocal); // clean resources
        imagedestroy($dotImageNew);       // clean resources
        imagedestroy($dotImage);          // clean resources
        
        //$time_end = microtime(true); // timing
        //$delta1 = round($time_end - $time_start, 4); // timing
        //fb('grayscale image generation  function:' . $delta1 . " seconds");

        //$t1 = microtime(true); // timing

        $this->CreateColourHeatMapUsePalette();

        //$t2 = microtime(true); // timing
        //$delta = round($t2 - $t1, 4); // timing
        //fb('colorizer function:' . $delta . " seconds");
    }

    private function CreateColourHeatMapUsePalette() {
		$imageFileNamePalette = $this->imagepath . 'colors.png';
		$imageResultQuality = 100;

		$imageFileNameGrayScaleSource = $this->imagepath . "bw." . $this->heatmap;
		$imageFileNameSaveResultJpg = $this->imagepath . $this->heatmap;

		$colorPalette = $this->LoadColorMap($imageFileNamePalette); // load color palette for coloring

		$paletteImage = imagecreate($this->mapImageWidth, $this->mapImageHeight); // make palette blank image
		$trueColorImage = imagecreatetruecolor($this->mapImageWidth, $this->mapImageHeight);

		$fillColorWhite = imagecolorallocate($paletteImage, 255, 255, 255); // WHITE COLOR
		imagefill($paletteImage, 0, 0, $fillColorWhite);  // can be white background

		$fillColorWhite2 = imagecolorallocate($trueColorImage, 255, 255, 255); // WHITE COLOR
		imagefill($trueColorImage, 0, 0, $fillColorWhite2);  // can be white background

		$grayscaleImageResource = imagecreatefromjpeg($imageFileNameGrayScaleSource);
		imagecopy($paletteImage, $grayscaleImageResource, 0, 0, 0, 0, $this->mapImageWidth, $this->mapImageHeight);
		imagedestroy($grayscaleImageResource); // clean resources

        $colors = imagecolorstotal($paletteImage);
        for ($i=0; $i<$colors; $i++) {
			$indexcolor = imagecolorsforindex($paletteImage, $i);
			$redColor = $indexcolor['red'];
			$cindex = 255 - $redColor;

			// set new palete color
			$r = $colorPalette[$cindex]["r"];
			$g = $colorPalette[$cindex]["g"];
			$b = $colorPalette[$cindex]["b"];

			imagecolorset($paletteImage, $i, $r, $g, $b);
        }
        
		imagecopy($trueColorImage,$paletteImage, 0, 0, 0, 0, $this->mapImageWidth, $this->mapImageHeight);
		$imageFileNameSaveResultJpg = $this->imagepath . $this->heatmap;

		$imageResultQuality = 90;
		imagefilter($trueColorImage, IMG_FILTER_GAUSSIAN_BLUR); // Blur image for better looking results
		imagejpeg($trueColorImage, $imageFileNameSaveResultJpg, $imageResultQuality); // save img to jpg

		imagedestroy($trueColorImage); // clean resources
		imagedestroy($paletteImage);   // clean resources    
    }
}

function heat_trackr_array_sort($array, $on, $order=SORT_ASC) {
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
            break;
            case SORT_DESC:
                arsort($sortable_array);
            break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}
?>