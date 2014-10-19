<?php
class AdvancedWidgetAreaEditor extends WidgetAreaEditor {
    private static $allowed_actions=array(
                                        'handleField',
                                        'handleAddWidgetEditor'
                                    );
    
    private static $url_handlers=array(
                                        'field/$Type/field/$FieldName!'=>'handleField',
                                        'add-widget/$ClassName!'=>'handleAddWidgetEditor'
                                    );
    
    /**
     * @param {array} $properties
     * @return {string} HTML
     */
    public function FieldHolder($properties=array()) {
        Requirements::css('widgets/css/WidgetAreaEditor.css');
        
        if($this->isReadonly() || $this->isDisabled()) {
            Requirements::css(AWE_BASE.'/css/AdvancedWidgetAreaEditor_readonly.css');
            
            return $this->renderWith('AdvancedWidgetAreaEditor_readonly');
        }
        
        
        Requirements::javascript('widgets/javascript/WidgetAreaEditor.js');
        Requirements::javascript(AWE_BASE.'/javascript/AdvancedWidgetAreaEditor.js');
        
        return $this->renderWith('AdvancedWidgetAreaEditor');
    }
    
    /**
     *
     * @return ArrayList
     */
    public function AvailableWidgets() {
        $widgets=new ArrayList();

        foreach($this->widgetClasses as $widgetClass) {
            $classes=ClassInfo::subclassesFor($widgetClass);
            
            if(isset($classes['Widget'])) {
                unset($classes['Widget']);
            }else if(isset($classes[0]) && $classes[0]=='Widget') {
                unset($classes[0]);
            }
            
            
            $record=$this->form->getRecord();
            $availableWidgets=null;
            $restrictedWidgets=null;
            if($record) {
                $availableWidgets=$record->config()->available_widgets;
                $restrictedWidgets=$record->config()->restricted_widgets;
            }
            
            
            foreach($classes as $class) {
                if((!empty($availableWidgets) && is_array($availableWidgets) && !in_array($class, $availableWidgets)) || (!empty($restrictedWidgets) && is_array($restrictedWidgets) && in_array($class, $restrictedWidgets))) {
                    continue;
                }
                
                $available=Config::inst()->get($class, 'only_available_in');
                if(!empty($available) && is_array($available)) {
                    if(in_array($this->Name, $available)) {
                        $widgets->push(singleton($class));
                    }
                }else {
                    $widgets->push(singleton($class));
                }
            }
        }
        
        return $widgets;
    }
    
    /**
     * @return HasManyList
     */
    public function UsedWidgets() {
        // Call class_exists() to load Widget.php earlier and avoid a segfault
        class_exists('Widget');
        
        $relationName=$this->name;
        $widgets=$this->form->getRecord()->getComponent($relationName)->Items();
        
        if($widgets instanceof HasManyList) {
            if($this->form->getRecord()->has_one($relationName)) {
                $joinField=$relationName.'ID';
            }else if($this->form->getRecord()->belongs_to($relationName)) {
                $joinField=$this->form->getRecord()->getRemoteJoinField($relationName, 'belongs_to');
            }
            
            $widgets=AdvancedWidgetsHasManyList::create($widgets->dataClass(), $joinField)->setDataQuery($widgets->dataQuery())->setWidgetEditor($this);
        }
        
        
        return $widgets;
    }
    
    /**
     * Handles a field request
     * @param {SS_HTTPRequest} $request
     * @return {FormField}
     */
    public function handleField($request) {
        $className=$request->param('Type');
        $fieldName=rawurldecode($request->param('FieldName'));
        $realFieldName=preg_replace('/^Widget\[(.*?)\]\[(.*?)\]\[(.*?)\]$/', '$3', $fieldName);
        $baseURL=preg_replace('/\?(.*?)$/', '', $this->Link('field')); //Get the base link stripping parameters
        $baseURL=Controller::join_links($baseURL, $className, 'field', $request->param('FieldName'), '/');
        
        
        //Parse field name for the id
        $objId=preg_replace('/Widget\[(.*?)\]\[(.*?)\]\[(.*?)\]/', '$2', $fieldName);
        if(class_exists($className) && is_subclass_of($className, 'Widget')) {
            if(is_numeric($objId)) {
                $obj=$this->UsedWidgets()->byID(intval($objId));
                if(empty($obj) || $obj===false || $obj->ID==0) {
                    return;
                }
            }else {
                $obj=singleton($className);
            }
        }else {
            return $this->httpError(404, 'Widget not found');
        }
        
        
        $field=$obj->getCMSFields()->dataFieldByName($realFieldName);
        if($field) {
            $field->setForm($this->getFormShiv($obj));
            
            //Replace the request, we need the post variables to appear as if the widgets are in the top field
            $request=$this->getFakeRequest($request, $obj, $baseURL);
            
            //Shift the real request by the remaining positions, we assume the field will handle the rest
            $this->request->shift(substr_count($this->request->remaining(), '/')+1);
            
            $field->setName('Widget['.$this->getName().']['.$objId.']['.$field->getName().']');
            
            //Fix the gridstate field
            if($field instanceof GridField) {
                $field->getState(false)->setName($field->getName().'[GridState]');
            }
            
            return $field->handleRequest($request, $this->model);
        }else {
            // falling back to fieldByName, e.g. for getting tabs
            $field=$obj->getCMSFields()->fieldByName($realFieldName);
            if($field) {
                $field->setForm($this->getFormShiv($obj));
                
                $request=$this->getFakeRequest($request, $obj, $baseURL);
                
                //Shift the real request by the remaining positions, we assume the field will handle the rest
                $this->request->shift(substr_count($this->request->remaining(), '/')+1);
                
                $field->setName('Widget['.$this->getName().']['.$objId.']['.$field->getName().']');
                
                //Fix the gridstate field
                if($field instanceof GridField) {
                    $field->getState(false)->setName($field->getName().'[GridState]');
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
     * {@link WidgetAreaEditor} within the CMS interface.
     *
     * @return string HTML
     */
    public function handleAddWidgetEditor($request) {
        $className=$request->param('ClassName');
        if(class_exists('Translatable') && Member::currentUserID()) {
            // set current locale based on logged in user's locale
            $locale = Member::currentUser()->Locale;
            i18n::set_locale($locale);
        }
        
        if(class_exists($className) && is_subclass_of($className, 'Widget')) {
            $obj=new $className();
            $obj->setWidgetEditor($this);
            return $obj->AdvancedEditableSegment();
        }else {
            user_error("Bad widget class: $className", E_USER_WARNING);
            return "Bad widget class name given";
        }
    }
    
    /**
     * Generates a fake request for the field
     * @param {SS_HTTPRequest} $request Source Request to base the fake request off of
     * @param {Widget} $sourceWidget Source widget
     * @param {string} $baseLink Base URL to be truncated off of the form
     * @return {SS_HTTPRequest} Fake HTTP Request used to fool the form field into thinking the request was made to it directly
     */
    protected function getFakeRequest(SS_HTTPRequest $request, Widget $sourceWidget, $baseLink) {
        $fieldName=rawurldecode($request->param('FieldName'));
        $objID=preg_replace('/Widget\[(.*?)\]\[(.*?)\]\[(.*?)\]$/', '$2', $fieldName);
        $finalPostVars=array();
        
        
        if($request->isPOST()) {
            $postVars=$request->postVars();
            
            //Pull the post data for the widget
            if(isset($postVars['Widget'][$this->getName()][$objID])) {
                $finalPostVars=$postVars['Widget'][$this->getName()][$objID];
            }else {
                $finalPostVars=array();
            }
            
            $finalPostVars=array_merge($finalPostVars, $postVars);
            unset($finalPostVars['Widget']);
            
            
            //Workaround for UploadField's and GridFields confusing the request
            $fields=$sourceWidget->getCMSFields();
            $uploadFields=array();
            $gridFields=array();
            foreach($fields as $field) {
                if($field instanceof UploadField) {
                    $uploadFields[]=$field->getName();
                }else if($field instanceof GridField) {
                    $gridFields[]=$field->getName();
                }
            }
            
            
            //Re-orgazine the upload field data
            if(count($uploadFields)) {
                foreach($uploadFields as $field) {
                    $formFieldName='Widget['.$this->getName().']['.$objID.']['.$field.']';
                    $fieldData=array(
                                    $formFieldName=>array(
                                                'name'=>array('Uploads'=>array()),
                                                'type'=>array('Uploads'=>array()),
                                                'tmp_name'=>array('Uploads'=>array()),
                                                'error'=>array('Uploads'=>array()),
                                                'size'=>array('Uploads'=>array())
                                            )
                                );
                    
                    if(isset($postVars['Widget']['name'][$this->getName()][$objID][$field]['Uploads'])) {
                        for($i=0;$i<count($postVars['Widget']['name'][$this->getName()][$objID][$field]['Uploads']);$i++) {
                            $fieldData[$formFieldName]['name']['Uploads'][]=$postVars['Widget']['name'][$this->getName()][$objID][$field]['Uploads'][$i];
                            $fieldData[$formFieldName]['type']['Uploads'][]=$postVars['Widget']['type'][$this->getName()][$objID][$field]['Uploads'][$i];
                            $fieldData[$formFieldName]['tmp_name']['Uploads'][]=$postVars['Widget']['tmp_name'][$this->getName()][$objID][$field]['Uploads'][$i];
                            $fieldData[$formFieldName]['error']['Uploads'][]=$postVars['Widget']['error'][$this->getName()][$objID][$field]['Uploads'][$i];
                            $fieldData[$formFieldName]['size']['Uploads'][]=$postVars['Widget']['size'][$this->getName()][$objID][$field]['Uploads'][$i];
                        }
                    }
                    
                    $finalPostVars=array_merge_recursive($finalPostVars, $fieldData);
                }
            }
            
            
            //Reorganize the gridfield data
            if(count($gridFields) && isset($postVars['Widget'][$this->getName()][$objID])) {
                foreach($gridFields as $field) {
                    $formFieldName='Widget['.$this->getName().']['.$objID.']['.$field.']';
                    $fieldData=array(
                                    $formFieldName=>$postVars['Widget'][$this->getName()][$objID][$field]
                                );
                }
                
                $finalPostVars=array_merge_recursive($finalPostVars, $fieldData);
            }
        }
        
        
        $headers=$request->getHeaders();
        $request=new SS_HTTPRequest($_SERVER['REQUEST_METHOD'], str_replace(rtrim($baseLink, '/'), '', rtrim($request->getURL(), '/')).'/', $request->getVars(), $finalPostVars, $request->getBody());
        $request->match('$Action/$ID/$OtherID');
        
        //Merge in the headers
        foreach($headers as $header=>$value) {
            $request->addHeader($header, $value);
        }
        
        return $request;
    }
    
    /**
     * Gets the shiv form
     * @return {AdvancedWidgetFormShiv}
     */
    public function getFormShiv(Widget $obj) {
        return new AdvancedWidgetFormShiv($this, $obj);
    }
    
    /**
     * Returns a readonly version of this field
     */
    public function performReadonlyTransformation() {
        $copy=clone $this;
        $copy->setReadonly(true);
        
        return $copy;
    }
    
    /**
     * Returns a disabled version of this field
     */
    public function performDisabledTransformation() {
        $copy=clone $this;
        $copy->setDisabled(true);
        
        return $copy;
    }
    
    /**
     * @param DataObjectInterface $record
     */
    public function saveInto(DataObjectInterface $record) {
        if($this->isDisabled() || $this->isReadonly()) {
            return;
        }
        
        $name=$this->name;
        $idName=$name."ID";
        
        $widgetarea=$record->getComponent($name);
        $widgetarea->write();
        
        $record->$idName=$widgetarea->ID;
        
        $widgets=$widgetarea->Items();
        
        // store the field IDs and delete the missing fields
        // alternatively, we could delete all the fields and re add them
        $missingWidgets=array();
        
        if($widgets) {
            foreach($widgets as $existingWidget) {
                $missingWidgets[$existingWidget->ID]=$existingWidget;
            }
        }
        
        if(isset($_REQUEST['Widget'])) {
            foreach(array_keys($_REQUEST['Widget']) as $widgetAreaName) {
                if($widgetAreaName!==$this->name) {
                    continue;
                }
                
                $widgetForm=new Form($this, 'WidgetForm', new FieldList(), new FieldList());
                
                foreach(array_keys($_REQUEST['Widget'][$widgetAreaName]) as $newWidgetID) {
                    $newWidgetData=$_REQUEST['Widget'][$widgetAreaName][$newWidgetID];
                    
                    // Sometimes the id is "new-1" or similar, ensure this doesn't get into the query
                    if(!is_numeric($newWidgetID)) {
                        $newWidgetID=0;
                    }
                    
                    // \"ParentID\" = '0' is for the new page
                    $widget=DataObject::get_one(
                                                'Widget',
                                                "(\"ParentID\"='{$record->$name()->ID}' OR ".
                                                "\"ParentID\"='0') AND \"Widget\".\"ID\"='$newWidgetID'"
                                            );
                    
                    // check if we are updating an existing widget
                    if($widget && isset($missingWidgets[$widget->ID])) {
                        unset($missingWidgets[$widget->ID]);
                    }
                    
                    // create a new object
                    if(!$widget && !empty($newWidgetData['Type']) && class_exists($newWidgetData['Type'])) {
                        $widget=new $newWidgetData['Type']();
                        $widget->ID=0;
                        $widget->ParentID=$record->$name()->ID;

                        if(!is_subclass_of($widget, 'Widget')) {
                            $widget=null;
                        }
                    }
                    
                    if($widget) {
                        if($widget->ParentID==0) {
                            $widget->ParentID=$record->$name()->ID;
                        }
                        
                        //Set the widget editor
                        $widget->setWidgetEditor($this);
                        
                        //Set the form's fields
                        $widgetForm->setFields($widget->getCMSFields());
                        
                        //Populate the form
                        $widgetForm->loadDataFrom($newWidgetData);
                        
                        
                        //Save the form into the widget and write
                        $widgetForm->saveInto($widget);
                        $widget->Sort=(array_key_exists('Sort', $newWidgetData) ? $newWidgetData['Sort']:$widget->Sort);
                        $widget->write();
                    }
                }
            }
        }
        
        // remove the fields not saved
        if($missingWidgets) {
            foreach($missingWidgets as $removedWidget) {
                if(isset($removedWidget) && is_numeric($removedWidget->ID)) {
                    $removedWidget->delete();
                }
            }
        }
    }
}
?>