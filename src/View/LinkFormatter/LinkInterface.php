<?php
declare(strict_types=1);

namespace CakeDC\Datatables\View\LinkFormatter;

use CakeDC\Datatables\View\Helper\DatatableHelper;

/**
 * LinkInterface
 */
interface LinkInterface
{
    /**
     * Constructor
     */
    public function __construct(DatatableHelper $helper, array $config = []);

    /**
     * Initialize
     */
    public function initialize(array $config = []): void;

    /**
     * Render
     */
    public function render(): string;
}
