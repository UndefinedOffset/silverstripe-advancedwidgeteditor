<div class="WidgetAreaEditor AdvancedWidgetAreaEditor field readonly" id="WidgetAreaEditor-$Name" name="$Name"<% if MaxWidgets %> maxwidgets="$MaxWidgets"<% end_if %> data-addlink="$Link('add-widget')">
    <input type="hidden" id="$Name" name="$IdxField" value="$Value" />
    <div class="usedWidgetsHolder">
        <h2><% _t('WidgetAreaEditor_ss.INUSE', 'Widgets currently used') %></h2>
        
        <div class="usedWidgets" id="usedWidgets-$Name">
            <% if $UsedWidgets %>
                <% loop $UsedWidgets %>
                    $AdvancedEditableSegment(true)
                <% end_loop %>
            <% else %>
                <div class="NoWidgets" id="NoWidgets-$Name"></div>
            <% end_if %>
        </div>
    </div>
</div>