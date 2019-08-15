var ya_qt,                //Таймер
    ya_queryItemsCnt = 5, //Количество элементов для подгрузки
    ya_queryUrl      = 'https://geocode-maps.yandex.ru/1.x/', //Url куда стучатся за результами поиска
    ya_mapQuery,     //Запрос карты
    ya_time = 700;   //Время ожидания ввода
    

$.selectPoint = function() {
    var myMap,        //Карта
        myPlacemark,  //Метка
        lattitudeInput = $("input[name='coord_lat']"),
        longitudeInput = $("input[name='coord_lng']"),
        addressInput = $(".yaAdressField .autocomplete"),
        point = [$('.selectPoint').data('coordLat'), $('.selectPoint').data('coordLng')];
        
    $('#useGeo').change(function() {
        if (!$(this).is(':checked')) {
            lattitudeInput.add(longitudeInput).add(addressInput).val('');
        }
        $('.selectPoint').toggle($(this).is(':checked'));        
    }).change();

    ymaps.ready(init); //Инициализация Yandex карты        
    
    /**
    * Устанавливает координаты в скрытое поле в нужном формате 
    * В качестве аргумента передаётся массив со значениями x и y
    * 
    * @param Array coords - массив с координатами точки
    */   
    function setCoordsToInput(coords){
        
        lattitudeInput.val(coords[0]);
        longitudeInput.val(coords[1]);
    }

    
    function init(){ 
        //Ставим параметры карты
        myMap = new ymaps.Map("map", {
            center: point,
            zoom: 12
        }); 
        
        //Ставим параметры метке
        myPlacemark = new ymaps.Placemark(point, {
            hintContent: lang.t('Ваш филиал'),
            balloonContent: lang.t('Ваш филиал')
        },{
            draggable: true 
        });
        
        myMap.geoObjects.add(myPlacemark); //Добавляем метку
        setCoordsToInput(point);//Выставляем координаты по умолчанию
        
        //Навесим события
        /**
        * Клик по карте перенос метки.
        */
        myMap.events.add('click',function(e){
           //Текщие координаты щелчка
           var coords = e.get('coords'); 
           myPlacemark.geometry.setCoordinates(coords);
           setCoordsToInput(coords);
        });
        
        /**
        * Перетаскивание метки
        */
        myPlacemark.events.add('dragend', function(e) {
           // Получение ссылки на объект, который был передвинут.
           var thisPlacemark = e.get('target');
           // Определение координат метки
           var coords = thisPlacemark.geometry.getCoordinates(); 
           setCoordsToInput(coords);
        });
        
    
        lattitudeInput.add(longitudeInput).change(function() {
            var coords = [lattitudeInput.val(), longitudeInput.val()];
            myMap.setCenter(coords);                     //Ставим в центр карты
            myPlacemark.geometry.setCoordinates(coords); //Ставим метку
        });            
        

        /**
        * Автозаполнение для поля адреса
        */
        addressInput.each(function(i){
           _this = $(this); 
           $(this).autocomplete({
              appendTo: "#ardessResults", //Куда будут подгружаться элементы 
              minLength: 3, // Минимальная длина текста адреса для запроса 
              delay: 500,
              /**
              * Фокус на пункте выпадающего меню
              */
              focus: function( event, ui ) {
                 _this.val( ui.item.value );
                 return false;
              },
              /**
              * Нажатие на выбраном пункте выпадающего меню, 
              * установка координат и показ на карте
              * 
              * @param event event - событие
              * @param object ui - объект выбраного пункта меню
              */
              select: function( event, ui ) {
                    var coords = ui.item.coords;
                    myMap.setCenter(coords);                     //Ставим в центр карты
                    myPlacemark.geometry.setCoordinates(coords); //Ставим метку
                    setCoordsToInput(coords);                    //Выставляем в скрытые поля координаты   
                    _this.val( ui.item.value );
                    return false;
              },
              /**
              * Подгрузка значение от yandex
              * 
              * @param string request - url для запроса
              * @param function response - функция в которую передаютмя значения
              */
              source: function(request, response){
                  $.ajax({
                      type:'POST',
                      url:ya_queryUrl,
                      data: {
                          format:'json',
                          results: ya_queryItemsCnt,
                          geocode: request.term
                      },
                      success: function(data) {
                          var ya_array = []; //Массив для autocomplete
                          
                          var collection = data.response.GeoObjectCollection;
                          var streetObjs = collection.featureMember; //Найденные элементы
                         
                          
                          if (streetObjs.length>0){
                            $(streetObjs).each(function(i,item){
                                var coords = item.GeoObject.Point.pos.split(' ');
                                ya_array.push({
                                        label: item.GeoObject.name,
                                        value: item.GeoObject.name,
                                        desc: item.GeoObject.description,
                                        coords: [coords[1],coords[0]]
                                });  
                            });  
                          }else{
                            ya_array.push({
                                label: lang.t('Ничего не найдено'),
                                value: '',
                            });
                          }
                          response(ya_array);
                      }
                  });
           
              }  
           }).on('keydown', function(e) {
               if (e.keyCode == 13) return false;
           });
           
           /**
           * Обработаем ответа сервера, прорисовка элементов
           */
           $(this).data('ui-autocomplete')._renderItem = function(ul, item){

                var oneItem = $('<li data-coords=""><a href="#" onclick="return false;"><span id="geoTitle" class="title"></span><i id="geoDescription" class="position"></i></a></li>');
                oneItem.data('coords', item.coords[1]+";"+item.coords[0]);
                $('#geoTitle',oneItem).html(item.label);
                $('#geoDescription',oneItem).html(item.desc);

                ul.addClass('ardessResults');

                return oneItem.appendTo(ul);
           }
        });            
    }
};  