<?php
declare(strict_types=1);

namespace CakeDC\Datatables;

class Datatables
{
    public const LINK_TYPE_GET = 'GET';
    public const LINK_TYPE_POST = 'POST';
    public const LINK_TYPE_PUT = 'PUT';
    public const LINK_TYPE_DELETE = 'DELETE';
	public const LINK_TYPE_CUSTOM = 'CUSTOM';

    public static function postLinkMethods()
    {
        return [
            static::LINK_TYPE_POST,
        ];
    }
}
