<?php
class AdvancedWidgetFormShiv {
    /**
     * @var AdvancedWidgetAreaEditor
     */
    private $_widgetEditor;
    
    /**
     * @var Widget
     */
    private $_widget;
    
    public $Validator=false;
    
    public function __construct(AdvancedWidgetAreaEditor $widgetEditor, Widget $widget) {
        $this->_widgetEditor=$widgetEditor;
        $this->_widget=$widget;
    }
    
    public function FormName() {
        return ($this->_widgetEditor->getForm() ? $this->_widgetEditor->getForm()->FormName():false);
    }
    
    public function getRecord() {
        return $this->_widget;
    }
    
    public function Controller() {
        return ($this->_widgetEditor->getForm() ? $this->_widgetEditor->getForm()->Controller():false);
    }
    
    public function FormAction() {
        return Controller::join_links($this->_widgetEditor->Link('field'), $this->_widget->ClassName, '');
    }
    
    public function getSecurityToken() {
        return ($this->_widgetEditor->getForm() ? $this->_widgetEditor->getForm()->getSecurityToken():false);
    }
}
?>