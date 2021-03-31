<?php
namespace UndefinedOffset\AdvancedWidgetEditor\Model;

use SilverStripe\ORM\HasManyList;
use UndefinedOffset\AdvancedWidgetEditor\Forms\AdvancedWidgetAreaEditor;

class AdvancedWidgetsHasManyList extends HasManyList
{
    private $_widgetEditor = null;

    /**
     * Sets the widget editor used in this list
     * @param AdvancedWidgetAreaEditor $editor
     */
    public function setWidgetEditor(AdvancedWidgetAreaEditor $editor)
    {
        $this->_widgetEditor = $editor;
        return $this;
    }

    /**
     * Create a DataObject from the given SQL row
     * @param array $row
     * @return DataObject
     */
    public function createDataObject($row)
    {
        $item = parent::createDataObject($row);

        $item->setWidgetEditor($this->_widgetEditor);

        return $item;
    }
}
