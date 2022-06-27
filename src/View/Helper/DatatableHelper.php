<?php
/**
 * DatatableHelper class helper to generate datatable.
 *
 * @todo check width not working
 * PHP version 7.4
 */

declare(strict_types=1);

namespace CakeDC\Datatables\View\Helper;

use InvalidArgumentException;
use Datatables\Exception\MissConfiguredException;
use Cake\View\View;
use Cake\View\Helper;
use Cake\Utility\Inflector;
use CakeDC\Datatables\Datatables;

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
    ];

    private $columnSearchTemplate = <<<COLUMN_SEARCH_CONFIGURATION

        var api = this.api();

        var columnsSearch = %s;   

        // For each column
        api
        .columns()
        .eq(0)
        .each(function (colIdx) {
            var cell = $('.filters th').eq(
                $(api.column(colIdx).header()).index()
            );
            switch (columnsSearch[colIdx].type) {
                case 'select' : 
                        cell.html('<select class="form-control input-sm"><option value=""></option></select>');
                        columnsSearch[colIdx].data.forEach(function (data) {
                            $(
                                'select',
                                $('.filters th').eq($(api.column(colIdx).header()).index())
                            ).append(
                                '<option value="' + data.id + '">' + data.name + '</option>'
                            );
                        });
                        $(
                            'select',
                            $('.filters th').eq($(api.column(colIdx).header()).index())
                        )
                        .on('change', function () {
                            api.column(colIdx).search(this.value).draw();
                        });
                    break;
                
                case 'date':
                        cell.html('<input type="text" class="form-control input-sm datepicker" placeholder="'+ cell.text() +'" />');
                        cell.on('keyup change', function () {
                            api.column(colIdx).search(this.value).draw();
                        });
                    break;
                case 'input':
                case 'default':
                    case 'input':
                        cell.html('<input type="text" class="form-control input-sm" placeholder="'+ cell.text() +'" />');
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

     /**
      * @var string[]
      */
    private $searchHeadersTypes =[];

    /**
     *  Inicializate function
     * 
     * @param  View $view
     * @param  array $config
     * 
     * @return void 
     */
    
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
     * @param string|array $key   key to write
     * @param string|array $value value to write
     * @param bool         $merge merge
     * 
     * @return void
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
    public function setGetDataUrl($url = null)
    {
        $url = (array)$url;
        $url = array_merge($url, ['fullBase' => true, '_ext' => 'json']);
        $url = $this->Url->build($url);

        if (!empty($this->getConfig('extraFields'))) {
            $extraFields = $this->processExtraFields();
            //@todo change to async or anonymous js function
            $this->getDataTemplate = <<<GET_DATA
            function getData() {                
                return {
                    url:'{$url}',    
                    data: function ( d ) {
                            return $.extend( {}, d, {                            
                                $extraFields
                            });
                        }                            
                }      
            }    
            GET_DATA;
        } else {
            $this->getDataTemplate = <<<GET_DATA
                let getData = async () => {
                    let res = await fetch('{$url}')
                }
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
            'width' => '30px',
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
     * @param  string $tagId
     * 
     * @return string
     */
    public function getDatatableScript(string $tagId): string
    {
        if (empty($this->getDataTemplate)) {
            $this->setGetDataUrl();
        }

        $this->processColumnRenderCallbacks();
        $this->processColumnDefinitionsCallbacks();
        $this->searchHeadersTypes= $this->processColumnTypeSearch();
        $this->validateConfigurationOptions();
        
        $this->columnSearchTemplate = sprintf($this->columnSearchTemplate, $this->searchHeadersTypes);

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
     * Loop types into javascript format.
     */
    protected function processColumnTypeSearch()
    {

        if ($this->getConfig('searchHeadersType') !== null) {
            $this->setTableTypeSearch($this->Config('searchHeadersType'));
        } elseif ($this->searchHeadersTypes === null) {
            throw new MissConfiguredException(__('Search headers type not configured'));
        }
        
        $rows = [];
        foreach ($this->searchHeadersTypes as $definition) {
            $parts = [];
            
            foreach ($definition as $parKey => $parVal) {
                if ($parKey=='data') {
                        
                    if (!empty($parVal) and is_array($parVal)) {    
                        $dataPars = [];
                           
                        foreach ($parVal as $v) {
                            $dataPars[] = "{'id': '".$v['id']."', 'name': '".$v['name']."'}";
                        }
                        $data = '['. implode(',', $dataPars) .']';     
                    } else {
                        $data = '""';
                    }
                    $parts[] = "'{$parKey}': {$data}";
                } else {  
                    $parts[] = "'{$parKey}': '{$parVal}'"; 
                }
            }
                $rows[] = '{'. implode(',', $parts) .'}';  
        }
        return '['. implode(',', $rows) .']';
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
                    $output .= '}';
                } elseif ($key['render'] ?? null) {
                    $output .= "render: {$key['render']}";
                } elseif ($key['orderable'] ?? null) {
                    $output .= "orderable: {$key['orderable']}";
                } elseif ($key['width'] ?? null) {
                    $output .= "width: '{$key['width']}'";
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
     * 
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
     * @param bool          $format
     * @param bool          $translate
     * @param array         $headersAttrsTr
     * @param array         $headersAttrsTh
     * 
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

    /**
     * Put Definition of types of search in headers
     * 
     * @param iterable|null $tableSearchHeaders - array of search headers
     * 
     * @return string
     */
    public function setTableTypeSearch(?iterable $tableSearchHeaders = null):void
    {
        if ($tableSearchHeaders === null) {
            $this->searchHeadersTypes= $this->_fillDefaulTypes(count($this->dataKeys));
        } elseif (count($tableSearchHeaders) != count($this->dataKeys)) {
            
            throw new MissConfiguredException(
                __('Number of columns in search headers must be equal to number of columns in searchable columns')
            );
        } else {
            $this->searchHeadersTypes = $tableSearchHeaders;
        }
     
        return;
    }

    /**
     * Get variable with type of search in headers
     * 
     * @return array
     */
    public function getSearchHedadersTypes() 
    {
        return $this->searchHeadersTypes;
    }

    /**
     * Fill default types for search headers
     * 
     * @param int $count Number of columns in searchable columns
     * 
     * @return array
     */
    private function _fillDefaulTypes(int $count):array
    {
        $searchTypes = [];
        for ($i = 0; $i < $count; $i++) {
            $searchTypes[] = ['type'=>'input', 'data'=>[]];
        }
        
        return $searchTypes;
    }
}
