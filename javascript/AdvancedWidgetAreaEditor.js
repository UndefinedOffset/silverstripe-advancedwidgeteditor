(function($) {
    $.entwine('ss', function($) {
        $('.WidgetAreaEditor.AdvancedWidgetAreaEditor').entwine({
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
                params=params.replace(/^(.*)\?/, '?');
                
                $.ajax({
                    'url':url+'/'+className+params,
                    'success':function(response) {parentRef.insertWidgetEditor(response);},
                    'data':{
                        'locale':locale
                    },
                });
            },
            insertWidgetEditor: function(response) {
                this._super(response);
                
                //If Upload Fields are present ensure the enctype is right
                if($(this).find('.ss-uploadfield').length>0) {
                    $(this).closest('form').attr('enctype', 'multipart/form-data');
                }
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