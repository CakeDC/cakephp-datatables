<?php
declare(strict_types=1);

namespace Cakephp-datatables\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Cakephp-datatables\View\Helper\DatatableHelper;

/**
 * Datatables\View\Helper\DatatableHelper Test Case
 */
class DatatableHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Cakephp-datatables\View\Helper\DatatableHelper
     */
    protected $Datatable;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $view = new View();
        $this->Datatable = new DatatableHelper($view);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Datatable);

        parent::tearDown();
    }
}
