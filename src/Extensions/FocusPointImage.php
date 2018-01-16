<?php

namespace Jonom\FocusPoint\Extensions;

use SilverStripe\Assets\Image;
use SilverStripe\Assets\Image_Backend;
use SilverStripe\Assets\Storage\AssetContainer;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Requirements;


/**
 * FocusPoint Image extension.
 * Extends Image to allow automatic cropping from a selected focus point.
 *
 * @extends DataExtension
 * @property Image owner
 */
class FocusPointImage extends DataExtension
{
    /**
     * TODO: Provide migration script to move from the two single fields to the composite-field
     */
    private static $db = [
        'FocusPoint' => 'FocusPoint'
    ];

    /**
     * Generate a label describing the focus point on a 3x3 grid e.g. 'focus-bottom-left'
     * This could be used for a CSS class. It's probably not very useful.
     * Use in templates with $BasicFocusArea.
     *
     * @return string
     */
    public function BasicFocusArea()
    {
        // Defaults
        $horzFocus = 'center';
        $vertFocus = 'center';

        $focus = $this->owner->getField('FocusPoint');

        // Calculate based on XY coords
        if ($focus->getFocusX() > .333) {
            $horzFocus = 'right';
        }
        if ($focus->getFocusX() < -.333) {
            $horzFocus = 'left';
        }
        if ($focus->getFocusY() > .333) {
            $vertFocus = 'top';
        }
        if ($focus->getFocusY() < -.333) {
            $vertFocus = 'bottom';
        }

        // Combine in to CSS class
        return 'focus-'.$horzFocus.'-'.$vertFocus;
    }

    /**
     * Generate a percentage based description of x focus point for use in CSS.
     * Range is 0% - 100%. Example x=.5 translates to 75%
     * Use in templates with {$PercentageX}%.
     *
     * @return int
     */
    public function PercentageX()
    {
        return round($this->focusCoordToOffset('x', $this->owner->getField('FocusPoint')->getFocusX() * 100));
    }

    /**
     * Generate a percentage based description of y focus point for use in CSS.
     * Range is 0% - 100%. Example y=-.5 translates to 75%
     * Use in templates with {$PercentageY}%.
     *
     * @return int
     */
    public function PercentageY()
    {
        return round($this->focusCoordToOffset('y', $this->owner->getField('FocusPoint')->getFocusY() * 100));
    }

    public function DebugFocusPoint()
    {
        Requirements::css('focuspoint/css/focuspoint-debug.css');

        return $this->owner->renderWith('Jonom/FocusPoint/FocusPointDebug');
    }

    public function focusCoordToOffset($axis, $coord)
    {
        // Turn a focus x/y coordinate in to an offset from left or top
        if ($axis == 'x') {
            return ($coord + 1) * 0.5;
        }
        if ($axis == 'y') {
            return ($coord - 1) * -0.5;
        }
    }

    public function focusOffsetToCoord($axis, $offset)
    {
        // Turn a left/top offset in to a focus x/y coordinate
        if ($axis == 'x') {
            return $offset * 2 - 1;
        }
        if ($axis == 'y') {
            return $offset * -2 + 1;
        }
    }

    public function calculateCrop($width, $height)
    {
        // Work out how to crop the image and provide new focus coordinates
        $cropData = array(
            'CropAxis' => 0,
            'CropOffset' => 0,
        );
        $cropData['x'] = array(
            'FocusPoint' => $this->owner->getField('FocusPoint')->getFocusX(),
            'OriginalLength' => $this->owner->getWidth(),
            'TargetLength' => round($width),
        );
        $cropData['y'] = array(
            'FocusPoint' => $this->owner->getField('FocusPoint')->getFocusY(),
            'OriginalLength' => $this->owner->getHeight(),
            'TargetLength' => round($height),
        );

        // Avoid divide by zero error
        if (!($cropData['x']['OriginalLength'] > 0 && $cropData['y']['OriginalLength'] > 0)) {
            return false;
        }

        // Work out which axis to crop on
        $cropAxis = false;
        $cropData['x']['ScaleRatio'] = $cropData['x']['OriginalLength'] / $cropData['x']['TargetLength'];
        $cropData['y']['ScaleRatio'] = $cropData['y']['OriginalLength'] / $cropData['y']['TargetLength'];
        if ($cropData['x']['ScaleRatio'] < $cropData['y']['ScaleRatio']) {
            // Top and/or bottom of image will be lost
            $cropAxis = 'y';
            $scaleRatio = $cropData['x']['ScaleRatio'];
        } elseif ($cropData['x']['ScaleRatio'] > $cropData['y']['ScaleRatio']) {
            // Left and/or right of image will be lost
            $cropAxis = 'x';
            $scaleRatio = $cropData['y']['ScaleRatio'];
        }
        $cropData['CropAxis'] = $cropAxis;

        // Adjust dimensions for cropping
        if ($cropAxis) {
            // Focus point offset
            $focusOffset = $this->focusCoordToOffset($cropAxis, $cropData[$cropAxis]['FocusPoint']);
            // Length after scaling but before cropping
            $scaledImageLength = floor($cropData[$cropAxis]['OriginalLength'] / $scaleRatio);
            // Focus point position in pixels
            $focusPos = floor($focusOffset * $scaledImageLength);
            // Container center in pixels
            $frameCenter = floor($cropData[$cropAxis]['TargetLength'] / 2);
            // Difference beetween focus point and center
            $focusShift = $focusPos - $frameCenter;
            // Limit offset so image remains filled
            $remainder = $scaledImageLength - $focusPos;
            $croppedRemainder = $cropData[$cropAxis]['TargetLength'] - $frameCenter;
            if ($remainder < $croppedRemainder) {
                $focusShift -= $croppedRemainder - $remainder;
            }
            if ($focusShift < 0) {
                $focusShift = 0;
            }
            // Set cropping start point
            $cropData['CropOffset'] = $focusShift;
            // Update Focus point location for cropped image
            $newFocusOffset = ($focusPos - $focusShift) / $cropData[$cropAxis]['TargetLength'];
            $cropData[$cropAxis]['FocusPoint'] = $this->focusOffsetToCoord($cropAxis, $newFocusOffset);
        }

        return $cropData;
    }

    /**
     * Resize and crop image to fill specified dimensions, centred on focal point
     * of image. Use in templates with $FocusFill.
     *
     * @param int $width  Width to crop to
     * @param int $height Height to crop to
     *
     * @return AssetContainer|null
     */
    public function FocusFill($width, $height)
    {
        return $this->CroppedFocusedImage($width, $height, $upscale = true);
    }

    /**
     * Crop this image to the aspect ratio defined by the specified width and
     * height, centred on focal point of image, then scale down the image to those
     * dimensions if it exceeds them. Similar to FocusFill but without
     * up-sampling. Use in templates with $FocusFillMax.
     *
     * @param int $width  Width to crop to
     * @param int $height Height to crop to
     *
     * @return AssetContainer|null
     */
    public function FocusFillMax($width, $height)
    {
        return $this->CroppedFocusedImage($width, $height, $upscale = false);
    }

    public function FocusCropWidth($width)
    {
        return ($this->owner->getWidth() > $width)
            ? $this->CroppedFocusedImage($width, $this->owner->getHeight())
            : $this->owner;
    }

    public function FocusCropHeight($height)
    {
        return ($this->owner->getHeight() > $height)
            ? $this->CroppedFocusedImage($this->owner->getWidth(), $height)
            : $this->owner;
    }

    /**
     * Generate a resized copy of this image with the given width & height,
     * cropping to maintain aspect ratio and focus point. Use in templates with
     * $CroppedFocusedImage.
     *
     * @param int  $width   Width to crop to
     * @param int  $height  Height to crop to
     * @param bool $upscale Will prevent upscaling if set to false
     *
     * @return AssetContainer|null
     */
    public function CroppedFocusedImage($width, $height, $upscale = true)
    {
        $width = $this->owner->castDimension($width, 'Width');
        $height = $this->owner->castDimension($height, 'Height');

        $originalWidth = $this->owner->getWidth();
        $originalHeight = $this->owner->getHeight();

        // Don't enlarge
        if (!$upscale) {
            $widthRatio = $originalWidth / $width;
            $heightRatio = $originalHeight / $height;
            if ($widthRatio < 1 && $widthRatio <= $heightRatio) {
                $width = $originalWidth;
                $height = round($height * $widthRatio);
            } elseif ($heightRatio < 1) {
                $height = $originalHeight;
                $width = round($width * $heightRatio);
            }
        }

        if ($this->owner->isSize($width, $height)) {
            return $this->owner;
        }

        $variant = $this->owner->variantName(__FUNCTION__, $width, $height);
        $cropData = $this->calculateCrop($width, $height);

        return $this->owner->manipulateImage($variant, function (Image_Backend $backend) use ($width, $height, $cropData) {
            $img = null;
            $cropAxis = $cropData['CropAxis'];
            $cropOffset = $cropData['CropOffset'];

            if ($cropAxis == 'x') {
                //Generate image
                $img = $backend
                    ->resizeByHeight($height)
                    ->crop(0, $cropOffset, $width, $height);
            } elseif ($cropAxis == 'y') {
                //Generate image
                $img = $backend
                    ->resizeByWidth($width)
                    ->crop($cropOffset, 0, $width, $height);
            } else {
                //Generate image without cropping
                $img = $backend->resize($width, $height);
            }

            if (!$img) {
                return null;
            }

            // Update FocusPoint
            $img->dbObject('FocusPoint')
                ->setFocusX($cropData['x']['FocusPoint'])
                ->setFocusY($cropData['y']['FocusPoint']);

            return $img;
        });
    }
}
