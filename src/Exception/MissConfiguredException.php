<?php
declare(strict_types=1);

namespace CakeDC\Datatables\Exception;

use Cake\Core\Exception\CakeException;

class MissConfiguredException extends CakeException
{
    protected int $_defaultCode = 500;
    protected string $_messageTemplate = 'Seems like there are some missing configurations.';
}
