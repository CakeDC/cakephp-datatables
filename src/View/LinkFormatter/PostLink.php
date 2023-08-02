<?php
declare(strict_types=1);

namespace CakeDC\Datatables\View\LinkFormatter;

use Cake\Utility\Text;
use CakeDC\Datatables\Datatables;

class PostLink implements LinkInterface
{
    use LinkTrait;

    protected array $_defaultConfig = [
        'template' => '<a href=":href" target=":target" onclick=":onclick">:content</a>',
        'url' => null,
        'value' => null,
        'label' => null,
        'disable' => null,
        'disableValue' => '',
        'type' => Datatables::LINK_TYPE_POST,
        'target' => '_self',
        'confirm' => false,
        'confirmCondition' => 'function (message){ return window.confirm(message); }',
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

        $htmlLink = Text::insert(
            $this->getConfig('template'),
            [
                'href' => $this->helper->Url->build($url) . $urlExtraValue,
                'target' => $this->getConfig('target') ?: "' + {$this->getConfig('target')} + '",
                'content' => $this->getConfig('label') ?: "' + {$this->getConfig('value')} + '",
                'onclick' => $this->onclickAction(),
            ]
        );

        return $this->conditionalLink($htmlLink);
    }

    /**
     * @return string
     */
    protected function onclickAction(): string
    {
        $output = 'return function(element) { ';
        $output .= $this->confirmAction();
        $output .= $this->externalForm();
        $output .= 'return true;';
        $output .= '}(this)';

        return $this->changeQuotes($output);
    }

    /**
     * @return string
     */
    protected function confirmAction(): string
    {
        $output = '';

        if ($this->getConfig('confirm')) {
            $output .= 'let confirmCondition = ' . $this->getConfig('confirmCondition') . ';';
            $output .= 'let message = "' . $this->getConfig('confirm') . '";';
            $output .= 'if (!confirmCondition(message)) { return false; }';
        }

        return $this->changeQuotes($output);
    }

    /**
     * @return string
     */
    protected function externalForm(): string
    {
        if (!in_array($this->getConfig('type'), Datatables::postLinkMethods())) {
            return '';
        }

        $output = 'let form = document.createElement("form");';
        $output .= 'form.setAttribute("method", "' . $this->getConfig('type') . '");';
        $output .= 'form.setAttribute("action", element.getAttribute("href"));';
        $output .= $this->csrfField();
        $output .= $this->formProtector();
        $output .= 'document.getElementsByTagName("body")[0].appendChild(form);';
        $output .= 'form.submit();';
        $output .= 'return false;';

        return $this->changeQuotes($output);
    }

    /**
     * @param  string $input
     * @return string
     */
    protected function changeQuotes(string $input): string
    {
        return str_replace('"', '\\\'', $input);
    }

    /**
     * @return string
     */
    protected function csrfField(): string
    {
        $request = $this->helper->getView()->getRequest();

        $csrfToken = $request->getAttribute('csrfToken');
        if (!$csrfToken) {
            return '';
        }

        $output = 'let csrfToken = document.createElement("input");';
        $output .= 'csrfToken.setAttribute("type", "hidden");';
        $output .= 'csrfToken.setAttribute("name", "_csrfToken");';
        $output .= 'csrfToken.setAttribute("value", "' . $csrfToken . '");';
        $output .= 'form.append(csrfToken);';

        return $this->changeQuotes($output);
    }

    /**
     * @return string
     */
    protected function formProtector(): string
    {
        // @todo WIP: compatibility with formSecurity
        /*
        $formTokenData = $this->_helper->getView()->getRequest()->getAttribute('formTokenData');
        if ($formTokenData !== null) {
            $session = $this->_helper->getView()->getRequest()->getSession();
            $session->start();
    
            return new FormProtector(
                $formTokenData
            );
        }
        */

        return '';
    }
}
