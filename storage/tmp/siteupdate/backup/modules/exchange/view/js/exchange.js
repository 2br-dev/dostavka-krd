$(function(){
    
    // Эмулятор запросов из 1С
    var requestEmulator = {
        urls: $('#import-params').data('urls'),
        urlIndex: 0,
        reset: function(){
            this.urlIndex = 0;
        },
        start: function(){
            $.rs.loading.show();
            $.ajax({
                dataType: 'html',  
                url: this.urls[this.urlIndex]+"&"+Math.random(),
                success: function(data){
                   $.rs.loading.hide();
                   if(data.match(/^progress/)){
                       // Продолжаем дальше (отправляем еще один запрос по этому же URL)
                       requestEmulator.start()
                       return;
                   }
                   if(data.match(/^success/)){
                       // Переходим к следующему URL
                       requestEmulator.next()
                       return;
                   }
                   if(data.match(/^failure/)){
                       // Переходим к следующему URL
                       requestEmulator.next()
                       return;
                   }
                   if(this.url.match(/mode=init/)){
                       // Переходим к следующему URL
                       requestEmulator.next()
                       return;
                   }
                   // Что-то пошло не так
                   $.messenger(data, {theme: 'error', expire: 0});
                }
            });
        },
        next: function(){
            this.urlIndex ++;
            // Если больше URL-ов не осталось
            if(this.urlIndex == this.urls.length){
                $.messenger("hideAll");
                $.messenger(lang.t('Импорт завершен'), {expire: 0});
                return;
            }
            this.start();
        }
    }
    
    // Смена источника импорта (FTP-папка или файлы на компьютере)
    $("input[name=import_source]").change(function(){
        var form_class = $(this).val();
        $(".source-block").addClass('hidden');
        $(".source-block."+form_class).removeClass('hidden');
    });
    
    // Кнопка "Загрузить"
    $(".start_import").click(function(){
        $.messenger("hideAll");
        var source = $("input[name=import_source]:checked").val();
        switch(source){
            case 'xml-files':
                importFromXmlFile();
                break;
            case 'ftp': 
                importFromFTP();
                break;
        }
    })
    
    // Загрузка из XML-файлов
    var importFromXmlFile = function(){
        // Upload файлов
        $(".xml-files .crud-form").ajaxSubmit({
            dataType:  'json',
            beforeSubmit: function(){
                $.rs.loading.show();
            },
            success: function(data){
                $.rs.loading.hide();
                if(!data.success){
                    $.messenger(data.formdata.errors["@system"].errors.join("\n"), {theme: 'error'});
                    return;
                }
                
                requestEmulator.reset();
                requestEmulator.start();
            }
        });
    }
    
    // Загрузка из папки FTP
    var importFromFTP = function(){
        requestEmulator.reset();
        requestEmulator.start();
    }
    
    
});