<?php

namespace Jonom\FocusPoint\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

class LeftAndMainExtension extends Extension
{
    public function init()
    {
        Requirements::javascript('jonom/focuspoint: client/dist/js/main.js');
        Requirements::css('jonom/focuspoint: client/dist/styles/main.css');
    }
}
