<?php

declare(strict_types=1);

namespace CakeDC\Datatables\View\Formatter\Link;

use Cake\Core\InstanceConfigTrait;
use Cake\View\Helper;
use Exception;

class AbstractLink
{
    use InstanceConfigTrait;

    protected $_helper;

    public function __construct(Helper $helper, array $config = [])
    {
        $this->_helper = $helper;
        $this->setConfig($config);
        $this->initialize($config);

        if (empty($this->getConfig('url'))) {
            throw new Exception("url option cannot be empty");
        }
    }

    /**
     * @param  array $config
     * @return void
     */
    public function initialize(array $config = []): void
    {
    }

    /**
     * @return string
     */
    public function link(): string
    {
        return '';
    }

    protected function conditionalLink(string $htmlLink)
    {
        if (empty($this->getConfig('disable'))) {
            return '\'' . $htmlLink . '\'';
        }

        return 'function(value) {
            let disable = ' . $this->getConfig('disable') . '
            if (disable(value, obj)) {
                return value;
            }

            return \'' . $htmlLink . '\';
        }(' . $this->getConfig('value') . ')';
    }
}
