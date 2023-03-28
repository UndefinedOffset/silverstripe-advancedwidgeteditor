<?php
namespace UndefinedOffset\AdvancedWidgetEditor\Forms;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\HasManyList;
use SilverStripe\View\Requirements;
use SilverStripe\Widgets\Forms\WidgetAreaEditor;
use SilverStripe\Widgets\Model\Widget;
use UndefinedOffset\AdvancedWidgetEditor\Model\AdvancedWidgetsHasManyList;
use UndefinedOffset\AdvancedWidgetEditor\Object\AdvancedWidgetFormShiv;

class AdvancedWidgetAreaEditor extends WidgetAreaEditor
{
    private static $allowed_actions = [
        'handleField',
        'handleAddWidgetEditor',
    ];

    private static $url_handlers = [
        'field/$Type/field/$FieldName!' => 'handleField',
        'add-widget/$ClassName!' => 'handleAddWidgetEditor',
    ];

    /**
     * @param array $properties
     * @return string HTML
     */
    public function FieldHolder($properties = [])
    {
        Requirements::css('silverstripe/widgets: css/WidgetAreaEditor.css');

        if ($this->isReadonly() || $this->isDisabled()) {
            Requirements::css('undefinedoffset/silverstripe-advancedwidgeteditor: css/AdvancedWidgetAreaEditor_readonly.css');

            return $this->renderWith(AdvancedWidgetAreaEditor::class . '_readonly');
        }


        Requirements::javascript('silverstripe/widgets: javascript/WidgetAreaEditor.js');
        Requirements::javascript('undefinedoffset/silverstripe-advancedwidgeteditor: javascript/AdvancedWidgetAreaEditor.js');

        return $this->renderWith(AdvancedWidgetAreaEditor::class);
    }

    /**
     * Gets the available widgets
     * @return ArrayList
     */
    public function AvailableWidgets()
    {
        $widgets = new ArrayList();

        foreach ($this->widgetClasses as $widgetClass) {
            $classes = ClassInfo::subclassesFor($widgetClass);
            unset($classes[strtolower(Widget::class)]);

            $record = $this->form->getRecord();
            $availableWidgets = null;
            $restrictedWidgets = null;
            if ($record) {
                $availableWidgets = $record->config()->available_widgets;
                $restrictedWidgets = $record->config()->restricted_widgets;
            }


            foreach ($classes as $class) {
                if ((!empty($availableWidgets) && is_array($availableWidgets) && !in_array($class, $availableWidgets)) || (!empty($restrictedWidgets) && is_array($restrictedWidgets) && in_array($class, $restrictedWidgets))) {
                    continue;
                }

                $available = $class::config()->only_available_in;
                if (!empty($available) && is_array($available)) {
                    if (in_array($this->Name, $available)) {
                        $widgets->push($class::singleton());
                    }
                } else {
                    $widgets->push($class::singleton());
                }
            }
        }

        return $widgets;
    }

    /**
     * Gets the widgets used in the current area
     * @return HasManyList
     */
    public function UsedWidgets()
    {
        $relationName = $this->name;
        $widgets = $this->form->getRecord()->getComponent($relationName)->Widgets();

        if ($widgets instanceof HasManyList) {
            $widgets = AdvancedWidgetsHasManyList::create($widgets->dataClass(), $widgets->getForeignKey())
                ->setDataQuery($widgets->dataQuery())
                ->setWidgetEditor($this);
        }

        return $widgets;
    }

    /**
     * Handles a field request
     * @param \SilverStripe\Control\HTTPRequest $request
     * @return mixed
     */
    public function handleField($request)
    {
        $className = str_replace('_', '\\', $request->param('Type'));
        $fieldName = rawurldecode($request->param('FieldName'));
        $realFieldName = preg_replace('/^Widget\[(.*?)\]\[(.*?)\]\[(.*?)\]$/', '$3', $fieldName);
        $baseURL = preg_replace('/\?(.*?)$/', '', $this->Link('field')); //Get the base link stripping parameters
        $baseURL = Controller::join_links($baseURL, $className, 'field', $request->param('FieldName'), '/');


        //Parse field name for the id
        $objId = preg_replace('/Widget\[(.*?)\]\[(.*?)\]\[(.*?)\]/', '$2', $fieldName);
        if (class_exists($className) && is_subclass_of($className, Widget::class)) {
            if (is_numeric($objId)) {
                $obj = $this->UsedWidgets()->byID(intval($objId));
                if (empty($obj) || $obj === false || !$obj->exists()) {
                    return $this->httpError(404, 'Widget not found');
                }
            } else {
                $obj = singleton($className);
            }
        } else {
            return $this->httpError(404, 'Widget not found');
        }


        $field = $obj->getCMSFields()->dataFieldByName($realFieldName);
        if ($field) {
            $field->setForm($this->getFormShiv($obj));

            //Replace the request, we need the post variables to appear as if the widgets are in the top field
            $request = $this->getFakeRequest($request, $obj, $baseURL);

            //Shift the real request by the remaining positions, we assume the field will handle the rest
            $this->request->shift(substr_count($this->request->remaining(), '/') + 1);

            $field->setName('Widget[' . $this->getName() . '][' . $objId . '][' . $field->getName() . ']');

            //Fix the gridstate field
            if ($field instanceof GridField) {
                $field->getState(false)->setName($field->getName() . '[GridState]');
            }

            return $field->handleRequest($request, $this->model);
        } else {
            // falling back to fieldByName, e.g. for getting tabs
            $field = $obj->getCMSFields()->fieldByName($realFieldName);
            if ($field) {
                $field->setForm($this->getFormShiv($obj));

                $request = $this->getFakeRequest($request, $obj, $baseURL);

                //Shift the real request by the remaining positions, we assume the field will handle the rest
                $this->request->shift(substr_count($this->request->remaining(), '/') + 1);

                $field->setName('Widget[' . $this->getName() . '][' . $objId . '][' . $field->getName() . ']');

                //Fix the gridstate field
                if ($field instanceof GridField) {
                    $field->getState(false)->setName($field->getName() . '[GridState]');
                }

                return $field->handleRequest($request, $this->model);
            }
        }

        return $this->httpError(404, 'Widget field not found');
    }

    /**
     * Uses the `WidgetEditor.ss` template and {@link Widget->editablesegment()}
     * to render a administrator-view of the widget. It is assumed that this
     * view contains form elements which are submitted and saved through
     * {@link \SilverStripe\Widgets\Forms\WidgetAreaEditor} within the CMS interface.
     *
     * @return string HTML
     */
    public function handleAddWidgetEditor($request)
    {
        $className = str_replace('_', '\\', $request->param('ClassName'));
        if (class_exists($className) && is_subclass_of($className, Widget::class)) {
            $obj = new $className();
            $obj->setWidgetEditor($this);
            return $obj->AdvancedEditableSegment();
        } else {
            user_error("Bad widget class: $className", E_USER_WARNING);
            return "Bad widget class name given";
        }
    }

    /**
     * Generates a fake request for the field
     * @param SS_HTTPRequest $request Source Request to base the fake request off of
     * @param Widget $sourceWidget Source widget
     * @param string $baseLink Base URL to be truncated off of the form
     * @return SS_HTTPRequest Fake HTTP Request used to fool the form field into thinking the request was made to it directly
     */
    protected function getFakeRequest(HTTPRequest $request, Widget $sourceWidget, $baseLink)
    {
        $fieldName = rawurldecode($request->param('FieldName'));
        $objID = preg_replace('/Widget\[(.*?)\]\[(.*?)\]\[(.*?)\]$/', '$2', $fieldName);
        $finalPostVars = [];


        if ($request->isPOST()) {
            $postVars = $request->postVars();

            //Pull the post data for the widget
            if (isset($postVars['Widget'][$this->getName()][$objID])) {
                $finalPostVars = $postVars['Widget'][$this->getName()][$objID];
            } else {
                $finalPostVars = [];
            }

            $finalPostVars = array_merge($finalPostVars, $postVars);
            unset($finalPostVars['Widget']);


            //Workaround for UploadField's and GridFields confusing the request
            $fields = $sourceWidget->getCMSFields();
            $uploadFields = [];
            $gridFields = [];
            foreach ($fields as $field) {
                if ($field instanceof UploadField) {
                    $uploadFields[] = $field->getName();
                } else if ($field instanceof GridField) {
                    $gridFields[] = $field->getName();
                }
            }


            //Re-orgazine the upload field data
            if (count($uploadFields)) {
                foreach ($uploadFields as $field) {
                    $formFieldName = 'Widget[' . $this->getName() . '][' . $objID . '][' . $field . ']';
                    $fieldData = [
                        $formFieldName => [
                            'name' => ['Uploads' => []],
                            'type' => ['Uploads' => []],
                            'tmp_name' => ['Uploads' => []],
                            'error' => ['Uploads' => []],
                            'size' => ['Uploads' => []],
                        ],
                    ];

                    if (isset($postVars['Widget']['name'][$this->getName()][$objID][$field]['Uploads'])) {
                        for ($i = 0; $i < count($postVars['Widget']['name'][$this->getName()][$objID][$field]['Uploads']); $i++) {
                            $fieldData[$formFieldName]['name']['Uploads'][] = $postVars['Widget']['name'][$this->getName()][$objID][$field]['Uploads'][$i];
                            $fieldData[$formFieldName]['type']['Uploads'][] = $postVars['Widget']['type'][$this->getName()][$objID][$field]['Uploads'][$i];
                            $fieldData[$formFieldName]['tmp_name']['Uploads'][] = $postVars['Widget']['tmp_name'][$this->getName()][$objID][$field]['Uploads'][$i];
                            $fieldData[$formFieldName]['error']['Uploads'][] = $postVars['Widget']['error'][$this->getName()][$objID][$field]['Uploads'][$i];
                            $fieldData[$formFieldName]['size']['Uploads'][] = $postVars['Widget']['size'][$this->getName()][$objID][$field]['Uploads'][$i];
                        }
                    }

                    $finalPostVars = array_merge_recursive($finalPostVars, $fieldData);
                }
            }


            //Reorganize the gridfield data
            if (count($gridFields) && isset($postVars['Widget'][$this->getName()][$objID])) {
                foreach ($gridFields as $field) {
                    $formFieldName = 'Widget[' . $this->getName() . '][' . $objID . '][' . $field . ']';
                    $fieldData = [
                        $formFieldName => $postVars['Widget'][$this->getName()][$objID][$field],
                    ];
                }

                $finalPostVars = array_merge_recursive($finalPostVars, $fieldData);
            }
        }


        $headers = $request->getHeaders();
        $url = rtrim($request->getURL(), '/');
        $fakeRequest = new HTTPRequest($request->httpMethod(), str_replace(rtrim($baseLink, '/'), '', $url) . '/', $request->getVars(), $finalPostVars, $request->getBody());
        $fakeRequest->match('$Action/$ID/$OtherID');
        $fakeRequest->shift(substr_count(substr($url, 0, strpos($url, $fieldName) + strlen($fieldName) + 1), '/'));
        $fakeRequest->setSession($request->getSession());

        //Merge in the headers
        foreach ($headers as $header => $value) {
            $fakeRequest->addHeader($header, $value);
        }

        return $fakeRequest;
    }

    /**
     * Gets the shiv form
     * @return AdvancedWidgetFormShiv
     */
    public function getFormShiv(Widget $obj)
    {
        return new AdvancedWidgetFormShiv($this, $obj);
    }

    /**
     * Returns a readonly version of this field
     */
    public function performReadonlyTransformation()
    {
        $copy = clone $this;
        $copy->setReadonly(true);

        return $copy;
    }

    /**
     * Returns a disabled version of this field
     */
    public function performDisabledTransformation()
    {
        $copy = clone $this;
        $copy->setDisabled(true);

        return $copy;
    }

    /**
     * @param \SilverStripe\ORM\DataObject $record
     */
    public function saveInto(DataObjectInterface $record)
    {
        if ($this->isDisabled() || $this->isReadonly()) {
            return;
        }

        $name = $this->name;
        $idName = $name . "ID";

        $widgetarea = $record->getComponent($name);
        $widgetarea->write();

        $record->$idName = $widgetarea->ID;

        $widgets = $widgetarea->Items();

        // store the field IDs and delete the missing fields
        // alternatively, we could delete all the fields and re add them
        $missingWidgets = [];

        if ($widgets) {
            foreach ($widgets as $existingWidget) {
                $missingWidgets[$existingWidget->ID] = $existingWidget;
            }
        }

        $widgetAreasData = $this->getForm()->getController()->getRequest()->requestVar('Widget');
        if (isset($widgetAreasData) && isset($widgetAreasData[$this->getName()])) {
            $widgetForm = new Form($this, 'WidgetForm', new FieldList(), new FieldList());

            foreach (array_keys($widgetAreasData[$this->getName()]) as $newWidgetID) {
                $newWidgetData = $widgetAreasData[$this->getName()][$newWidgetID];

                // Sometimes the id is "new-1" or similar, ensure this doesn't get into the query
                if (!is_numeric($newWidgetID)) {
                    $newWidgetID = 0;
                }

                // \"ParentID\" = '0' is for the new page
                $widget = Widget::get()
                    ->filter('ParentID', [$record->$name()->ID, 0])
                    ->filter('ID', $newWidgetID)
                    ->first();

                // check if we are updating an existing widget
                if ($widget && isset($missingWidgets[$widget->ID])) {
                    unset($missingWidgets[$widget->ID]);
                }

                // create a new object
                $className = str_replace('_', '\\', $newWidgetData['Type']);
                if (!$widget && !empty($className) && class_exists($className) && is_subclass_of($className, Widget::class, true)) {
                    $widget = new $className();
                    $widget->ID = 0;
                    $widget->ParentID = $record->$name()->ID;
                }

                if ($widget) {
                    if ($widget->ParentID == 0) {
                        $widget->ParentID = $record->$name()->ID;
                    }

                    //Set the widget editor
                    $widget->setWidgetEditor($this);

                    //Set the form's fields
                    $widgetForm->setFields($widget->getCMSFields());

                    //Populate the form
                    $widgetForm->loadDataFrom($newWidgetData);

                    //Save the form into the widget and write
                    $widgetForm->saveInto($widget);
                    $widget->Sort = (array_key_exists('Sort', $newWidgetData) ? $newWidgetData['Sort'] : $widget->Sort);
                    $widget->write();
                }
            }
        }

        //Remove the widgets not saved
        if ($missingWidgets) {
            foreach ($missingWidgets as $removedWidget) {
                if (isset($removedWidget) && is_numeric($removedWidget->ID)) {
                    $removedWidget->delete();
                }
            }
        }
    }
}
