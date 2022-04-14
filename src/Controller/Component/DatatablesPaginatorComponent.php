<?php
declare(strict_types=1);

namespace CakeDC\Datatables\Controller\Component;

use Cake\Controller\Component\PaginatorComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Datasource\ResultSetInterface;
use Cake\Datasource\SimplePaginator;
use Cake\Http\ServerRequest;
use Cake\Log\Log;

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

    /**
     * Usinb SimplePaginator by default, override if needed
     *
     * @param \Cake\Controller\ComponentRegistry $registry
     * @param array $config
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        if (!isset($config['paginator'])) {
            $config['paginator'] = new SimplePaginator();
        }
        parent::__construct($registry, $config);
    }

    public function paginate(object $object, array $settings = []): ResultSetInterface
    {
        // translate query params
        $request = $this->_registry->getController()->getRequest();
        Log::error(json_encode($request->getQuery()));
        Log::error(json_encode($request->getQuery('columns')));
        Log::error(json_encode($request->getQuery('search')));
        Log::error(json_encode($request->getQuery('order')));
        Log::error(json_encode($request->getQuery('start')));
        Log::error(json_encode($request->getQuery('length')));
        $settings = $this->applyOrder($request, $settings);
        $settings = $this->applyLimits($request, $settings);
        Log::debug(json_encode($settings));
        $resultSet = parent::paginate($object, $settings);
        // translate paging options
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

        return $settings;
    }
}
