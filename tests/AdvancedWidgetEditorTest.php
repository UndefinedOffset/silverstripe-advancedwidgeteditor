<?php
namespace UndefinedOffset\AdvancedWidgetEditor\Tests;

use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Dev\FunctionalTest;
use UndefinedOffset\AdvancedWidgetEditor\Forms\AdvancedWidgetAreaEditor;
use UndefinedOffset\AdvancedWidgetEditor\Tests\TestObjects\FakePage;
use UndefinedOffset\AdvancedWidgetEditor\Tests\TestObjects\TestController;
use UndefinedOffset\AdvancedWidgetEditor\Tests\TestObjects\TestObject;
use UndefinedOffset\AdvancedWidgetEditor\Tests\TestObjects\TestWidget;

class AdvancedWidgetEditorTest extends FunctionalTest
{
    protected static $fixture_file = 'AdvancedWidgetEditorTest.yml';

    protected static $extra_dataobjects = [
        FakePage::class,
        TestWidget::class,
        TestObject::class,
    ];

    protected static $extra_controllers = [
        TestController::class,
    ];

    protected $autoFollowRedirection = false;
    protected $depSettings = null;

    public function setUp(): void
    {
        parent::setUp();

        Director::config()->update('alternate_base_url', '/');

        /** @var Image $file **/
        $file = $this->objFromFixture(Image::class, 'awesample');
        if (!$file->exists()) {
            $file->setFromLocalFile(dirname(__FILE__) . '/assets/awe-sample.jpg');
            $file->write();
        }
    }

    /**
     * Tests to ensure that the field names are being correctly re-written by php
     */
    public function testFieldNameRewrite()
    {
        $editor = new AdvancedWidgetAreaEditor('SideBar');
        $widget = new TestWidget();
        $widget->setWidgetEditor($editor);


        $fields = $widget->AdvancedCMSEditor();

        $this->assertMatchesRegularExpression('/^Widget\[SideBar\]\[(\d+)\]\[(.*?)\]$/', $fields->first()->getName());
    }

    /**
     * Tests to ensure that the widget editor is correctly being set on UsedWidgets
     */
    public function testUsedWidets()
    {
        $page = $this->objFromFixture(FakePage::class, 'testpage');
        $page->doPublish();

        $controller = new TestController();
        $editor = $controller->TestForm()->Fields()->dataFieldByName('SideBar');

        $widget = $editor->UsedWidgets()->first();
        $this->assertEquals('Widget[SideBar][' . $widget->ID . ']', $widget->AdvancedName());
    }

    /**
     * Tests to see if the add widget routing is feeding in correctly and returning the expected response
     */
    public function testAddRouting()
    {
        $page = $this->objFromFixture(FakePage::class, 'testpage');
        $page->doPublish();

        $response = $this->get(Controller::join_links('AWETestController/TestForm/field/SideBar/add-widget/', str_replace('\\', '_', TestWidget::class)));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('name="Widget[SideBar][0][Type]"', $response->getBody(), 'Response did not contain the expected widget fields');
    }

    /**
     * Tests to see if routing to a field like TreeDropdownField is working
     */
    public function testNestedTreeDropdownRouting()
    {
        $this->logInWithPermission('ADMIN');

        $page = $this->objFromFixture(FakePage::class, 'testpage');
        $page->doPublish();

        $controller = new TestController();
        $widget = $controller->TestForm()->Fields()->dataFieldByName('SideBar')->UsedWidgets()->first();
        $treeDropdown = $widget->AdvancedCMSEditor()->dataFieldByName('Widget[SideBar][' . $widget->ID . '][TestLinkID]');

        $response = $this->get($treeDropdown->Link('tree'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<ul class="tree">', $response->getBody(), 'Response did not contain the tree');
    }

    /**
     * Tests to see if nested routing to a field like GridField is working
     */
    public function testGridFieldRouting()
    {
        $this->logInWithPermission('ADMIN');

        $page = $this->objFromFixture(FakePage::class, 'testpage');
        $page->doPublish();

        $controller = new TestController();
        $widget = $controller->TestForm()->Fields()->dataFieldByName('SideBar')->UsedWidgets()->first();
        $gridField = $widget->AdvancedCMSEditor()->dataFieldByName('Widget[SideBar][' . $widget->ID . '][TestObjects]');


        $response = $this->get($gridField->Link('item/' . $widget->TestObjects()->first()->ID . '/edit'), null, ['X-Requested-With' => 'XMLHttpRequest']);


        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('id="Form_ItemEditForm"', $response->getBody(), 'Response did not contain the item edit form');
    }

    /**
     * Tests to see if the widget editor is saving correctly
     */
    public function testEditorSaving()
    {
        $this->logInWithPermission('ADMIN');

        $page = $this->objFromFixture(FakePage::class, 'testpage');
        $page->doPublish();

        $controller = new TestController();
        $widget = $controller->TestForm()->Fields()->dataFieldByName('SideBar')->UsedWidgets()->first();

        $img = $this->objFromFixture(Image::class, 'awesample');

        $response = $this->post(
            $controller->TestForm()->FormAction() . '?stage=Stage',
            [
                'Widget' => [
                    'SideBar' => [
                        $widget->ID => [
                            'Title' => 'Changed Title',
                            'SampleBoolean' => 1,
                            'Image' => [
                                'Files' => [
                                    $img->ID,
                                ],
                            ],
                            'Sort' => $widget->Sort,
                            'Type' => $widget->ClassName,
                        ],
                    ],
                ],
                'SideBarID' => $widget->ParentID,
                'action_doSave' => 1,
            ],
            [
                'X-Requested-With' => 'XMLHttpRequest',
            ]
        );


        //Verify we had a 200 response
        $this->assertEquals(200, $response->getStatusCode());


        //Re-fetch the widget so we have the current instance
        $widget = $controller->TestForm()->Fields()->dataFieldByName('SideBar')->UsedWidgets()->first();


        //Verify the widget exists
        $this->assertInstanceOf(TestWidget::class, $widget);


        //Verify the fields changed
        $this->assertEquals('Changed Title', $widget->Title);
        $this->assertEquals(1, $widget->SampleBoolean);
        $this->assertEquals($img->ID, $widget->ImageID);
    }
}
