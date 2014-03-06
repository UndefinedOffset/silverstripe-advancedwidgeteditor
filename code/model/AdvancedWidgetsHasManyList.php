<?php
class AdvancedWidgetsHasManyList extends HasManyList {
    private $_widgetEditor=null;
    
    public function setWidgetEditor(AdvancedWidgetAreaEditor $editor) {
        $this->_widgetEditor=$editor;
        return $this;
    }
    
    /**
     * Create a DataObject from the given SQL row
     *
     * @param array $row
     * @return DataObject
     */
    protected function createDataObject($row) {
        $item=parent::createDataObject($row);
        
        $item->setWidgetEditor($this->_widgetEditor);
        
        return $item;
    }
}
?>