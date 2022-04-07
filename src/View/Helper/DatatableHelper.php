<?php
declare(strict_types=1);

namespace Datatables\View\Helper;

use Cake\Collection\Collection;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\Helper\HtmlHelper;
use Cake\View\Helper\UrlHelper;
use Cake\View\View;
use Datatables\Exception\MissConfiguredException;

/**
 * Datatable helper
 * @property HtmlHelper $Html
 * @property UrlHelper $Url
 */
class DatatableHelper extends Helper
{
    /**
     * Default Datatable js library configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'processing' => true,
        'serverSide' => true,
    ];

    /**
     * Json template with placeholders for configuration options.
     *
     * @var string
     */
    private $datatableConfigurationTemplate = <<<DATATABLE_CONFIGURATION
    // API callback
    %s

    // Datatables configuration
    $(() => {
        $('#%s').DataTable({
            ajax: getData(),
            processing: %s,
            serverSide: %s,
            columns: %s
        });
    });
DATATABLE_CONFIGURATION;

    /**
     * Other helpers used by DatatableHelper
     *
     * @var array
     */
    protected $helpers = ['Url', 'Html'];
    private $htmlHelperTemplateIds = ['link'];
    private $htmlTemplates = [];

    /**
     * @var string[]
     */
    private $dataKeys;

    /**
     * @var string
     */
    private $getDataTemplate;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setHtmlTemplates();
    }

    /**
     * Set HTML templates from the Html helper.
     *
     * @param array $htmlTemplates
     * @return void
     */
    public function setHtmlTemplates(array $htmlTemplates = [])
    {
        if (empty($htmlTemplates)) {
            foreach ($this->htmlHelperTemplateIds as $templateId) {
                $htmlTemplates[$templateId] = $this->Html->getTemplates($templateId);
            }
        }
        $this->htmlTemplates = array_merge($htmlTemplates, $this->htmlTemplates);
    }

    /**
     * Build the get data callback
     *
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

    /**
     * Get Datatable initialization script with options configured.
     *
     * @param string $tagId
     * @return string
     */
    public function getDatatableScript(string $tagId): string
    {
        $config = $this->getConfig();
        $columns = json_encode($this->dataKeys);
        if (empty($this->getDataTemplate)) {
            $this->setGetDataUrl();
        }

        $this->validateConfigurationOptions();
        return sprintf(
            $this->datatableConfigurationTemplate,
            $this->getDataTemplate,
            $tagId,
            $config['processing']? 'true' : 'false',
            $config['serverSide']? 'true' : 'false',
            $columns ?? '[]'
        );
    }

    /**
     * Validate configuration options for the datatable.
     *
     * @throws MissConfiguredException
     */
    private function validateConfigurationOptions()
    {
        if (empty($this->dataKeys)) {
            throw new MissConfiguredException(__('There are not columns specified for your datatable.'));
        }
    }

    /**
     * Get formatted table headers
     *
     * @param iterable|null $tableHeaders
     * @param bool $format
     * @param bool $translate
     * @param array $headersAttrs
     * @return string
     */
    public function getTableHeaders(
        iterable $tableHeaders = null,
        bool $format = false,
        bool $translate = false,
        array $headersAttrs = []
    ): string
    {
        $tableHeaders = $tableHeaders ?? $this->dataKeys;

        foreach ($tableHeaders as &$tableHeader) {
            if ($format) {
                $tableHeader = str_replace('.', '_', $tableHeader);
                $tableHeader = Inflector::humanize($tableHeader);
            }

            if ($translate) {
                $tableHeader = __($tableHeader);
            }
        }

        return $this->Html->tableHeaders($tableHeaders, $headersAttrs);
    }
}
