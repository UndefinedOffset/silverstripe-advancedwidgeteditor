<?php
namespace UndefinedOffset\AdvancedWidgetEditor\Extensions;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;
use UndefinedOffset\AdvancedWidgetEditor\Forms\AdvancedWidgetAreaEditor;
use UndefinedOffset\AdvancedWidgetEditor\Object\AdvancedWidgetFormShiv;

class AdvancedWidgetEditorInterface extends DataExtension
{
    private $_widgetEditor = null;

    /**
     * Sets the widget editor instance for the owner
     * @param AdvancedWidgetAreaEditor $editor Editor to be used
     */
    public function setWidgetEditor(AdvancedWidgetAreaEditor $editor)
    {
        $this->_widgetEditor = $editor;
    }

    /**
     * Wrapper for generating the display of the widget
     * @param bool $readonly Boolean true if the fields should be rendered as readonly
     * @return string HTML to be used as the display
     */
    public function AdvancedEditableSegment($readonly = false)
    {
        return $this->owner->customise(['IsEditorReadonly' => $readonly])->renderWith(AdvancedWidgetEditorInterface::class);
    }

    /**
     * @return string
     */
    public function AdvancedName()
    {
        return 'Widget[' . $this->_widgetEditor->getName() . '][' . $this->owner->ID . ']';
    }

    /**
     * Gets the fields to be used in the form
     * @param bool $readonly Boolean true if the fields should be rendered as readonly
     * @return FieldList Fields to be used in the form
     */
    public function AdvancedCMSEditor($readonly = false)
    {
        $fields = $this->owner->getCMSFields();


        $this->renameFields($fields, $readonly);


        //If readonly make the whole fieldlist readonly
        if ($readonly) {
            $fields = $fields->makeReadonly();
        }

        return $fields;
    }

    /**
     * Renames the fields for use in the editor
     * @param FieldList|CompositeField $fields Field list or CompositeField
     * @param bool $readonly Boolean true if the fields should be rendered as readonly
     * @param int $depth Recurrsion protection
     */
    final protected function renameFields($fields, $readonly, $depth = 0)
    {
        //Recursion protection
        if ($depth > 10) {
            user_error('Too much recurssion', E_USER_ERROR);
        }


        //Verify we're looking at a FieldList or CompositeField
        if (!($fields instanceof FieldList) && !($fields instanceof CompositeField)) {
            user_error('Argument 1 passed to AdvancedWidgetEditorInterface::renameFields() must be an instance of FieldList or CompositeField', E_USER_ERROR);
        }


        //Loop through each field and rename
        foreach ($fields as $field) {
            $field->setForm(new AdvancedWidgetFormShiv($this->_widgetEditor, $this->owner));

            $name = $field->getName();
            if (isset($this->owner->$name) || $this->owner->hasMethod($name) || ($this->owner->hasMethod('hasField') && $this->owner->hasField($name))) {
                $field->setValue($this->owner->__get($name), $this->owner);
            }

            //Workaround for UploadField fixes an issue detecting if the relationship is a has_one relationship
            if ($field instanceof UploadField && $this->owner->has_one($name)) {
                $field->setAllowedMaxFileNumber(1);
            }


            $name = preg_replace("/([A-Za-z0-9\-_]+)/", 'Widget[' . $this->_widgetEditor->getName() . '][' . $this->owner->ID . '][$1]', $name);
            $field->setName($name);


            //Fix the gridstate field
            if ($field instanceof GridField) {
                if ($readonly) {
                    $field = ReadonlyField::create($name . '[GridState]', $field->getTitle());
                } else {
                    $field->getState(false)->setName($name . '[GridState]');
                }
            }


            //Fix display_logic
            if ($field->hasMethod('getDisplayLogicCriteria') && $field->getDisplayLogicCriteria() && $field->getDisplayLogicCriteria()->hasMethod('prefixMasters')) {
                $field->getDisplayLogicCriteria()->prefixMasters('Widget[' . $this->_widgetEditor->getName() . '][' . $this->owner->ID . ']');
            }


            //If we're looking at a FieldList or Composite Field recurrse down into it
            if ($field instanceof CompositeField) {
                $depth++;
                $this->renameFields($field->FieldList(), $readonly, $depth);
            }
        }
    }
}
