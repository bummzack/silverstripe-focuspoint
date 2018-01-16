<?php

namespace Jonom\FocusPoint\FieldType;

use SilverStripe\ORM\FieldType\DBComposite;


class DBFocusPoint extends DBComposite
{
    /**
     * Describes the focus point coordinates on an image.
     * FocusX: Decimal number between -1 & 1, where -1 is far left, 0 is center, 1 is far right.
     * FocusY: Decimal number between -1 & 1, where -1 is bottom, 0 is center, 1 is top.
     */
    private static $composite_db = [
        'FocusX' => 'Double',
        'FocusY' => 'Double'
    ];

    public function getFocusX()
    {
        return $this->getField('FocusX');
    }

    public function setFocusX($value)
    {
        $this->setField('FocusX',$value);
        return $this;
    }

    public function getFocusY()
    {
        return $this->getField('FocusY');
    }

    public function setFocusY($value)
    {
        $this->setField('FocusY',$value);
        return $this;
    }

    public function exists()
    {
        return true;
    }
}
