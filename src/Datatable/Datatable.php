<?php

declare(strict_types=1);

namespace CakeDC\Datatables\Datatable;

use CakeDC\Datatables\Datatables;
use CakeDC\Datatables\Exception\MissConfiguredException;
use CakeDC\Datatables\View\LinkFormatter\LinkInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Exception;
use InvalidArgumentException;

/**
 * @property \Cake\View\Helper $Helper
 */
class Datatable
{
    use InstanceConfigTrait;

    /**
     * @var string
     */
    protected $getDataTemplate;

    /**
     * @var string
     */
    protected $configColumns;

    /**
     * Default config for this helper.
     *
     * @var array<string, mixed>
     */


    const MULTI_SELECT_TYPE_SELECT2 = 'select2';
    const MULTI_SELECT_TYPE_JQUERY_UI = 'jquery-ui';

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
        'createdRow' => false,
        'ajaxUrl' => null,
        'ajaxType' => 'GET',
        'csrfToken' => null,
        'autoWidth' => false,
        'tableCss' => [
            'width' => '100%',
            'table-layout' => 'fixed',
            'word-wrap' => 'break-word',
        ],
        'delay' => 3000,
        'definitionColumns' => [],
        'fixedHeader' => true,
        'tableId' => '',
        'headers' => [],
        'fields' => [],
        'headersConfig' => [
            'format' => false,
            'translate' => false,
            'headersAttrsTr' => [],
            'headersAttrsTh' => [],
        ],
        'multiSelectType' => self::MULTI_SELECT_TYPE_SELECT2,
        'defaultOrder' => [0, 'asc'],
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

    /**
     * @var string[]
     */
    protected $searchHeadersTypes = [];

    // @todo chagen var to const/let
    protected $columnSearchTemplate = <<<COLUMN_SEARCH_CONFIGURATION
        const api = this.api();

        let columnsSearch = :searchTypes;

        // For each column
        api
            .columns()
            .eq(0)
            .each(function (colIdx) {
                var cell = $('#:tagId .filters th').eq(
                    $(api.column(colIdx).header()).index()
                );
                if (columnsSearch[colIdx] !== undefined) {

                    if (columnsSearch[colIdx].type !== undefined) {
                        switch (columnsSearch[colIdx].type) {
                            case 'multiple':
                                cell.html('<select class="form-select-multiple" multiple="multiple"></select>');
                                columnsSearch[colIdx].data.forEach(function (data) {
                                    $(
                                        'select',
                                        $('#:tagId .filters th').eq($(api.column(colIdx).header()).index())
                                    ).append(
                                        '<option value="' + data.id + '">' + data.name + '</option>'
                                    );
                                });
                                $(
                                    'select',
                                    $('#:tagId .filters th').eq($(api.column(colIdx).header()).index())
                                )
                                .on('change', function () {
                                    let select_value = $(
                                        'select option:selected',
                                        $('#:tagId .filters th').eq($(api.column(colIdx).header()).index())
                                    ).toArray().map(item => item.value).join();
                                    api.column(colIdx).search(select_value).draw();
                                });
                                break;

                            case 'select' :
                                cell.html('<select style="width:100%"><option value=""></option></select>');
                                columnsSearch[colIdx].data.forEach(function (data) {
                                    $(
                                        'select',
                                        $('#:tagId .filters th').eq($(api.column(colIdx).header()).index())
                                    ).append(
                                        '<option value="' + data.id + '">' + data.name + '</option>'
                                    );
                                });
                                $(
                                    'select',
                                    $('#:tagId .filters th').eq($(api.column(colIdx).header()).index())
                                )
                                .on('change', function () {
                                    api.column(colIdx).search(this.value).draw();
                                });
                                break;

                            case 'date':
                                title = cell.data('header') ?? '';
                                cell.html('<input type="text" id="from' + colIdx + '" class="from datepicker" data-provide="datepicker" placeholder="'+ title +'" /><br /><input type="text" class="to datepiker" id="to' + colIdx + '" data-provide="datepicker" placeholder="'+ title +'" />')
                                $('#:tagId').find('#from'+colIdx)
                                .datepicker()
                                .on('change', function () {
                                    if($('#to'+colIdx).val() !== '' && validateDate($('#to'+colIdx).val())) {
                                        api.column(colIdx).search($('#:tagId').find('#from'+colIdx).val() + '|' + $('#:tagId').find('#to' + colIdx).val()).draw();
                                    } else {
                                        $('#to'+colIdx).val('');
                                        if($('#from'+colIdx).val() !== '' && validateDate($('#from'+colIdx).val())) {
                                            api.column(colIdx).search($('#:tagId').find('#from'+colIdx).val() + '|').draw();
                                        } else {
                                            $('#from'+colIdx).val('');
                                            api.column(colIdx).search('').draw();
                                        }
                                    }
                                });
                                $('#:tagId').find('#to'+colIdx)
                                .datepicker()
                                .on('change', function () {
                                    if($('#from'+colIdx).val() !== '' && validateDate($('#from'+colIdx).val())) {
                                        api.column(colIdx).search($('#:tagId').find('#from'+colIdx).val() + '|' + $('#:tagId').find('#to' + colIdx).val()).draw();
                                    } else {
                                        $('#from'+colIdx).val('');
                                        if ($('#to'+colIdx).val() !== '' && validateDate($('#to'+colIdx).val())) {
                                            api.column(colIdx).search('|' + $('#:tagId').find('#to' + colIdx).val()).draw();
                                        } else {
                                            $('#to'+colIdx).val('');
                                            api.column(colIdx).search('').draw();
                                        }
                                    }

                                });
                                break;
                            case 'input':
                            default:
                                title = cell.data('header') ?? '';
                                cell.html('<input type="text" style="width:100%;" placeholder="'+ title +'" />');
                                $(
                                    'input',
                                    $('#:tagId .filters th').eq($(api.column(colIdx).header()).index())
                                )
                                .off('keyup change')
                                .on('keyup change', function (e) {
                                    let action = exeCall;
                                    if(action == null || action == false) {
                                        exeCall = true;
                                        setTimeout(function () {
                                            exeCall = false;
                                        }, :delay);
                                    } else {
                                        if(action == true) {
                                            return;
                                        }
                                    }

                                    e.stopPropagation();
                                    // Get the search value
                                    $(this).attr('title', $(this).val());
                                    var regexr = '({search})'; //$(this).parents('th').find('select').val();

                                    var cursorPosition = this.selectionStart;
                                    // Search the column for that value
                                    api
                                        .column(colIdx)
                                        .search(
                                            this.value != ''?
                                                regexr.replace('{search}',
                                                    '(((' + this.value + ')))'): '',
                                                    this.value != '',
                                                    this.value == ''
                                                )
                                        .draw();

                                    $(this)
                                        .focus()[0]
                                        .setSelectionRange(cursorPosition, cursorPosition);
                                });
                                break;
                        }
                    }
                }
            });

        // Save filters
        $('#:tagId .filters input, #:tagId .filters select').on('change', function () {
            saveFilters(api);
        });

        api.on('order', function() {
            saveFilters(api);
        })

        $('.clear-filters').on('click', function() {
            resetFilters(api)
        })
    COLUMN_SEARCH_CONFIGURATION;

    protected $genericSearchTemplate = <<<GENERIC_SEARCH_CONFIGURATION
        $('#:searchInput').on('keyup click', function () {
            $('#:tagId').DataTable().search(
                $('#:searchInput').val()
            ).draw();
        });
    GENERIC_SEARCH_CONFIGURATION;

    // @todo change to Text::insert format
    protected $columnSearchHeaderTemplate = <<<COLUMN_SEARCH_HEADER_CONFIGURATION
        const filters = $('#:tagId thead tr')
            .clone(true)
            .addClass('filters');
        filters.find('th').each((index, item) => {
            let iCell = $(item)
            iCell.data('header', iCell.text())
            iCell.text('')
        })
        filters.appendTo('#:tagId thead');
    COLUMN_SEARCH_HEADER_CONFIGURATION;

    /**
     * Json template with placeholders for configuration options.
     *
     * @var string
     */
    protected $datatableConfigurationTemplate = <<<DATATABLE_CONFIGURATION
        // Datatables configuration
        $(async () => {

            // API callback
            :getDataMethod

            // Generic search
            let exeCall = null;
            :searchTemplate

            :columnSearchTemplate

            const dt = $('#:tagId');

            dt.DataTable({
                orderCellsTop: true,
                fixedHeader: :fixedHeader,
                autoWidth: :autoWidth,
                ajax: await getData(),
                //searching: false,
                pageLength: :pageLength,
                processing: :processing,
                serverSide: :serverSide,
                //@todo: add option to select the paging type
                //pagingType: "simple",
                columns: [
                    :configColumns
                ],
                columnDefs: [
                    :definitionColumns
                ],
                :callbackCreatedRow
                language: :language,
                lengthMenu: :lengthMenu,
                //@todo add function callback in callback Datatable function
                drawCallback: :drawCallback,
                //@todo use configuration instead
                initComplete: function () {
                    //onComplete
                    :onCompleteCallback

                    //column search
                    :columnSearch

                    :multiSelectCallback

                    // Load filters
                    loadFilters(api);
                },
                order: :defaultOrder,
            });

            dt.css(:tableCss);

            async function saveFilters(api) {
                let filters = {};
                $('#:tagId .filters input, #:tagId .filters select').each(function (index, item) {
                    filters[index] = $(item).val();
                });

                let order = api.order();

                localStorage.setItem('filters_:tagId', JSON.stringify({filters, order}));
            }

            async function loadFilters(api) {
                let {filters, order} = JSON.parse(localStorage.getItem('filters_:tagId')) ?? {filters: [], order: []};

                $('#:tagId .filters input, #:tagId .filters select').each(function (index, item) {
                    $(item).val(filters[index] ?? null);
                    api.columns(index).search(filters[index] ?? '') 
                });

                api.order(order);

                api.draw();
            }

            async function resetFilters(api) {
                await localStorage.removeItem('filters_:tagId');
                await loadFilters(api)
            }

            function validateDate(text) {
                text = text.replaceAll("/","-");
                var re = /^(\d{4}(-)\d{2}(-)\d{2}|\d{2}(-)\d{2}(-)\d{4})$/;
                return re.test(text);
            }
        });
    DATATABLE_CONFIGURATION;

    protected $datatableJqueryUITemplate = <<<JQUERYUI_CONFIGURATION
			if ($.fn.multiselect) { $(function(){ $('.form-select-multiple').multiselect(); }); }
	JQUERYUI_CONFIGURATION;


    protected $datatableSelect2Template = <<<SELECT2_CONFIGURATION
			if($.fn.select2) { $(function(){ $('.form-select-multiple').select2();}); }
	SELECT2_CONFIGURATION;


    /**
     * @param Helper $Helper
     */
    public function __construct($Helper)
    {
        $this->Helper = $Helper;
        $this->setConfig($Helper->getConfig(), null, true);
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

    public function setCallbackCreatedRow(string $functionCallback)
    {
        $this->setConfig('createdRow', $functionCallback);
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

        $this->columnSearchTemplate = Text::insert(
            $this->columnSearchTemplate,
            [
                'searchTypes' => ($this->searchHeadersTypes ?? ''),
                'delay' => $this->getConfig('delay') ?? '3000',
                'tagId' => $tagId,
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
        $valuesToReplace = [
            'getDataMethod' => $this->getDataTemplate,
            'searchTemplate' => $searchTemplate,
            'columnSearchTemplate' => $columnSearchTemplate,
            'tagId' => $tagId,
            'fixedHeader' => $this->getConfig('fixedHeader') ? 'true' : 'false',
            'autoWidth' => $this->getConfig('autoWidth') ? 'true' : 'false',
            'pageLength' => $this->getConfig('pageLength') ?? '10',
            'processing' => $this->getConfig('processing') ? 'true' : 'false',
            'serverSide' => $this->getConfig('serverSide') ? 'true' : 'false',
            'configColumns' => $this->configColumns,
            'definitionColumns' => $this->getConfig('definitionColumns'),
            'language' => json_encode($this->getConfig('language')),
            'lengthMenu' => json_encode($this->getConfig('lengthMenu')),
            'drawCallback' => $this->getConfig('drawCallback') ? $this->getConfig('drawCallback') : 'null',
            'onCompleteCallback' => $this->getConfig('onCompleteCallback') ? $this->getConfig('onCompleteCallback') : '',
            'columnSearch' => $this->getConfig('columnSearch') ? $this->columnSearchTemplate : '',
            'tableCss' => json_encode($this->getConfig('tableCss')),
            'multiSelectCallback' => $this->getConfig('multiSelectType') === 'jquery-ui' ? $this->datatableJqueryUITemplate : $this->datatableSelect2Template,
            'defaultOrder' => json_encode([$this->getConfig('defaultOrder')]),
        ];

        if ($this->getConfig('createdRow')) {
            $valuesToReplace['callbackCreatedRow'] = 'createdRow: ' . $this->getConfig('createdRow') . ',';
        } else {
            $valuesToReplace['callbackCreatedRow'] = '';
        }

        return Text::insert(
            $this->datatableConfigurationTemplate,
            $valuesToReplace
        );
    }

    public function setCallback($callback): void
    {
        $this->setConfig('drawCallback', $callback);
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

        $ajaxType = $this->getConfig('ajaxType');
        $csrfToken = $this->getConfig('csrfToken');

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
            if ($csrfToken !== null) {
                $headers = "headers: { 'X-CSRF-Token': '{$csrfToken}' },";
            } else {
                $headers = "";
            }
            $this->getDataTemplate = <<<GET_DATA
                let getData = async () => {
                    return {
                        {$headers}
                        url:'{$url}',
                        type: '{$ajaxType}',
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
                if ($key['render'] ?? false) {
                    $output .= "\nrender: {$key['render']},";
                }
                if ($key['orderable'] ?? false) {
                    $output .= "\norderable: {$key['orderable']},";
                }
                if ($key['width'] ?? false) {
                    $output .= "\nwidth: '{$key['width']}',";
                }
                if ($key['className'] ?? false) {
                    $output .= "\nclassName: '{$key['className']}',";
                }
            }
            $output .= '}';
            return $output;
        };
        $configColumns = array_map($processor, (array)$this->getConfig('fields'));
        $configRowActions = $processor((array)$this->getConfig('rowActions'));
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
            $links[] = $this->processActionLink($link)->render();
        }

        return $links;
    }

    /**
     * Format link with specified options from links array.
     *
     * @param  array $link
     * @return LinkInterface
     */
    protected function processActionLink(array $link): LinkInterface
    {
        switch ($link['type'] ?? null) {
            case Datatables::LINK_TYPE_DELETE:
            case Datatables::LINK_TYPE_PUT:
            case Datatables::LINK_TYPE_POST:
                $output = new \CakeDC\Datatables\View\LinkFormatter\PostLink($this->Helper, $link);
                break;
            case Datatables::LINK_TYPE_CUSTOM:
                if (!class_exists($link['linkFormatter'] ?? null)) {
                    throw new \OutOfBoundsException("Please specify a custom linkFormatter");
                }
                $output = new $link['linkFormatter']($this->Helper, $link);
                break;
            case Datatables::LINK_TYPE_GET:
            default:
                $output = new \CakeDC\Datatables\View\LinkFormatter\Link($this->Helper, $link);
                break;
        }

        return $output;
    }

    /**
     * Loop columns definitions to set properties inside ColumnDefs as orderable or searchable
     */
    protected function processColumnDefinitionsCallbacks()
    {
        $rows = [];
        foreach ($this->getConfig('definitionColumns') as $definition) {
            $parts = [];
            foreach ($definition as $key => $val) {
                $parts[] = "'{$key}': {$val}";
            }
            $rows[] = '{' . implode(',', $parts) . '}';
        }

        $this->setConfig('definitionColumns', implode(',', $rows));
    }

    /**
     * Loop types into javascript format.
     */
    protected function processColumnTypeSearch(): string
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
            if (isset($key['searchable']) && !filter_var($key['searchable'], FILTER_VALIDATE_BOOLEAN)) {
                $searchTypes[] = [];
            } else {
                if (isset($key['searchInput'])) {
                    $searchTypes[] = [
                        'type' => $key['searchInput']['type'],
                        'data' => (isset($key['searchInput']['options']) ? $key['searchInput']['options'] : []),
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

    /**
     * Set columns definitions as orderable and sortable
     *
     * @param  \Cake\Collection\Collection $dataDefinitions array of definitions in columns as orderable and sortable
     * @return void
     */
    public function setDefinitions(iterable $dataDefinitions)
    {
        $this->setConfig('definitionColumns', $dataDefinitions);
    }
}
