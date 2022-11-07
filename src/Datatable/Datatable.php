<?php

declare(strict_types=1);

namespace CakeDC\Datatables\Datatable;

use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Inflector;
use Exception;

class Datatable
{
    use InstanceConfigTrait;

    /**
     * Default config for this helper.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'tableId' => null,
        'headers' => [],
        'fields' => [],
    ];

    protected $Helper;

    public function __construct($Helper, $config = [])
    {
        $this->Helper = $Helper;
        $this->setConfig($config, null, true);
    }


    public function setHeaders(array $headers, array $configs = [])
    {
        $this->setConfig('headers', $headers);
        $this->setConfig('headersConfig', $configs, true);

        return $this;
    }

    public function setFields(array $fields)
    {
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

        return 'console.log("from getDatatableScript")';
    }

    public function getCommonScript(): string
    {

        return 'console.log("from getCommonScript")';
    }
}
