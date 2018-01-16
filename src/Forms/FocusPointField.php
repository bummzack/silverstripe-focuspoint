<?php

namespace Jonom\FocusPoint\Forms;

use SilverStripe\Forms\FormField;


/**
 * FocusPointField class.
 * Facilitates the selection of a focus point on an image.
 *
 * @extends FieldGroup
 */
class FocusPointField extends FormField
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

    protected $schemaDataType = self::SCHEMA_DATA_TYPE_CUSTOM;

    protected $schemaComponent = 'FocusPointField';
}
