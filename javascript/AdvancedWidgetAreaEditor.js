(function($) {
    $.entwine('ss', function($) {
        $('.WidgetAreaEditor.AdvancedWidgetAreaEditor').entwine({
            onadd: function() {
                this._super();
                
                //If Upload Fields are present ensure the enctype is right
                if($(this).find('.ss-uploadfield').length>0) {
                    $(this).closest('form').attr('enctype', 'multipart/form-data');
                }
            },
            addAdvancedWidget: function(className, holder) {
                if($('#WidgetAreaEditor-'+holder).attr('maxwidgets')) {
                    var maxCount = $('#WidgetAreaEditor-'+holder).attr('maxwidgets');
                    var count = $('#usedWidgets-'+holder+' .Widget').length;
                    if (count+1 > maxCount) {
                        alert(ss.i18n._t('WidgetAreaEditor.TOOMANY'));
                        return;
                    }
                }
                
                //If Upload Fields are present ensure the enctype is right
                if($(this).find('.ss-uploadfield').length>0) {
                    $(this).closest('form').attr('enctype', 'multipart/form-data');
                }
                
                var parentRef=$(this);
                var locale=$(this).closest('form').find('input[name=Locale]').val();
                var url=$(this).attr('data-addlink');
                url=url.replace(/\?(.*)$/, '');
                var params=$(this).attr('data-addlink');
                if(params.indexOf('?')!=-1) {
                    params=params.replace(/^(.*)\?/, '?');
                }else {
                    params='';
                }
                
                $.ajax({
                    'url':url+'/'+className+params,
                    'success':function(response) {parentRef.insertWidgetEditor(response);},
                    'data':{
                        'locale':locale
                    },
                });
            },
            rewriteWidgetAreaAttributes: function() {
                //Do nothing the widgets should be written correctly coming from the cms
            },
            insertWidgetEditor: function(response) {
                var usedWidgets = $('#usedWidgets-'+$(this).attr('name')).children();
                
                // Give the widget a unique id
                var newID=parseInt($(this).data('maxid'))+1;
                $(this).data('maxid', newID);
                
                var widgetContent=response.replace(/Widget\[(.*?)\]\[0\]/gi, "Widget[$1][new-" + (newID) + "]");
                widgetContent=widgetContent.replace(new RegExp('Widget-' + ($(this).attr('name')) + '-0-','gi'), "Widget-" + ($(this).attr('name')) + "-new-" + (newID) + "-");
                $('#usedWidgets-'+$(this).attr('name')).append(widgetContent);
                
                //If Upload Fields are present ensure the enctype is right
                if($(this).find('.ss-uploadfield').length>0) {
                    $(this).closest('form').attr('enctype', 'multipart/form-data');
                }
                
                this.rewriteWidgetAreaAttributes();
            }
        });
        
        $('.AdvancedWidgetAreaEditor div.availableWidgets .Widget h3').entwine({
            onclick: function(event) {
                parts = $(this).parent().attr('id').split('-');
                var widgetArea = parts.pop();
                var className = parts.pop();
                $('#WidgetAreaEditor-'+widgetArea).addAdvancedWidget(className, widgetArea);
                
                event.stopPropagation();
                return false;
            }
        });
    });
})(jQuery);