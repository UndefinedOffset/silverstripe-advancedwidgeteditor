<?php
namespace UndefinedOffset\AdvancedWidgetEditor\Tests\TestObjects;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Widgets\Model\Widget;

class TestWidget extends Widget implements TestOnly
{
    private static $db = [
        'Title' => 'Varchar',
        'SampleBoolean' => 'Boolean',
    ];

    private static $has_one = [
        'Image' => Image::class,
        'TestLink' => SiteTree::class,
    ];

    private static $has_many = [
        'TestObjects' => TestObject::class,
    ];

    private static $table_name = 'AWETestWidget';


    /**
     * Gets fields used in the cms
     * @return FieldList Fields to be used
     */
    public function getCMSFields()
    {
        return new FieldList(
            new TextField('Title', 'Title'),
            new TreeDropdownField('TestLinkID', 'Test Link', SiteTree::class),
            new GridField('TestObjects', 'Test Objects', $this->TestObjects(), GridFieldConfig_RecordEditor::create(10)),
            new UploadField('Image', 'Image'),
            new CheckboxField('SampleBoolean', 'A simple checkbox')
        );
    }

    public function Title()
    {
        return $this->Title;
    }
}
