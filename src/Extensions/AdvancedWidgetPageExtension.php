<?php
namespace UndefinedOffset\AdvancedWidgetEditor\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Widgets\Extensions\WidgetPageExtension;
use SilverStripe\Widgets\Model\WidgetArea;
use UndefinedOffset\AdvancedWidgetEditor\Forms\AdvancedWidgetAreaEditor;

class AdvancedWidgetPageExtension extends WidgetPageExtension
{
    private static $db = [
        'InheritSideBar' => 'Boolean',
    ];

    private static $defaults = [
        'InheritSideBar' => true,
    ];

    private static $has_one = [
        'SideBar' => WidgetArea::class,
    ];

    /**
     * Updates the fields used in the cms
     * @param FieldList $fields Fields to be extended
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Widgets', new CheckboxField('InheritSideBar', _t(__CLASS__ . '.INHERIT_SIDEBAR', 'Inherit Sidebar From Parent')));

        $fields->addFieldToTab('Root.Widgets', new AdvancedWidgetAreaEditor('SideBar'));
    }
}
