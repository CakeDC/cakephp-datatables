<?php

namespace CakeDC\Datatables\Exception;

use Cake\Core\Exception\CakeException;

class MissConfiguredException extends CakeException
{
    protected $_defaultCode = 500;

    protected $_messageTemplate = 'Seems like there are some missing configurations.';
}
