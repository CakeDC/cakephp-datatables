<?php

declare(strict_types=1);

namespace CakeDC\Datatables\View\Formatter\Link;

use Cake\Utility\Text;
use CakeDC\Datatables\Datatables;

class PostLink extends AbstractLink
{
    protected $_defaultConfig = [
        'template' => '<a href=":href" target=":target" onclick=":onclick">:content</a>',
        'url' => null,
        'value' => null,
        'label' => null,
        'disable' => null,
        'type' => Datatables::LINK_TYPE_POST,
        'target' => '_self',
        'confirm' => false,
        'confirmCondition' => 'function (message){ return window.confirm(message); }',
    ];

    /**
     * @return string
     */
    public function link(): string
    {
        $urlExtraValue = '';

        $url = $this->getConfig('url', null);
        if (is_array($url)) {
            $urlExtraValue = $url['extra'] ?? '';
            unset($url['extra']);
        }

        $htmlLink = Text::insert(
            $this->getConfig('template'),
            [
                'href' => $this->_helper->Url->build($url) . $urlExtraValue,
                'target' => $this->getConfig('target') ?: "' + {$this->getConfig('target')} + '",
                'content' => $this->getConfig('label') ?: "' + {$this->getConfig('value')} + '",
                'onclick' => $this->onclickAction(),
            ]
        );

        return $this->conditionalLink($htmlLink);
    }

    protected function onclickAction(): string
    {
        $output = 'return function(element) { ';
        if ($this->getConfig('confirm')) {
            $output .= 'let confirmCondition = ' . $this->getConfig('confirmCondition') . ';';
            $output .= 'let message = "' . $this->getConfig('confirm') . '";';
            $output .= 'if (!confirmCondition(message)) { return false; }';
        }

        //$output .= 'alert(\\\'asdasds\\\');';
        $output .= 'return true;';
        $output .= '}(this)';

        return $output;
    }

}
