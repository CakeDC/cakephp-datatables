<?php

declare(strict_types=1);

namespace CakeDC\Datatables\View\Formatter\Link;

use Cake\Utility\Text;
use CakeDC\Datatables\Datatables;

class Link extends AbstractLink
{
    protected $_defaultConfig = [
        'template' => '<a href=":href" target=":target">:content</a>',
        'url' => null,
        'value' => null,
        'label' => null,
        'disable' => null,
        'type' => Datatables::LINK_TYPE_GET,
        'confirm' => false,
        'target' => '_self',
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
            ]
        );

        return $this->conditionalLink($htmlLink);
    }
}
