<?php

namespace Jonom\FocusPoint\Extensions;


use Jonom\FocusPoint\Forms\FocusPointField;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;

/**
 * Extension that adds the FocusPointField to asset-admin
 * @package Jonom\FocusPoint\Extensions
 */
class ImageFormFactoryExtension extends Extension
{
    public function updateFormFields(FieldList $fields, $controller, $formName, $context)
    {
        $fields->push(FocusPointField::create(
            'FocusPoint',
            _t(FocusPointField::class . '.FOCUSPOINT', 'Focus point')
        ));
    }
}
