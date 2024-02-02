<?php
declare(strict_types=1);

/**
 * DatatableHelper class helper to generate datatable.
 *
 * @todo check width not working
 * PHP version 7.4
 */
namespace CakeDC\Datatables\View\Helper;

use Cake\View\Helper;
use CakeDC\Datatables\Datatable\Datatable;

/**
 * Datatable helper
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class DatatableHelper extends Helper
{
    protected array $helpers = ['Url', 'Html'];

    /**
     * Default Datatable js library configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
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
        'ajaxType' => 'GET',
        'csrfToken' => null,
        'autoWidth' => false,
        'tableCss' => [
            'width' => '100%',
            'table-layout' => 'fixed',
            'word-wrap' => 'break-word',
        ],
        'delay' => 3000,
    ];

    // @todo maybe array of instances
    private ?Datatable $dtInstance = null;

    /**
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->reset();
    }

    /**
     * @return \CakeDC\Datatables\Datatable\Datatable
     */
    public function reset(): Datatable
    {
        $this->dtInstance = new Datatable($this);

        return $this->dtInstance;
    }

    /**
     * @return \CakeDC\Datatables\Datatable\Datatable
     */
    public function getInstance(): Datatable
    {
        if (empty($this->dtInstance)) {
            return $this->reset();
        }

        return $this->dtInstance;
    }

    /**
     * @param array $fields
     * @return void
     */
    public function setFields(array $fields): void
    {
        $this->dtInstance->setFields($fields);
    }

    /**
     * @param array $rowActions
     * @return void
     */
    public function setRowActions(array $rowActions): void
    {
        $this->dtInstance->setRowActions($rowActions);
    }

    /**
     * Set callback for created row
     */
    public function setCallbackCreatedRow(string $functionCallback): void
    {
        $this->dtInstance->setCallbackCreatedRow($functionCallback);
    }

    /**
     * @param string $tagId
     * @return string
     * @throws \Exception
     */
    public function getDatatableScript(string $tagId): string
    {
        $this->dtInstance->setTableId($tagId);

        if ($this->dtInstance->getConfig('ajaxType') === 'POST') {
            $csrfToken = $this->_View->getRequest()->getAttribute('csrfToken');
            $this->dtInstance->setConfig('csrfToken', $csrfToken);
        }

        return $this->dtInstance->getDatatableScript();
    }

    /**
     * Get formatted table headers
     *
     * @param array|null $tableHeaders
     * @param bool $format
     * @param bool $translate
     * @param array $headersAttrsTr
     * @param array $headersAttrsTh
     * @return string
     * @throws \Exception
     */
    public function getTableHeaders(
        ?array $tableHeaders = null,
        bool $format = false,
        bool $translate = false,
        array $headersAttrsTr = [],
        array $headersAttrsTh = []
    ): string {
        return $this->dtInstance
            ->setHeaders($tableHeaders, [
                'format' => $format,
                'translate' => $translate,
                'headersAttrsTr' => $headersAttrsTr,
                'headersAttrsTh' => $headersAttrsTh,
            ])
            ->getTableHeaders();
    }

    /**
     * Set callback
     */
    public function setCallback(string $functionCallback): void
    {
        $this->dtInstance->setCallback($functionCallback);
    }
}
