<?php

namespace JonoM\FocusPoint\Forms;

use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;

/**
 * FocusPointField class.
 * Facilitates the selection of a focus point on an image.
 *
 * @extends FieldGroup
 */
class FocusPointField extends FieldGroup
{
    /**
     * Enable to view Focus X and Focus Y fields while in Dev mode.
     *
     * @var bool
     * @config
     */
    private static $debug = false;

    /**
     * Maximum width of preview image
     *
     * @var integer
     * @config
     */
    private static $max_width = 300;

    /**
     * Maximum height of preview image
     *
     * @var integer
     * @config
     */
    private static $max_height = 150;

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_CUSTOM;

    protected $schemaComponent = 'FocusPointField';

    public function __construct($name, $title = null, Image $image = null)
    {
        // Create the fields
        $fields = [
            TextField::create($name . 'FocusX'),
            TextField::create($name . 'FocusY')
        ];

        if ($image) {
            $previewImage = $image->FitMax($this->config()->get('max_width'), $this->config()->get('max_height'));
            array_unshift($fields, LiteralField::create('FocusPointGrid', $previewImage->renderWith(
                FocusPointField::class,
                ['FieldGridBackgroundCSS' => $image->FieldGridBackgroundCSS($previewImage->getWidth(), $previewImage->getHeight())]
            )));
        }

        parent::__construct($fields);

        $this
            ->setName($name)
            ->setTitle($title)
            ->addExtraClass('focuspoint-fieldgroup');

        if (Director::isDev() && $this->config()->get('debug')) {
            $this->addExtraClass('debug');
        }
    }
}
