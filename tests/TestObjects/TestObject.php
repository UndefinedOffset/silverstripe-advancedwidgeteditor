<?php
namespace UndefinedOffset\AdvancedWidgetEditor\Tests\TestObjects;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $has_one = [
        'Parent' => TestWidget::class,
    ];

    private static $table_name = 'AWETestObject';
}
