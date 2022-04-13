<?php
declare(strict_types=1);

namespace CakeDC\Datatables\View\Helper;

use Cake\Collection\Collection;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\Helper\HtmlHelper;
use Cake\View\Helper\UrlHelper;
use Cake\View\View;
use Datatables\Exception\MissConfiguredException;
use InvalidArgumentException;

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
            columns: [
                %s
            ]
        });
    });
DATATABLE_CONFIGURATION;

    /**
     * Other helpers used by DatatableHelper
     *
     * @var array
     */
    protected $helpers = ['Url', 'Html'];
    private $htmlTemplates = [
        'link' => '<a href="%s">%s</a>'
    ];

    /**
     * @var string[]
     */
    private $dataKeys;

    /**
     * @var string
     */
    private $getDataTemplate;

    /**
     * @var string
     */
    private $configColumns;


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
    public function setFields(iterable $dataKeys)
    {
        if (empty($dataKeys)) {
            throw new InvalidArgumentException(__('Couldn\'t get first item'));
        }
        $this->dataKeys = $dataKeys;
    }

    /**
     * Get Datatable initialization script with options configured.
     *
     * @param string $tagId
     * @return string
     */
    public function getDatatableScript(string $tagId): string
    {
        if (empty($this->getDataTemplate)) {
            $this->setGetDataUrl();
        }
        $this->processColumnRenderCallbacks();
        $this->validateConfigurationOptions();
        $config = $this->getConfig();

        return sprintf(
            $this->datatableConfigurationTemplate,
            $this->getDataTemplate,
            $tagId,
            $config['processing']? 'true' : 'false',
            $config['serverSide']? 'true' : 'false',
            $this->configColumns
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

        if (empty($this->configColumns)) {
            throw new MissConfiguredException(__('Column renders are not specified for your datatable.'));
        }
    }

    /**
     * Loop columns and create callbacks or simple json objects accordingly.
     */
    private function processColumnRenderCallbacks()
    {
        $configColumns = array_map(function ($key) {
            $output = '{';
            if (is_string($key)) {
                $output .= "data:'{$key}'";
            } else {
                $output .= "data:'{$key['name']}',";

                if (isset($key['links'])) {
                    $output .= "\nrender: function(data, type, obj) {";
                    $links = [];
                    foreach ((array) $key['links'] as $link) {
                        $links[] = $this->processActionLink($link);
                    }
                    $output .= 'return ' . implode("\n + ", $links);
                    $output .= "}";
                } else {
                    $output .= "render:{$key['render']}";
                }
            }
            $output .= '}';

            return $output;
        }, (array)$this->dataKeys);
        $this->configColumns = implode(", \n", $configColumns);
    }

    /**
     * Format link with specified options from links array.
     *
     * @param array $link
     * @return string
     */
    private function processActionLink(array $link): string
    {
        $urlExtraValue = '';
        if (is_array($link['url'])) {
            $urlExtraValue = $link['url']['extra'] ?? '';
            unset($link['url']['extra']);
        }
        return "'" .
            sprintf(
                $this->htmlTemplates['link'],
                $this->Url->build($link['url']) . $urlExtraValue,
                $link['label']?: "' + {$link['value']} + '"
            )
            . "'" ;
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
