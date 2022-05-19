<?php
//@todo check width not working

declare(strict_types=1);

namespace CakeDC\Datatables\View\Helper;

use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\View;
use CakeDC\Datatables\Datatables;
use Datatables\Exception\MissConfiguredException;
use InvalidArgumentException;

/**
 * Datatable helper
 *
 * @property \CakeDC\Datatables\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\UrlHelper $Url
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
        // override to provide translations, @see https://datatables.net/examples/basic_init/language.html
        'language' => [],
        'pageLength' => 10,
        'lengthMenu' => [],
        'columnSearch' => true,
        //true => use default input search, false => use externalSearchInputId input search field
        'search' => true,
        // set an external input to act as search
        'externalSearchInputId' => null,
        // extra fields to inject in ajax call, for example CSRF token, additional ids, etc
        'extraFields' => [],
        //draw callback function
        //@todo add function callback in callback Datatable function
        'drawCallback' => null,
        //complete callback function
        'onCompleteCallback' => null,
        'ajaxUrl' => null,
    ];

    private $columnSearchTemplate = <<<COLUMN_SEARCH_CONFIGURATION
        
        var api = this.api();

        // For each column
        api
            .columns()
            .eq(0)
            .each(function (colIdx) {
                // Set the header cell to contain the input element
                var cell = $('.filters th').eq(
                    $(api.column(colIdx).header()).index()
                );
                var title = $(cell).text();
                $(cell).html('<input type="text" style="width:100%;" placeholder="' + title + '" />');

                // On every keypress in this input
                $(
                    'input',
                    $('.filters th').eq($(api.column(colIdx).header()).index())
                )
                .off('keyup change')
                .on('keyup change', function (e) {
                    e.stopPropagation();

                    // Get the search value
                    $(this).attr('title', $(this).val());
                    var regexr = '({search})'; //$(this).parents('th').find('select').val();

                    var cursorPosition = this.selectionStart;
                    // Search the column for that value
                    api
                        .column(colIdx)
                        .search(
                            this.value != ''
                                ? regexr.replace('{search}', '(((' + this.value + ')))')
                                : '',
                            this.value != '',
                            this.value == ''
                        )
                        .draw();

                    $(this)
                        .focus()[0]
                        .setSelectionRange(cursorPosition, cursorPosition);
                });
            });
    COLUMN_SEARCH_CONFIGURATION;

    private $genericSearchTemplate = <<<GENERIC_SEARCH_CONFIGURATION
        $('#%s').on( 'keyup click', function () {
            $('#%s').DataTable().search(
                $('#%s').val()       
            ).draw();
        });
    GENERIC_SEARCH_CONFIGURATION;

    private $columnSearchHeaderTemplate = <<<COLUMN_SEARCH_HEADER_CONFIGURATION
        $('#%s thead tr')
            .clone(true)
            .addClass('filters')
            .appendTo('#%s thead');
    COLUMN_SEARCH_HEADER_CONFIGURATION;

    /**
     * Json template with placeholders for configuration options.
     *
     * @var string
     */
    private $datatableConfigurationTemplate = <<<DATATABLE_CONFIGURATION
        // API callback
        %s

        // Generic search
        %s

        // Datatables configuration
        $(() => {

            //@todo use configuration for multicolumn filters
            %s
            
            $('#%s').DataTable({
                orderCellsTop: true,
                fixedHeader: true,
                autoWidth: false,
                ajax: getData(),
                //searching: false,
                pageLength: %s,
                processing: %s,
                serverSide: %s,
                //@todo: add option to select the paging type
                //pagingType: "simple",
                columns: [
                    %s
                ],
                columnDefs: [
                    %s
                ],
                language: %s,
                lengthMenu: %s,
                //@todo add function callback in callback Datatable function
                drawCallback: %s,
                //@todo use configuration instead  
                initComplete: function () { 

                    //onComplete
                    %s

                    //column search
                    %s

                },
            });
        });
    DATATABLE_CONFIGURATION;

    /**
     * Other helpers used by DatatableHelper
     *
     * @var array
     */
    protected $helpers = ['Url', 'Html'];

    /**
     * @var string[]
     */
    private $dataKeys = [];

    /**
     * @var string
     */
    private $getDataTemplate;

    /**
     * @var string
     */
    private $configColumns;

    /**
     * @var string[]
     */
    private $definitionColumns = [];

    public function __construct(View $view, array $config = [])
    {
        if (!isset($config['lengthMenu'])) {
            $config['lengthMenu'] = [5, 10, 25, 50, 100];
        }
        parent::__construct($view, $config);
    }

    /**
     * set value of congig variable to value passed as param
     *
     * @param string|array $key key to write
     * @param string|array $value value to write
     * @param bool $merge merge
     */
    public function setConfigKey($key, $value = null, $merge = true)
    {
        $this->setConfig($key, $value);
    }

    /**
     * Build the get data callback
     *
     * @param string|array $url url to ajax call
     */
    public function setGetDataUrl($defaultUrl = null)
    {
        $url = (array) $this->getConfig('ajaxUrl', $defaultUrl);
        $url = array_merge($url, ['fullBase' => true, '_ext' => 'json']);
        $url = $this->Url->build($url);

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

    /**
     * Set columns definitions as orderable and sortable
     *
     * @param \Cake\Collection\Collection $dataDefinitions array of definitions in columns as orderable and sortable
     */
    public function setDefinitions(iterable $dataDefinitions)
    {
        $this->definitionColumns = $dataDefinitions;
    }

    /**
     * @param \Cake\Collection\Collection $dataKeys data keys to show in datatable
     */
    public function setFields(iterable $dataKeys)
    {
        if (empty($dataKeys)) {
            throw new InvalidArgumentException(__('Couldn\'t get first item'));
        }
        $this->dataKeys = $dataKeys;
    }

    public function setRowActions(?iterable $rowActions = null)
    {
        if ($rowActions) {
            $this->rowActions = $rowActions;

            return;
        }

        // default row actions
        $this->rowActions = [
            'name' => 'actions',
            'orderable' => 'false',
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
                    'confirm' => __('Are you sure you want to delete this item?'), // @todo go to class config
                    'label' => '<li class="far fa-trash-alt"></li>',
                ],
            ],
        ];
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
        $this->processColumnDefinitionsCallbacks();
        $this->validateConfigurationOptions();

        if ($this->getConfig('columnSearch')) {
            $columnSearchTemplate = sprintf($this->columnSearchHeaderTemplate, $tagId, $tagId);
        } else {
            $columnSearchTemplate = '';
        }

        if (!$this->getConfig('search')) {
            $searchInput = $this->getConfig('externalSearchInputId');
            $searchTemplate = sprintf($this->genericSearchTemplate, $searchInput, $tagId, $searchInput);
        } else {
            $searchTemplate = '';
        }

        return sprintf(
            $this->datatableConfigurationTemplate,
            $this->getDataTemplate,
            $searchTemplate,
            $columnSearchTemplate,
            $tagId,
            $this->getConfig('pageLentgh') ?? '10',
            $this->getConfig('processing') ? 'true' : 'false',
            $this->getConfig('serverSide') ? 'true' : 'false',
            $this->configColumns,
            $this->definitionColumns,
            json_encode($this->getConfig('language')),
            json_encode($this->getConfig('lengthMenu')),
            $this->getConfig('drawCallback') ? $this->getConfig('drawCallback') : 'null',
            $this->getConfig('onCompleteCallback') ? $this->getConfig('onCompleteCallback') : 'null',
            $this->getConfig('columnSearch') ? $this->columnSearchTemplate : '',
        );
    }

    /**
     * Validate configuration options for the datatable.
     *
     * @throws \Datatables\Exception\MissConfiguredException
     */
    protected function validateConfigurationOptions()
    {
        if (empty($this->dataKeys)) {
            throw new MissConfiguredException(__('There are not columns specified for your datatable.'));
        }

        if (empty($this->configColumns)) {
            throw new MissConfiguredException(__('Column renders are not specified for your datatable.'));
        }
    }

    /**
     * Loop extra fields to inject in ajax call to server
     */
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
     * Loop columns and create callbacks or simple json objects accordingly.
     *
     * @todo: refactor into data object to define the column properties accordingly
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
        $configColumns = array_map($processor, (array)$this->dataKeys);
        $configRowActions = $processor((array)$this->rowActions);
        $this->configColumns = implode(", \n", $configColumns);
        $this->configColumns .= ", \n" . $configRowActions;
    }

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
     * @param array $link
     * @return string
     */
    protected function processActionLink(array $link): string
    {
        switch ($link['type'] ?? null) {
            case Datatables::LINK_TYPE_DELETE:
            case Datatables::LINK_TYPE_PUT:
            case Datatables::LINK_TYPE_POST:
                $output = new \CakeDC\Datatables\View\Formatter\Link\PostLink($this, $link);
                break;

            case Datatables::LINK_TYPE_GET:
            default:
                $output = new \CakeDC\Datatables\View\Formatter\Link\Link($this, $link);
                break;
        }

        return $output->link();
    }

    /**
     * Get formatted table headers
     *
     * @param iterable|null $tableHeaders
     * @param bool $format
     * @param bool $translate
     * @param array $headersAttrsTr
     * @param array $headersAttrsTh
     * @return string
     */
    public function getTableHeaders(
        ?iterable $tableHeaders = null,
        bool $format = false,
        bool $translate = false,
        array $headersAttrsTr = [],
        array $headersAttrsTh = []
    ): string {
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

        return $this->Html->tableHeaders($tableHeaders, $headersAttrsTr, $headersAttrsTh);
    }
}
