<?php
declare(strict_types=1);

namespace CakeDC\Datatables\View\LinkFormatter;

use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Text;
use CakeDC\Datatables\View\Helper\DatatableHelper;
use Exception;

trait LinkTrait
{
    use InstanceConfigTrait;

    protected DatatableHelper $helper;

    protected string $conditionalLinkScript = <<<CONDITIONAL_LINK_SCRIPT
    function (value) {
        const disable = :disable
        if (disable(value, obj)) {
            return value ?? "";
        }

        return ':htmlLink';
    }(:valueObj)
    CONDITIONAL_LINK_SCRIPT;

    /**
     * @throws \Exception
     */
    public function __construct(DatatableHelper $helper, array $config = [])
    {
        $this->helper = $helper;
        $this->setConfig($config);
        $this->initialize($config);

        if (empty($this->getConfig('url'))) {
            throw new Exception('url option cannot be empty');
        }
    }

    /**
     * @param array $config
     * @return void
     */
    public function initialize(array $config = []): void
    {
    }

    /**
     * @return string
     */
    public function render(): string
    {
        return '';
    }

    /**
     * Get conditional link
     *
     * @param string $htmlLink
     * @return string
     */
    protected function conditionalLink(string $htmlLink): string
    {
        if (empty($this->getConfig('disable'))) {
            return '\'' . $htmlLink . '\'';
        }

        return Text::insert($this->conditionalLinkScript, [
            'disable' => $this->getConfig('disable'),
            'htmlLink' => $htmlLink,
            'valueObj' => $this->getConfig('value'),
        ]);
    }
}
