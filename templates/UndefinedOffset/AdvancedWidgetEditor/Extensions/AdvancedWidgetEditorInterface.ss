<div class="$ClassName Widget" id="$AdvancedName">
    <h3<% if not $IsEditorReadonly %> class="handle"<% end_if %>>$CMSTitle</h3>
    
    <div class="widgetDescription">
        <p>$Description</p>
    </div>
    
    <% if $AdvancedCMSEditor %>
        <div class="widgetFields">
            $AdvancedCMSEditor($IsEditorReadonly)
        </div>
    <% end_if %>
    
    <input type="hidden" name="$AdvancedName[Type]" value="$ClassName" />
    <input type="hidden" name="$AdvancedName[Sort]" value="$Sort" />
    
    <% if not $IsEditorReadonly %>
        <p class="deleteWidget"><span class="widgetDelete ss-ui-button"><% _t('WidgetEditor_ss.DELETE', 'Delete') %></span></p>
    <% end_if %>
</div>