<?php
class AdvancedWidgetEditorInterface extends DataExtension {
    private $_widgetEditor=null;
    
    /**
     * Sets the widget editor instance for the owner
     * @param {AdvancedWidgetAreaEditor} $editor Editor to be used
     */
    public function setWidgetEditor(AdvancedWidgetAreaEditor $editor) {
        $this->_widgetEditor=$editor;
    }
    
    /**
     * Wrapper for generating the display of the widget
     * @param {bool} $readonly Boolean true if the fields should be rendered as readonly
     * @return {string} HTML to be used as the display
     */
    public function AdvancedEditableSegment($readonly=false) {
        return $this->owner->customise(array('IsEditorReadonly'=>$readonly))->renderWith('AdvancedWidgetEditor');
    }

    /**
     * @return string
     */
    public function AdvancedName() {
        return 'Widget['.$this->_widgetEditor->getName().']['.$this->owner->ID.']';
    }
    
    /**
     * Gets the fields to be used in the form
     * @param {bool} $readonly Boolean true if the fields should be rendered as readonly
     * @return {FieldList} Fields to be used in the form
     */
    public function AdvancedCMSEditor($readonly=false) {
        $fields=$this->owner->getCMSFields();
        $outputFields=new FieldList();
        
        foreach($fields as $field) {
            $field->setForm(new AdvancedWidgetFormShiv($this->_widgetEditor, $this->owner));
            
            $name=$field->getName();
            if(isset($this->owner->$name) || $this->owner->hasMethod($name) || ($this->owner->hasMethod('hasField') && $this->owner->hasField($name))) {
                $field->setValue($this->owner->__get($name), $this->owner);
            }
            
            //Workaround for UploadField fixes an issue detecting if the relationship is a has_one relationship
            if($field instanceof UploadField && $this->owner->has_one($name)) {
                $field->setAllowedMaxFileNumber(1);
            }
            
            
            $name=preg_replace("/([A-Za-z0-9\-_]+)/", "Widget[".$this->_widgetEditor->getName()."][".$this->owner->ID."][\\1]", $name);
            $field->setName($name);
            
            
            //Fix the gridstate field
            if($field instanceof GridField) {
                $field->getState(false)->setName($name.'[GridState]');
            }
            
            
            $outputFields->push($field);
        }
        
        
        //If readonly make the whole fieldlist readonly
        if($readonly) {
            $outputFields=$outputFields->makeReadonly();
        }
        
        return $outputFields;
    }
}
?>