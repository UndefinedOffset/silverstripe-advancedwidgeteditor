<?php
class AdvancedWidgetEditorTest extends FunctionalTest {
    protected static $fixture_file='AdvancedWidgetEditorTest.yml';
    
    
    protected $extraDataObjects=array(
                                    'AdvancedWidgetEditorTest_FakePage',
                                    'AdvancedWidgetEditorTest_TestWidget',
                                    'AdvancedWidgetEditorTest_TestObject'
                                );
    
    /**
     * Tests to ensure that the field names are being correctly re-written by php
     */
    public function testFieldNameRewrite() {
        $editor=new AdvancedWidgetAreaEditor('SideBar');
        $widget=new AdvancedWidgetEditorTest_TestWidget();
        $widget->setWidgetEditor($editor);
        
        
        $fields=$widget->AdvancedCMSEditor();
        
        $this->assertRegExp('/^Widget\[SideBar\]\[(\d+)\]\[(.*?)\]$/', $fields->first()->getName());
    }
    
    /**
     * Tests to ensure that the widget editor is correctly being set on UsedWidgets
     */
    public function testUsedWidets() {
        $page=$this->objFromFixture('AdvancedWidgetEditorTest_FakePage', 'testpage');
        
        $form=new Form($page, 'TestForm', new FieldList($editor=new AdvancedWidgetAreaEditor('SideBar')), new FieldList());
        $form->loadDataFrom($page);
        
        $widget=$editor->UsedWidgets()->first();
        $this->assertEquals('Widget[SideBar]['.$widget->ID.']', $widget->AdvancedName());
    }
    
    /**
     * Tests to see if the add widget routing is feeding in correctly and returning the expected response
     */
    public function testAddRouting() {
        $page=$this->objFromFixture('AdvancedWidgetEditorTest_FakePage', 'testpage');
        $page->publish('Stage', 'Live');
        
        $response=$this->get('AdvancedWidgetEditor_TestController/TestForm/field/SideBar/add-widget/AdvancedWidgetEditorTest_TestWidget');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('name="Widget[SideBar][0][Type]"', $response->getBody(), 'Response did not contain the expected widget fields');
    }
    
    /**
     * Tests to see if routing to a field like TreeDropdownField is working
     */
    public function testNestedTreeDropdownRouting() {
        $page=$this->objFromFixture('AdvancedWidgetEditorTest_FakePage', 'testpage');
        $page->publish('Stage', 'Live');
        
        $widget=$page->SideBar()->Widgets()->first();
        
        $response=$this->post(
                    'AdvancedWidgetEditor_TestController/TestForm/field/SideBar/field/AdvancedWidgetEditorTest_TestWidget/field/Widget%5BSideBar%5D%5B'.$widget->ID.'%5D%5BTestLinkID%5D/tree',
                    array(
                        'Widget'=>array(
                                        'SideBar'=>array(
                                                        $widget->ID=>array(
                                                                        'TestObjects'=>array()
                                                                    )
                                                    )
                                    )
                    )
                );
        
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('<ul class="tree">', $response->getBody(), 'Response did not contain the tree');
    }
    
    /**
     * Tests to see if nested routing to a field like GridField is working
     */
    public function testGridFieldRouting() {
        $this->logInWithPermission('ADMIN');
        
        $page=$this->objFromFixture('AdvancedWidgetEditorTest_FakePage', 'testpage');
        $page->publish('Stage', 'Live');
        
        $controller=new AdvancedWidgetEditor_TestController();
        $widget=$controller->TestForm()->Fields()->dataFieldByName('SideBar')->UsedWidgets()->first();
        $gridField=$widget->AdvancedCMSEditor()->dataFieldByName('Widget[SideBar]['.$widget->ID.'][TestObjects]');
        
        
        $response=$this->get($gridField->Link('item/'.$widget->TestObjects()->first()->ID.'/edit?ajax=1'), null, array('X-Requested-With'=>'XMLHttpRequest'));
        
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('id="Form_ItemEditForm"', $response->getBody(), 'Response did not contain the item edit form');
    }
    
    /**
     * Tests to see if the widget editor is saving correctly
     */
    public function testEditorSaving() {
        $this->logInWithPermission('ADMIN');
        
        $page=$this->objFromFixture('AdvancedWidgetEditorTest_FakePage', 'testpage');
        $page->publish('Stage', 'Live');
        
        $controller=new AdvancedWidgetEditor_TestController();
        $widget=$controller->TestForm()->Fields()->dataFieldByName('SideBar')->UsedWidgets()->first();
        
        $img=$this->objFromFixture('Image', 'awesample');
        
        
        $response=$this->post($controller->TestForm()->FormAction(), array(
                                                                            'Widget'=>array(
                                                                                            'SideBar'=>array(
                                                                                                            $widget->ID=array(
                                                                                                                                'Title'=>'Changed Title',
                                                                                                                                'SampleBoolean'=>1,
                                                                                                                                'Image'=>array(
                                                                                                                                                'Files'=>array(
                                                                                                                                                                $img->ID
                                                                                                                                                            )
                                                                                                                                            ),
                                                                                                                                'Sort'=>$widget->Sort,
                                                                                                                                'Type'=>$widget->ClassName
                                                                                                                            )
                                                                                                        )
                                                                                        ),
                                                                            'SideBarID'=>$widget->ParentID,
                                                                            'action_doSave'=>1
                                                                        ), array('X-Requested-With'=>'XMLHttpRequest'));
        
        //Verify we had a 200 response
        $this->assertEquals(200, $response->getStatusCode());
        
        $widget=$controller->TestForm()->Fields()->dataFieldByName('SideBar')->UsedWidgets()->first();
        
        
        //Verify the widget exists
        $this->assertInstanceOf('AdvancedWidgetEditorTest_TestWidget', $widget);
        
        
        //Verify the fields changed
        $this->assertEquals('Changed Title', $widget->Title);
        $this->assertEquals(1, $widget->SampleBoolean);
        $this->assertEquals($img->ID, $widget->ImageID);
    }
}

class AdvancedWidgetEditorTest_FakePage extends Page implements TestOnly {
    private static $has_one=array(
                                'SideBar'=>'WidgetArea'
                            );
}

class AdvancedWidgetEditorTest_TestWidget extends Widget implements TestOnly {
    private static $db=array(
                            'Title'=>'Varchar',
                            'SampleBoolean'=>'Boolean'
                        );
    
    private static $has_one=array(
                                'Image'=>'Image',
                                'TestLink'=>'SiteTree'
                            );
    
    private static $has_many=array(
                                'TestObjects'=>'AdvancedWidgetEditorTest_TestObject'
                            );
    
    
    /**
     * Gets fields used in the cms
     * @return {FieldList} Fields to be used
     */
    public function getCMSFields() {
        return new FieldList(
                            new TextField('Title', 'Title'),
                            new TreeDropdownField('TestLinkID', 'Test Link', 'SiteTree'),
                            new GridField('TestObjects', 'Test Objects', $this->TestObjects(), GridFieldConfig_RecordEditor::create(10)),
                            new UploadField('Image', 'Image'),
                            new CheckboxField('SampleBoolean', 'A simple checkbox')
                        );
    }
    
	public function Title() {
		return $this->Title;
	}
}

class AdvancedWidgetEditorTest_TestObject extends DataObject implements TestOnly {
    private static $db=array(
                            'Title'=>'Varchar'
                        );
    
    private static $has_one=array(
                                'Parent'=>'AdvancedWidgetEditorTest_TestWidget'
                            );
}

class AdvancedWidgetEditor_TestController extends Controller implements TestOnly {
    private static $allowed_actions=array('TestForm');
    
    public function TestForm() {
        $page=AdvancedWidgetEditorTest_FakePage::get()->first();
        
        $form=new Form($this, 'TestForm', new FieldList(new AdvancedWidgetAreaEditor('SideBar')), new FieldList(new FormAction('doSave', 'Save')));
        $form->loadDataFrom($page);
        $form->disableSecurityToken();
        
        return $form;
    }
    
    public function doSave($data, Form $form) {
        $page=AdvancedWidgetEditorTest_FakePage::get()->first();
        $form->saveInto($page);
        $page->write();
        
        return 'HELO';
    }
}
?>