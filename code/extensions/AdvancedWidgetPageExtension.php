<?php
class AdvancedWidgetPageExtension extends WidgetPageExtension
{
    private static $db = [
                            'InheritSideBar' => 'Boolean',
                        ];
    
    private static $defaults = [
                                    'InheritSideBar' => true,
                                ];
    
    private static $has_one = [
                                'SideBar' => 'WidgetArea',
                            ];
    
    /**
     * Updates the fields used in the cms
     * @param FieldList $fields Fields to be extended
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Widgets', new CheckboxField('InheritSideBar', _t('AdvancedWidgetPageExtension.INHERIT_SIDEBAR', 'Inherit Sidebar From Parent')));
        
        $fields->addFieldToTab('Root.Widgets', new AdvancedWidgetAreaEditor('SideBar'));
    }
}
