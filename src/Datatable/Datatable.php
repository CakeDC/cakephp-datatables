<?php

declare(strict_types=1);

namespace CakeDC\Datatables\Datatable;

use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use CakeDC\Datatables\Datatables;
use CakeDC\Datatables\Exception\MissConfiguredException;
use Exception;
use InvalidArgumentException;

/**
 * @property \Cake\View\Helper $Helper
 */
class Datatable
{
    use InstanceConfigTrait;

    /**
     * Default config for this helper.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'processing' => true,
        'serverSide' => true,
        // override to provide translations, @see https://datatables.net/examples/basic_init/language.html
        'language' => [],
        'pageLength' => 10,
        'lengthMenu' => [],
        'columnSearch' => true,
        //true => use default input search, false => use externalSearchInputId input search field
        'search' => true,
        'searchHeadersType' => null,
        // set an external input to act as search
        'externalSearchInputId' => null,
        // extra fields to inject in ajax call, for example CSRF token, additional ids, etc
        'extraFields' => null,
        //draw callback function
        //@todo add function callback in callback Datatable function
        'drawCallback' => null,
        //complete callback function
        'onCompleteCallback' => null,
        'ajaxUrl' => null,
        'autoWidth' => false,
        'tableCss' => [
            'width' => '100%',
            'table-layout' => 'fixed',
            'word-wrap' => 'break-word',
        ],
        'delay' => 3000,

        'tableId' => null,
        'headers' => [],
        'fields' => [],
        'headersConfig' => [
            'format' => false,
            'translate' => false,
            'headersAttrsTr' => [],
            'headersAttrsTh' => [],
        ],
        'rowActions' => [
            'name' => 'actions',
            'orderable' => 'false',
            'searchable' => 'false',
            'width' => '60px',
            //@todo: provide template customization for row actions default labels
            'links' => [
                [
                    'url' => ['action' => 'view', 'extra' => "/' + obj.id + '"],
                    'label' => '<li class="fas fa-search"></li>',
                ],
                [
                    'url' => ['action' => 'edit', 'extra' => "/' + obj.id + '"],
                    'label' => '<li class="fas fa-pencil-alt"></li>',
                ],
                [
                    'url' => ['action' => 'delete', 'extra' => "/' + obj.id + '"],
                    'type' => Datatables::LINK_TYPE_POST,
                    'confirm' => 'Are you sure you want to delete this item?', // @todo go to class config
                    'label' => '<li class="far fa-trash-alt"></li>',
                ],
            ],
        ],
    ];

    protected $Helper;

    public function __construct($Helper)
    {
        $this->Helper = $Helper;
        $this->setConfig($Helper->getConfig, null, true);

        //if (empty($this->Helper->Html)) {
        //    $this->Helper->Html = $this->Helper->getView()->loadHelper('Html');
        //}
    }

    public function setTableId(string $tableId)
    {
        $this->setConfig('tableId', $tableId);

        return $this;
    }

    public function setHeaders(array $headers, array $configs = [])
    {

        $this->setConfig('headers', $headers);
        $this->setConfig('headersConfig', $configs, true);

        return $this;
    }

    public function setFields(array $fields)
    {
        if (empty($fields)) {
            throw new InvalidArgumentException(__('Couldn\'t get first item'));
        }

        $this->setConfig('fields', $fields);

        return $this;
    }

    public function setRowActions(array $rowActions, bool $merge = false)
    {
        $this->setConfig('rowActions', $rowActions, $merge);

        return $this;
    }

    public function getTableHeaders()
    {
        $tableHeaders = $this->getConfig('headers');

        if (empty($tableHeaders)) {
            throw new Exception();
        }

        $headersConfig = $this->getConfig('headersConfig');

        foreach ($tableHeaders as &$tableHeader) {
            if ($headersConfig['format']) {
                $tableHeader = str_replace('.', '_', $tableHeader);
                $tableHeader = Inflector::humanize($tableHeader);
            }
            if ($headersConfig['translate']) {
                $tableHeader = __($tableHeader);
            }
        }

        return $this->Helper->Html->tableHeaders($tableHeaders, $headersConfig['headersAttrsTr'], $headersConfig['headersAttrsTh']);
    }    


    public function getDatatableScript(): string
    {
        if (empty($this->getDataTemplate)) {
            $this->setGetDataUrl();
        }

        $tagId = $this->getConfig('tableId');


        $this->processColumnRenderCallbacks();
        $this->processColumnDefinitionsCallbacks();
        $this->searchHeadersTypes = $this->processColumnTypeSearch();
        $this->validateConfigurationOptions();

        debug([
            'searchTypes' => ($this->searchHeadersTypes ?: ''),
            'delay' => $this->getConfig('delay') ?? '3000',
        ]);
        exit();

        $this->columnSearchTemplate = Text::insert(
            $this->columnSearchTemplate,
            [
                'searchTypes' => ($this->searchHeadersTypes ?: ''),
                'delay' => $this->getConfig('delay') ?? '3000',
            ]
        );

        if ($this->getConfig('columnSearch')) {
            $columnSearchTemplate = Text::insert(
                $this->columnSearchHeaderTemplate,
                ['tagId' => $tagId]
            );
        } else {
            $columnSearchTemplate = '';
        }

        if (!$this->getConfig('search')) {
            $searchInput = $this->getConfig('externalSearchInputId');
            $searchTemplate = Text::insert(
                $this->genericSearchTemplate,
                [
                    'searchInput' => $searchInput,
                    'tagId' => $tagId,
                ]
            );
        } else {
            $searchTemplate = '';
        }

        // @todo change values to config _default
        return Text::insert(
            $this->datatableConfigurationTemplate,
            [
                'getDataMethod' => $this->getDataTemplate,
                'searchTemplate' => $searchTemplate,
                'columnSearchTemplate' => $columnSearchTemplate,
                'tagId' => $tagId,
                'autoWidth' => $this->getConfig('autoWidth') ? 'true' : 'false',
                'pageLength' => $this->getConfig('pageLentgh') ?? '10',
                'processing' => $this->getConfig('processing') ? 'true' : 'false',
                'serverSide' => $this->getConfig('serverSide') ? 'true' : 'false',
                'configColumns' => $this->configColumns,
                'definitionColumns' => $this->definitionColumns,
                'language' => json_encode($this->getConfig('language')),
                'lengthMenu' => json_encode($this->getConfig('lengthMenu')),
                'drawCallback' => $this->getConfig('drawCallback') ? $this->getConfig('drawCallback') : 'null',
                'onCompleteCallback' => $this->getConfig('onCompleteCallback') ? $this->getConfig('onCompleteCallback') : 'null',
                'columnSearch' => $this->getConfig('columnSearch') ? $this->columnSearchTemplate : '',
                'tableCss' => json_encode($this->getConfig('tableCss')),
            ]
        );
    }

    public function getCommonScript(): string
    {
        return 'console.log("from getCommonScript")';
    }

    public function setGetDataUrl($defaultUrl = null)
    {
        $url = (array) $this->getConfig('ajaxUrl', $defaultUrl);
        $url = array_merge($url, ['fullBase' => true, '_ext' => 'json']);
        $url = $this->Helper->Url->build($url);

        if (!empty($this->getConfig('extraFields'))) {
            $extraFields = $this->processExtraFields();
            //@todo change to async or anonymous js function
            $this->getDataTemplate = <<<GET_DATA
            let getData = async () => {
                return {
                    url:'{$url}',
                    data: function ( d ) {
                            return $.extend( {}, d, {
                                $extraFields
                            });
                        }
                }
            };
            GET_DATA;
        } else {
            // @todo setConfig type POST
            $this->getDataTemplate = <<<GET_DATA
                let getData = async () => {
                    return {
                        url:'{$url}',
                        type: 'POST',
                    }
                };
            GET_DATA;
        }
    }

    protected function processExtraFields()
    {
        $rows = [];
        foreach ($this->getConfig('extraFields') as $definition) {
            $parts = [];
            foreach ($definition as $key => $val) {
                $parts[] = "'{$key}': {$val}";
            }
            $rows[] = implode(',', $parts);
        }

        return implode(',', $rows);
    }

    /**
     * Loop columns and create callbacks or simple json objects accordingly.
     *
     * @todo:  refactor into data object to define the column properties accordingly
     * @return void
     */
    protected function processColumnRenderCallbacks()
    {
        $processor = function ($key) {
            $output = '{';
            if (is_string($key)) {
                $output .= "data: '{$key}'";
            } else {
                if (!isset($key['name'])) {
                    return '';
                }
                $output .= "data: '{$key['name']}',";

                if (isset($key['links'])) {
                    $output .= "\nrender: function(data, type, obj) { ";
                    $links = $this->processActionLinkList((array)$key['links']);
                    $output .= "return " . implode("\n + ", $links);
                    $output .= '},';
                }
                if ($key['render'] ?? null) {
                    $output .= "\nrender: {$key['render']},";
                }
                if ($key['orderable'] ?? null) {
                    $output .= "\norderable: {$key['orderable']},";
                }
                if ($key['width'] ?? null) {
                    $output .= "\nwidth: '{$key['width']}',";
                }
            }
            $output .= '}';

            return $output;
        };
        $configColumns = array_map($processor, (array)$this->getConfig('fields'));
        $configRowActions = $processor((array)$this->rowActions);
        $this->configColumns = implode(", \n", $configColumns);
        $this->configColumns .= ", \n" . $configRowActions;
    }

    /**
     *  Process links to prepare them for the datatable.
     *
     * @param  array $sourceLinks
     * @return array
     */
    protected function processActionLinkList(array $sourceLinks): array
    {
        $links = [];
        foreach ($sourceLinks as $link) {
            $links[] = $this->processActionLink($link);
        }

        return $links;
    }

    /**
     * Format link with specified options from links array.
     *
     * @param  array $link
     * @return string
     */
    protected function processActionLink(array $link): string
    {
        switch ($link['type'] ?? null) {
        case Datatables::LINK_TYPE_DELETE:
        case Datatables::LINK_TYPE_PUT:
        case Datatables::LINK_TYPE_POST:
            $output = new \CakeDC\Datatables\View\Formatter\Link\PostLink($this->Helper, $link);
            break;
			case Datatables::LINK_TYPE_CUSTOM:
				if (!class_exists($link['formatter'] ?? null)) {
					throw new \OutOfBoundsException("Please specify a custom formatter");
				}
				$output = new $link['formatter']($this->Helper, $link);

				if (!method_exists($output, 'link')){
					throw new \OutOfBoundsException("Method link is not found in class");
				}

				break;
        case Datatables::LINK_TYPE_GET:
        default:
            $output = new \CakeDC\Datatables\View\Formatter\Link\Link($this->Helper, $link);
            break;
        }

        return $output->link();
    }

    /**
     * Loop columns definitions to set properties inside ColumnDefs as orderable or searchable
     */
    protected function processColumnDefinitionsCallbacks()
    {
        $rows = [];
        foreach ($this->definitionColumns as $definition) {
            $parts = [];
            foreach ($definition as $key => $val) {
                $parts[] = "'{$key}': {$val}";
            }
            $rows[] = '{' . implode(',', $parts) . '}';
        }
        $this->definitionColumns = implode(',', $rows);
    }

    /**
     * Loop types into javascript format.
     */
    protected function processColumnTypeSearch()
    {
        if ($this->searchHeadersTypes === null || $this->searchHeadersTypes == []) {
            $this->searchHeadersTypes = $this->getConfig('searchHeadersTypes');
        }
        if ($this->searchHeadersTypes === null || $this->searchHeadersTypes == []) {
            $this->searchHeadersTypes = $this->fillTypes($this->getConfig('fields'));
        }

        $rows = [];
        foreach ($this->searchHeadersTypes as $definition) {
            $parts = [];

            foreach ($definition as $parKey => $parVal) {
                if ($parKey == 'data') {
                    if (!empty($parVal) && is_array($parVal)) {
                        $dataPars = [];
                        foreach ($parVal as $v) {
                            $dataPars[] = "{'id': '" . $v['id'] . "', 'name': '" . $v['name'] . "'}";
                        }
                        $data = '[' . implode(',', $dataPars) . ']';
                    } else {
                        $data = '[]';
                    }
                    $parts[] = "'{$parKey}': {$data}";
                } else {
                    $parts[] = "'{$parKey}': '{$parVal}'";
                }
            }
            $rows[] = '{' . implode(',', $parts) . '}';
        }

        return '[' . implode(',', $rows) . ']';
    }

    /**
     * Fill default types for search headers
     *
     * @param  array $datakeys Number of columns in searchable columns
     * @return array
     */
    protected function fillTypes(array $datakeys): array
    {
        $searchTypes = [];
        foreach ($datakeys as $name => $key) {

            if (isset($key['searchable']) && $key['searchable'] == 'false') {
                $searchTypes[] = [];
            } else {
                if (isset($key['searchInput'])) {
                    $searchTypes[] = [
                        'type' => $key['searchInput']['type'],
                        'data' => (isset($key['searchInput']['options'])?$key['searchInput']['options']:[]),
                    ];
                } else {
                    $searchTypes[] = ['type' => 'input', 'data' => []];
                }
            }
        }

        return $searchTypes;
    }

    /**
     * Validate configuration options for the datatable.
     *
     * @throws \Datatables\Exception\MissConfiguredException
     */
    protected function validateConfigurationOptions()
    {
        if (empty($this->getConfig('fields'))) {
            throw new MissConfiguredException(__('There are not columns specified for your datatable.'));
        }

        if (empty($this->configColumns)) {
            throw new MissConfiguredException(__('Column renders are not specified for your datatable.'));
        }
    }
}
