<?php

declare(strict_types=1);

namespace CakeDC\Datatables\View\LinkFormatter;

use Cake\View\Helper;

interface LinkInterface
{
    public function __construct(Helper $helper, array $config = []);
    public function initialize(array $config = []): void;
    public function render(): string;
}