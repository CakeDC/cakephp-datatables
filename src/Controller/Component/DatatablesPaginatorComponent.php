<?php
declare(strict_types=1);

namespace CakeDC\Datatables\Controller\Component;

use Cake\Controller\Component\PaginatorComponent;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\ServerRequest;

/**
 * DatatablesPaginator component
 */
class DatatablesPaginatorComponent extends PaginatorComponent
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
    ];

    public function paginate(object $object, array $settings = []): ResultSetInterface
    {
        $request = $this->_registry->getController()->getRequest();
        $settings = $this->applyOrder($request, $settings);
        $settings = $this->applyLimits($request, $settings);
        $resultSet = parent::paginate($object, $settings);

        return $resultSet;
    }

    /**
     * Translate between datatables and CakePHP pagination order
     *
     * @param \Cake\Http\ServerRequest $request
     * @param array $settings
     * @return array
     */
    protected function applyOrder(ServerRequest $request, array $settings): array
    {
        // translate ordering
        $dtColumns = $request->getQuery('columns');
        $dtOrders = (array)$request->getQuery('order');
        foreach ($dtOrders as $dtOrder) {
            $colIndex = (int)($dtOrder['column'] ?? 0);
            $colOrder = $dtOrder['dir'] ?? 'asc';
            $colOrderable = $dtColumns[$colIndex]['orderable'] ?? null;
            if (!$colOrderable === 'true') {
                continue;
            }
            $colName = $dtColumns[$colIndex]['data'];
            $settings['order'][$colName] = $colOrder;
        }

        return $settings;
    }

    /**
     * Translate limit and offset from datatables
     *
     * @param \Cake\Http\ServerRequest $request
     * @param array $settings
     * @return array
     */
    protected function applyLimits(ServerRequest $request, array $settings): array
    {
        $dtStart = (int)$request->getQuery('start');
        $dtLength = (int)$request->getQuery('length');

        $settings['limit'] = $dtLength;
        if ($dtStart === 0) {
            $settings['page'] = 1;
        } else {
            $settings['page'] = intdiv($dtStart, $dtLength) + 1;
        }

        return $settings;
    }

    /**
     * Translate the CakePHP pagination paging attribute into response keys for the datatables to consume
     *
     * @param $resultSet
     * @return void
     */
    public function prepareResponse($resultSet): void
    {
        $pagingData = $this->getController()->getRequest()->getAttribute('paging');
        if (is_array($pagingData) && count($pagingData) === 1) {
            $pagingData = reset($pagingData);
        }
        $this->getController()->set([
            'data' => $resultSet,
            'recordsTotal' => $pagingData['count'] ?? 0,
            'recordsFiltered' => $pagingData['count'] ?? 0,
        ]);
        $this->getController()->viewBuilder()->setOption('serialize', [
            'data',
            'draw',
            'recordsTotal',
            'recordsFiltered',
        ]);
    }

    /**
     * Translate request query params from datatables into search queries for cakephp
     * Extracting parameters from the structure provided in datatables like
     * {"data":"title","name":"","searchable":"true","orderable":"true","search":{"value":"((((a))))","regex":"false"}}
     * into ?title=a
     *
     * @return void
     */
    public function prepareRequestQueryParams(): void
    {
        // translate ordering
        $request = $this->getController()->getRequest();
        $dtColumns = $request->getQuery('columns');
        $newQueryParams = [];
        foreach ($dtColumns as $dtColumn) {
            if (($dtColumn['searchable'] ?? null) !== 'true') {
                continue;
            }
            $colName = $dtColumn['data'];
            if ($request->getQuery($colName)) {
                // we already have a query param with values, ignoring this column
                continue;
            }
            $colSearch = $dtColumn['search']['value'] ?? null;
            $colSearch = ltrim($colSearch, '(');
            $colSearch = rtrim($colSearch, ')');
            $colSearch = trim($colSearch);
            //@todo: enable regexp based search option
            if (!$colSearch) {
                continue;
            }
            $newQueryParams[$colName] = $colSearch;
        }
        $request = $request->withQueryParams($request->getQueryParams() + $newQueryParams);
        $this->getController()->setRequest($request);
    }
}
