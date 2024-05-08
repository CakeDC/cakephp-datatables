<?php
declare(strict_types=1);

namespace CakeDC\Datatables\View\LinkFormatter;

use Cake\Utility\Text;
use CakeDC\Datatables\Datatables;

class Link implements LinkInterface
{
    use LinkTrait;

    protected array $_defaultConfig = [
        'template' => '<a href=":href" title=":title" target=":target" class=":class">:content</a>',
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
        $value = $this->getConfig('value');
        $title = $this->getConfig('title');
        $class = $this->getConfig('class');
        $htmlLink = Text::insert(
            $this->getConfig('template'),
            [
                'href' => $this->helper->Url->build($url) . $urlExtraValue,
                'target' => $target ?: "' + {$target} + '",
                'title' => $title ?: "' + {$title} + '",
                'class' => $class ?: "' + {$class} + '",
                'content' => $value ?: "' + {$this->getConfig('value')} + '",
            ]
        );

        return $this->conditionalLink($htmlLink);
    }
}
