<?php
declare(strict_types=1);

namespace Datatables\View\Helper;

use Cake\Collection\Collection;
use Cake\View\Helper;
use Cake\View\Helper\HtmlHelper;
use Cake\View\Helper\UrlHelper;
use Cake\View\View;

/**
 * Datatable helper
 * @property HtmlHelper $Html
 * @property UrlHelper $Url
 */
class DatatableHelper extends Helper
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'processing' => true,
        'serverSide' => true,
    ];

    /**
     * Other helpers used by DatatableHelper
     *
     * @var array
     */
    protected $helpers = ['Url', 'Html'];
    private $linkTemplates = [];
    private $getDataTemplate = '';


    /**
     * @var string[]
     */
    private $dataKeys;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        // Load link templates
        $linkTemplate = $this->Html->getTemplates('link');
    }

    /**
     * @param array $linkTemplates
     * @return void
     */
    public function setLinkTemplates(array $linkTemplates)
    {
        $this->linkTemplates = $linkTemplates;
    }

    /**
     * Build the get data callback
     * @param string|array $url
     */
    public function setGetDataUrl($url = null)
    {
        $url = (array)$url;
        $url = array_merge($url, ['fullBase' => true, '_ext' => 'json']);
        $url = $this->Url->build($url);
        $this->getDataTemplate = <<<GET_DATA
    let getData = async () => {
        let res = await fetch('{$url}')
    }
GET_DATA;
    }

    /**
     * @param array|Collection $data
     */
    public function setFields(iterable $data)
    {
        $firstRecord = null;
        if ($data instanceof Collection) {
            $dataKeys =  array_keys((array) $data->first());
        } else {
            $dataKeys = $data;
        }
        if (empty($dataKeys)) {
            throw new \InvalidArgumentException(__('Couldn\'t get first item'));
        }

        $stringKeys = array_filter($dataKeys, 'is_string');
        foreach ($stringKeys as $key) {
            $this->dataKeys[] = ['data' => $key];
        }
    }

    public function getConfigJson(string $tagId, $prettyPrint = false): string
    {
        $config = array_merge($this->_defaultConfig, ['columns' => $this->dataKeys]);
        $config = json_encode($config, ($prettyPrint? JSON_PRETTY_PRINT : 0));
        // Remove start and end curly brackets
        $config = substr($config, 1, -1);
        return <<<TEMPLATE_STRING
            {$this->getDataTemplate}
            $(() => {
                $('#{$tagId}').DataTable({
                    // Can't escape callback function
                    ajax: getData(),
                    {$config},
                });
            });
TEMPLATE_STRING;
    }
}
