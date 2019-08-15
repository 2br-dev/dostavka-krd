{addcss file="bread_crumps.css"}
{addcss file="delivery.css"}
{addjs file="delivery.js"}
{extends file="%THEME%/wrapper.tpl"}
{block name="content"}
    <div class="dev_cont">
        {moduleinsert name="\Main\Controller\Block\Breadcrumbs"}
        <main class="dev">
            <p class="dev__title">Доставка и оплата</p>
            
            <section class="dev__sec">
                <div class="sec__title">
                    <img src="/templates/dostavka/resource/img/dostavka/icons/icon_cart_1.svg"  class="sec__title_icon">
                    <p class="sec__title_text">Доставка</p>
                </div>
                <p class="sec__text_1">Доставка на этаж производится без взимания дополнительной платы. При отказе от покупки доставка не оплачивается. Передача заказанного товара происходит в присутствии заказчика, либо его представителя по месту доставки. Оплачивая товар на месте, Вы соглашаетесь, что товар проверен Вами на количество и соответствие заказанному.</p>
                <div class="sec__flex">
                    <div class="sec__col">
                        <p class="sec__col_title">Краснодар</p>
                        <p class="sec__col_title-b">Бесплатно</p>
                        <p class="sec__col_text">При заказе от 1000 рублей</p>
                        <p class="sec__col_title-b">200 рублей</p>
                        <p class="sec__col_text">При заказе до 1000 рублей</p>
                    </div>
                    <div class="sec__col">
                        <p class="sec__col_title sec__col_title-btn"  id="call_spoiler_1">Пригород</p>
                        <p class="sec__col_title-b">Бесплатно</p>
                        <p class="sec__col_text">При заказе от 1500 рублей</p>
                        <p class="sec__col_title-b">250 рублей</p>
                        <p class="sec__col_text">При заказе до 1500 рублей</p>
                    </div>
                </div>
            </section>
            <section class="spoiler  {*spoiler-active*}" id="spoiler_1">
                <p class="spoiler__text">
                    Доставка производится в следующие близлежащие районы Краснодара: пос. Северный, пос. Южный, пос. Плодородный, р-он Витаминкомбината, Ейского шоссе, Гор.хутор, р-он Рубероидного завода, ст. Елизаветинская, ст. Марьянская, ст. Новотитаровская, пос. Знаменский, Старознаменский, Индустриальный, Дивный, х. Копанской, р-н Аэропорта, Восточный обход, от пересечения Ростовского шоссе с ул. Российской до 18 км от Краснодара, ближайшие поселения и с/т, находящиеся недалеко от трассы Немецкая деревня, Западный обход, 2-ое отд с/х Солнечный, Колосистый, пос. Яблоновский, Тургеневское шоссе, Мега Адыгея, Радиорынок, Новознаменский, хутор Ленина.
                </p>
            </section>

            <section class="dev__sec">
                <div class="sec__title">
                    <img src="/templates/dostavka/resource/img/dostavka/icons/icon_cart_2.svg"  class="sec__title_icon">
                    <p class="sec__title_text">Время доставки</p>
                </div>
                <p class="sec__text_1">Доставка на этаж производится без взимания дополнительной платы. При отказе от покупки доставка не оплачивается. Передача заказанного товара происходит в присутствии заказчика, либо его представителя по месту доставки. Оплачивая товар на месте, Вы соглашаетесь, что товар проверен Вами на количество и соответствие заказанному.</p>
                <div class="sec__flex">
                    <div class="sec__col">
                        <p class="sec__col_title-b">Заказ до 15:00</p>
                        <p class="sec__col_text_2">Доставляется на следующий день в интервале с 15:00 до 22:00</p>
                        <p class="sec__col_text_3">Желательное время доставки можно указать в комментарии при оформлении заказа.</p>
                    </div>
                    <div class="sec__col">
                        <p class="sec__col_title-b">Заказ после 15:00</p>
                        <p class="sec__col_text_2">Доставляется через день в интервале с 9:00 до 15:00</p>
                        <p class="sec__col_text_3">Желательное время доставки можно указать в комментарии при оформлении заказа.</p>
                    </div>
                </div>
            </section>

            <section class="dev__sec">
                <div class="sec__title">
                    <img src="/templates/dostavka/resource/img/dostavka/icons/icon_cart_3.svg"  class="sec__title_icon">
                    <p class="sec__title_text">Оплата</p>
                </div>
                <p class="sec__title_text_2">Выберите один из четырех способов оплаты:</p>
                <div class="sec__flex">
                    <div class="sec__col">
                        <p class="sec__col_title-b">Наличными курьеру</p>
                        <p class="sec__col_text_2">Заказ оплачивается при получении. </p>
                    </div>
                    <div class="sec__col">
                        <p class="sec__col_title-b">Безналичный расчет по выставленному счету</p>
                    </div>
                </div>
                <div class="sec__spacer"></div>
                <div class="sec__flex">
                    <div class="sec__col">
                        <p class="sec__col_title-b">Картой курьеру</p>
                        <p class="sec__col_text_2">Заказ оплачивается банковской картой при получении.</p>
                        <div class="sec__col_icons">
                            <img src="/templates/dostavka/resource/img/dostavka/icons/pay_1.svg" alt="" class="sec__col_icon">
                            <img src="/templates/dostavka/resource/img/dostavka/icons/pay_2.svg" alt="" class="sec__col_icon">
                            <img src="/templates/dostavka/resource/img/dostavka/icons/pay_3.svg" alt="" class="sec__col_icon">
                            <img src="/templates/dostavka/resource/img/dostavka/icons/pay_4.svg" alt="" class="sec__col_icon">
                            <img src="/templates/dostavka/resource/img/dostavka/icons/pay_5.svg" alt="" class="sec__col_icon">
                        </div>
                    </div>
                    <div class="sec__col">
                        <p class="sec__col_title-b  sec__col_title-b-btn" id="call_spoiler_2">Оплата на сайте</p>
                        <p class="sec__col_text_2">С помощью банковской карты через «Сбербанк». Оплата предоставляется без комиссии.</p>
                    </div>
                </div>
            </section>

            <section class="spoiler {*spoiler-active*}"  id="spoiler_2">
                <p class="spoiler__text">
                    Платежи осуществляются перечислением денежных средств с банковских карт VISA и MASTER CARD при наличии возможности совершения интернет-платежей, предоставленных Вашим банком, выпустившим банковскую карту. О наличии возможности совершения интернет-платежей с использованием банковской карты, Вы можете узнать, обратившись в банк. Услуга оплаты осуществляется в соответствии с Правилами международных платежных систем на принципах соблюдения конфиденциальности и безопасности совершения платежа, для чего используются самые современные методы проверки, шифрования и передачи данных по закрытым каналам связи. Ввод данных банковской карты осуществляется на защищенной платежной странице банка - партнера, предоставляющего услугу. Банком-партнером ООО «Акватория» является АО «Сбербанк». 
                </p>
                <p class="spoiler__title">Случаи отказа в совершении платежа: </p>
                <p class="spoiler__text_2">- Банковская карта не предназначена для совершения платежей через интернет, о чем можно узнать, осведомившись в Вашем Банке; </p>
                <p class="spoiler__text_2">- Данные банковской карты введены неверно;</p>
                <p class="spoiler__text_2">- Истек срок действия банковской карты. Срок действия карты, как правило, указан на лицевой стороне карты (это месяц и год, до которого действительна карта). Подробнее о сроке действия карты Вы можете узнать, обратившись в банк, выпустивший банковскую карту;</p>
                <p class="spoiler__text_2">- Недостаточно средств для оплаты на банковской карте. Подробнее о наличии средств на банковской карте Вы можете узнать, обратившись в банк, выпустивший банковскую карту;</p>
                <p class="spoiler__text_2">- Превышен установленный лимит операций за день. Сумма ежедневного лимита для всех операций определяется банком-партнером.</p>
               
                <p class="spoiler__title_2"><span>Для корректного ввода</span> необходимо внимательно и точно, соблюдая последовательность цифр и букв, ввести данные так, как они указаны на Вашей карте</p>
                <p class="spoiler__text_2">- Владелец карты (как правило, указан на лицевой стороне банковской карты на английском языке заглавными буквами. Например, IVANOV IVAN);</p>
                <p class="spoiler__text_2">- Номер карты (как правило, указан на лицевой стороне банковской карты и состоит из 16-и цифр. Например: 0123 4567 8901 2345);</p>
                <p class="spoiler__text_2">- Срок действия карты (как правило, указан на лицевой стороне банковской карты - месяц и год, до которого действительна карта. Срок действия карты вводится цифрами. Например, 12 (вводится в поле месяца) и 20 (вводится в поле года), что означает, что карта действительна до декабря 2020 года);</p>
                <p class="spoiler__text_2">- CVV2 или CVC2 код карты (как правило, указан на обратной стороне банковской карты и состоит из 3-х цифр. Например, 123).</p>
            </section>
        </main>
    </div>
{/block}
{block name="fixedCart"}{/block} {* Исключаем плавающую корзину *}