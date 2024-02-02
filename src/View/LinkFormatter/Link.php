<?php
declare(strict_types=1);

namespace CakeDC\Datatables\View\LinkFormatter;

use Cake\Utility\Text;
use CakeDC\Datatables\Datatables;

class Link implements LinkInterface
{
    use LinkTrait;

    protected array $_defaultConfig = [
        'template' => '<a href=":href" target=":target">:content</a>',
        'url' => null,
        'value' => null,
        'label' => null,
        'disable' => null,
        'disableValue' => '',
        'type' => Datatables::LINK_TYPE_GET,
        'confirm' => false,
        'target' => '_self',
    ];

    /**
     * @return string
     */
    public function render(): string
    {
        $urlExtraValue = '';

        $url = $this->getConfig('url');
        if (is_array($url)) {
            $urlExtraValue = $url['extra'] ?? '';
            unset($url['extra']);
        }

        $target = $this->getConfig('target');
        assert(is_string($target));
        $label = $this->getConfig('value');
        assert(is_string($label));
        $htmlLink = Text::insert(
            $this->getConfig('template'),
            [
                'href' => $this->helper->Url->build($url) . $urlExtraValue,
                'target' => $target ?: "' + {$target} + '",
                'content' => $label ?: "' + {$this->getConfig('value')} + '",
            ]
        );

        return $this->conditionalLink($htmlLink);
    }
}
