<?php
namespace UndefinedOffset\AdvancedWidgetEditor\Tests\TestObjects;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Widgets\Model\WidgetArea;
use Page;

class FakePage extends Page implements TestOnly
{
    private static $has_one = [
        'SideBar' => WidgetArea::class,
    ];

    private static $table_name = 'AWEFakePage';

    private static $owns = [
        'SideBar',
    ];

    private static $cascade_deletes = [
        'SideBar',
    ];
}
