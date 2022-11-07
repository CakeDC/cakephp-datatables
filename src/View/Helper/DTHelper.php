<?php
declare(strict_types=1);

namespace CakeDC\Datatables\View\Helper;

use Cake\Utility\Text;
use Cake\View\Helper;
use CakeDC\Datatables\Datatable\Datatable;

/**
 * DT helper
 * 
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class DTHelper extends Helper
{

    protected $helpers = ['Html', 'Url'];

    protected bool $_commonScript = false;

    /**
     * Default configuration.
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

        'headersConfig' => [
            'format' => false,
            'translate' => false,
            'headersAttrsTr' => [],
            'headersAttrsTh' => [],
        ],
        'rowActions' => [], // default rowActions
    ];


    public function initialize(array $config): void
    {
        // @todo load config from file
    }

    public function newInstance(): Datatable
    {
        return new Datatable($this, $this->getConfig());
    }

    public function render(Datatable $dtInstance, array $tableOptions = [])
    {
        if (empty($tableOptions['id'])) {
            $tableOptions['id'] = Text::uuid();
        }
        $dtInstance->setConfig('tableId', $tableOptions['id']);

        $output = $this->Html->tag('table', '<thead>' . $dtInstance->getTableHeaders() . '</thead>', $tableOptions);
        
        if (!$this->_commonScript) {
            $this->Html->scriptBlock($dtInstance->getCommonScript(), ['block' => true]);
            $this->_commonScript = true;
        }

        $this->Html->scriptBlock($dtInstance->getDatatableScript(), ['block' => true]);

        return $output;
    }
}
