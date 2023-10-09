<?php
declare(strict_types=1);

namespace CakeDC\Datatables\View\LinkFormatter;

use Cake\View\Helper;

/**
 * LinkInterface
 */
interface LinkInterface
{
    /**
     * Constructor
     */
    public function __construct(Helper $helper, array $config = []);

    /**
     * Initialize
     */
    public function initialize(array $config = []): void;

    /**
     * Render
     */
    public function render(): string;
}
