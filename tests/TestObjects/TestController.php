<?php
namespace UndefinedOffset\AdvancedWidgetEditor\Tests\TestObjects;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use UndefinedOffset\AdvancedWidgetEditor\Forms\AdvancedWidgetAreaEditor;

class TestController extends Controller implements TestOnly
{
    private static $allowed_actions = [
        'TestForm',
    ];

    private static $url_segment = 'AWETestController';

    public function TestForm(): Form
    {
        $page = FakePage::get()->first();

        $form = new Form(
            $this,
            'TestForm',
            new FieldList(
                new AdvancedWidgetAreaEditor('SideBar')
            ),
            new FieldList(
                new FormAction('doSave', 'Save')
            )
        );
        $form->loadDataFrom($page);
        $form->disableSecurityToken();

        return $form;
    }

    public function doSave($data, Form $form)
    {
        $page = FakePage::get()->first();
        $form->saveInto($page);
        $page->write();

        return 'HELO';
    }
}
