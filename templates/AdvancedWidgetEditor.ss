<div class="$ClassName Widget" id="$AdvancedName">
    <h3 class="handle">$CMSTitle</h3>
    
    <div class="widgetDescription">
        <p>$Description</p>
    </div>
    
    <% if $AdvancedCMSEditor %>
        <div class="widgetFields">
            $AdvancedCMSEditor
        </div>
    <% end_if %>
    
    <input type="hidden" name="$AdvancedName[Type]" value="$ClassName" />
    <input type="hidden" name="$AdvancedName[Sort]" value="$Sort" />
    
    <p class="deleteWidget"><span class="widgetDelete ss-ui-button"><% _t('WidgetEditor_ss.DELETE', 'Delete') %></span></p>
</div>