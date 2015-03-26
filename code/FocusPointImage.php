<?php

class FocusPointImage extends DataExtension {

	private static $db = array(
		'FocusX' => 'Double', // Decimal number between -1 & 1, where -1 is far left, 0 is center, 1 is far right.
		'FocusY' => 'Double' // Decimal number between -1 & 1, where -1 is bottom, 0 is center, 1 is top.
	);

	private static $defaults = array(
		// Preserve default behaviour of cropping from center
		'FocusX' => '0',
		'FocusY' => '0'
	);
	
	public function updateCMSFields(FieldList $fields) {
		// Add FocusPoint field for selecting focus
		$fields->addFieldToTab("Root.Main", $this->owner->FocusPointFields());
	}
	
	public function FocusPointFields() {
		// Load necessary scripts and styles
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(FOCUSPOINT_DIR . '/javascript/FocusPointField.js');
		Requirements::css(FOCUSPOINT_DIR . '/css/FocusPointField.css');
		
		// Create the fields
    $fG = FieldGroup::create(
			LiteralField::create('FocusPointGrid', $this->owner->renderWith('FocusPointField')),
			TextField::create('FocusX'),
			TextField::create('FocusY')
    );
		$fG->setName('FocusPoint');
		$fG->setTitle('Focus Point');
		$fG->addExtraClass('focuspoint-fieldgroup');
		return $fG;
	}
	
	/**
	 * Generate a broad description of focus point i.e. 'focus-bottom-left' for use in CSS.
	 * Use in templates with $BasicFocusArea
	 * 
	 * @return string
	 */
	public function BasicFocusArea() {
		// Defaults
		$horzFocus = "center";
		$vertFocus = "center";
		
		// Calculate based on XY coords
		if ($this->owner->FocusX > .333) {
			$horzFocus = "right";
		}
		if ($this->owner->FocusX < -.333) {
			$horzFocus = "left";
		}
		if ($this->owner->FocusY > .333) {
			$vertFocus = "top";
		}
		if ($this->owner->FocusY < -.333) {
			$vertFocus = "bottom";
		}
		
		// Combine in to CSS class
		return 'focus-'.$horzFocus.'-'.$vertFocus;
	}
	
	/**
	 * Generate a percentage based description of x focus point for use in CSS.
	 * Range is 0% - 100%. Example x=.5 translates to 75%
	 * Use in templates with {$PercentageX}%
	 * 
	 * @return int
	 */
	public function PercentageX() {
		return round(($this->owner->FocusX + 1)*50);
	}
	
	/**
	 * Generate a percentage based description of y focus point for use in CSS.
	 * Range is 0% - 100%. Example y=-.5 translates to 75%
	 * Use in templates with {$PercentageY}%
	 * 
	 * @return int
	 */
	public function PercentageY() {
		return round(($this->owner->FocusY - 1)*-50);
	}
	
	/**
	 * Get an image for the focus point CMS field.
	 * 
	 * @return Image
	 */
	public function FocusPointFieldImage() {
		// Use same image as CMS preview to save generating a new image - copied from File::getCMSFields()
		return $this->owner->getFormattedImage(
			'SetWidth', 
			Config::inst()->get('Image', 'asset_preview_width')
		);
	}
	
	/**
	 * Generate a resized copy of this image with the given width & height, cropping to maintain aspect ratio and focus point.
	 * Use in templates with $CroppedFocusedImage
	 * 
	 * @param integer $width Width to crop to
	 * @param integer $height Height to crop to
	 * @param boolean $upscale Will prevent upscaling if set to false
	 * @return Image
	 */
	public function CroppedFocusedImage($width,$height,$upscale=true) {
		// Don't enlarge
		if (!$upscale) {
			$widthRatio = $this->owner->width / $width;
			$heightRatio = $this->owner->height / $height;
			if ($widthRatio < 1 && $widthRatio <= $heightRatio) {
				$width = $this->owner->width;
				$height = $height*$widthRatio;
			}
			else if ($heightRatio < 1) {
				$height = $this->owner->height;
				$width = $width*$heightRatio;
			}
		}
		// Cache buster - add coords to filename as percentage (2 decimal points accuracy)
		$focusHash = $this->PercentageX() . '-' . $this->PercentageY();
		// Only resize if necessary
		return $this->owner->isSize($width, $height)&& !Config::inst()->get('Image', 'force_resample')
			? $this->owner
			: $this->owner->getFormattedImage('CroppedFocusedImage', $width, $height, $focusHash);
	}
	
	/**
	 * Generate a resized copy of this image with the given width & height, cropping to maintain aspect ratio and focus point.
	 * Use in templates with $CroppedFocusedImage
	 * 
	 * @param Image_Backend $backend
	 * @param integer $width Width to crop to
	 * @param integer $height Height to crop to
	 * @return Image_Backend
	 */
	public function generateCroppedFocusedImage(Image_Backend $backend, $width, $height){
		
		$width = round($width);
		$height = round($height);
		$top = 0;
		$left = 0;
		$originalWidth = $this->owner->width;
		$originalHeight = $this->owner->height;
		
		if ($this->owner->width > 0 && $this->owner->height > 0 ){// Can't divide by zero
		
			// Which is over by more?
			$widthRatio = $originalWidth/$width;
			$heightRatio = $originalHeight/$height;
			
			// Calculate offset required
			
			if ($widthRatio > $heightRatio) {
			
				// Left and/or right of image will be lost
				
				// target center in px
				$croppedCenterX = floor($width/2);
				
				// X axis focus point of scaled image in px
				$focusFactorX = ($this->owner->FocusX + 1)/2; // i.e .333 = one third along
				$scaledImageWidth = floor($originalWidth/$heightRatio);
				$focusX = floor($focusFactorX*$scaledImageWidth);
				
				// Calculate difference beetween focus point and center
				$focusOffsetX = $focusX - $croppedCenterX;
				
				// Reduce offset if necessary so image remains filled
				$xRemainder = $scaledImageWidth - $focusX;
				$croppedXRemainder = $width - $croppedCenterX;
				if ($xRemainder < $croppedXRemainder) $focusOffsetX-= $croppedXRemainder - $xRemainder;
				if ($focusOffsetX < 0) $focusOffsetX =0;
				
				// Set horizontal crop start point
				$left =  $focusOffsetX;
				
				// Generate image
				return $backend->resizeByHeight($height)->crop($top, $left, $width, $height);
				
			} else if ($widthRatio < $heightRatio) {
			
				// Top and/or bottom of image will be lost
			
				// Container center in px
				$croppedCenterY = floor($height/2);
				
				// Focus point of resize image in px
				$focusFactorY = ($this->owner->FocusY + 1)/2; // zero is bottom of image, 1 is top
				$scaledImageHeight = floor($originalHeight/$widthRatio);
				$focusY = $scaledImageHeight - floor($focusFactorY*$scaledImageHeight);
				
				// Calculate difference beetween focus point and center
				$focusOffsetY = $focusY - $croppedCenterY;
				
				// Reduce offset if necessary so image remains filled
				$yRemainder = $scaledImageHeight - $focusY;
				$croppedYRemainder = $height - $croppedCenterY;
				if ($yRemainder < $croppedYRemainder) $focusOffsetY-= $croppedYRemainder - $yRemainder;
				if ($focusOffsetY < 0) $focusOffsetY =0;
				
				// Set vertical crop start point
				$top =  $focusOffsetY;
				
				// Generate image
				return $backend->resizeByWidth($width)->crop($top, $left, $width, $height);
				
			} else {
			
				// Generate image without cropping
				return $backend->resize($width,$height);
			}
		
		}
	}
}