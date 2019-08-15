<div class="onePageAdressBlock">
    <p class="checkoutMobileCaption">Адрес и контакты</p>
    {if !$is_auth}
    <div class="userType">
        <div class="centerWrapper">
            <ul class="centerBlock">
                <li class="user first"><input type="radio" id="type-user" name="user_type" value="person" {if $order.user_type=='person'}checked{/if}><label for="type-user">Частное лицо</label></li>
                <li class="company"><input type="radio" id="type-company" name="user_type" value="company" {if $order.user_type=='company'}checked{/if}><label for="type-company">Компания</label></li>
                <li class="noregister"><input type="radio" id="type-noregister" name="user_type" value="noregister" {if $order.user_type=='noregister'}checked{/if}><label for="type-noregister">Без регистрации</label></li>
                <li class="account"><input type="radio" id="type-account" name="user_type" value="user" {if $order.user_type=='user'}checked{/if}><label for="type-account">Я регистрировался ранее</label></li>
            </ul>
        </div>
    </div>
    {/if}    
    <div class="newAccount">
        <div class="workArea">
            <div class="half fleft">
                <h2>Контактные данные</h2>
                {if $is_auth}
                    {if $user.is_company}
                    <div class="formLine">
                        <label class="fielName">Наименование компании</label><br>
                        <span class="textValue">{$user.company}</span>
                    </div>
                    <div class="formLine">
                        <label class="fielName">ИНН</label><br>
                        <span class="textValue">{$user.company_inn}</span>
                    </div>
                    {/if}
                    <div class="formLine">
                        <label class="fielName">Имя</label><br>
                        <span class="textValue">{$user.name}</span>
                    </div>
                    <div class="formLine">
                        <label class="fielName">Фамилия</label><br>
                        <span class="textValue">{$user.surname}</span>
                    </div>
                    <div class="formLine">
                        <label class="fielName">Отчество</label><br>
                        <span class="textValue">{$user.midname}</span>
                    </div>
                    <div class="formLine">
                        <label class="fielName">Телефон</label><br>
                        <span class="textValue">{$user.phone}</span>
                    </div>
                    <div class="formLine">
                        <label class="fielName">e-mail</label><br>
                        <span class="textValue">{$user.e_mail}</span>
                    </div>
                    <div class="formLine changeUser">
                        <a href="{urlmake logout=true}" class="link">Сменить пользователя</a>
                    </div>
                {else}
                    <div class="userRegister">
                        <div class="organization">
                            <div class="formLine">
                                <label class="fielName">Наименование компании</label><br>
                                {$order->getPropertyView('reg_company')}
                                <div class="help">Например: ООО Аудиторская фирма "Аудитор"</div>
                            </div>
                            <div class="formLine">
                                <label class="fielName">ИНН</label><br>
                                {$order->getPropertyView('reg_company_inn')}
                                <div class="help">10 или 12 цифр</div>
                            </div>
                        </div>
                        <div class="formLine">
                            <label class="fielName">Имя</label><br>
                            {$order->getPropertyView('reg_name')}
                            <div class="help">Имя покупателя, владельца аккаунта</div>
                        </div>
                        <div class="formLine">
                            <label class="fielName">Фамилия</label><br>
                            {$order->getPropertyView('reg_surname')}
                            <div class="help">Фамилия покупателя, владельца аккаунта</div>
                        </div>
                        <div class="formLine">
                            <label class="fielName">Отчество</label><br>
                            {$order->getPropertyView('reg_midname')}
                        </div>                
                        <div class="formLine">
                            <label class="fielName">Телефон</label><br>
                            {$order->getPropertyView('reg_phone')}
                            <div class="help">В формате: +7(123)9876543</div>
                        </div>
                        <div class="formLine">
                            <label class="fielName">e-mail</label><br>
                            {$order->getPropertyView('reg_e_mail')}
                        </div>
                        
                        <div class="formLine">
                            <label class="fielName">Пароль</label><br>
                            <input type="checkbox" name="reg_autologin" {if $order.reg_autologin}checked{/if} value="1" id="reg-autologin">
                            <label for="reg-autologin">Получить автоматически на e-mail</label>
                            <div class="help">Нужен для проверки статуса заказа, обращения в поддержку, входа в кабинет</div>                    
                            <div id="manual-login" {if $order.reg_autologin}style="display:none"{/if}>
                                <div class="inline">
                                    {$order.__reg_openpass->formView(['form'])}
                                    <div class="help">Пароль</div>
                                </div>
                                <div class="inline">
                                    {$order.__reg_pass2->formView()}
                                    <div class="help">Повтор пароля</div>
                                </div>
                                
                                <div class="formFieldError">{$order->getErrorsByForm('reg_openpass', ', ')}</div>
                            </div>                
                        </div>
                        
                        {foreach $reg_userfields->getStructure() as $fld}
                            <div class="formLine">
                            <label class="fielName">{$fld.title}</label><br>
                                {$reg_userfields->getForm($fld.alias)}
                                {$errname=$reg_userfields->getErrorForm($fld.alias)}
                                {$error=$order->getErrorsByForm($errname, ', ')}
                                {if !empty($error)}
                                    <span class="formFieldError">{$error}</span>
                                {/if}
                            </div>
                        {/foreach}
                    </div>
                    <div class="userWithoutRegister">
                        <div class="formLine">
                            <label class="fielName">Ф.И.О.</label><br>
                            {$order->getPropertyView('user_fio')}
                            <div class="help">Фамилия, Имя и Отчество покупателя, владельца аккаунта</div>
                        </div>
                        <div class="formLine">
                            <label class="fielName">E-mail</label><br>
                            {$order->getPropertyView('user_email')}
                            <div class="help">E-mail покупателя, владельца аккаунта</div>
                        </div>                
                        <div class="formLine">
                            <label class="fielName">Телефон</label><br>
                            {$order->getPropertyView('user_phone')}
                            <div class="help">В формате: +7(123)9876543</div>
                        </div>
                    </div>
                {/if}
            </div>
            <div class="half fright">
                <h2>Адрес</h2>
                {if $have_pickup_points} {* Если есть пункты самовывоза *}
                   <div class="formPickUpTypeWrapper"> 
                       <input id="onlyPickUpPoints" type="radio" name="only_pickup_points" value="1" {if $order.only_pickup_points!==0}checked{/if}/> <label for="onlyPickUpPoints">{t}Забрать заказ самостоятельно{/t}</label><br/>  
                       <input id="onlyDelivery" type="radio" name="only_pickup_points" value="0" {if $order.only_pickup_points===0}checked{/if}/> <label for="onlyDelivery">{t}Доставить по адресу{/t}</label>  
                   </div>
                   <div id="formAddressSectionWrapper" class="formAddressSectionWrapper {if $order.only_pickup_points!==0}hidden{/if}">  
                {/if}
                {if count($address_list)>0}
                    <div class="formLine address">
                        {foreach $address_list as $address}
                        <span class="addressItem">
                            <input type="radio" name="use_addr" value="{$address.id}" id="adr_{$address.id}" {if $order.use_addr == $address.id}checked{/if}><label for="adr_{$address.id}">{$address->getLineView()}</label>
                            <a href="{$router->getUrl('shop-front-checkout', ['Act' =>'deleteAddress', 'id' => $address.id])}" class="deleteAddress"/></a><br>
                        </span>
                        {/foreach}
                        <span class="addressItem">
                            <input type="radio" name="use_addr" value="0" id="use_addr_new" {if $order.use_addr == 0}checked{/if}><label for="use_addr_new">Другой адрес</label><br>
                        </span>
                    </div>
                {else}
                    <input type="hidden" name="use_addr" value="0">
                {/if}             
                
                <div class="newAddress{if $order.use_addr>0 && $address_list} hidden{/if}">
                    <div class="formLine">
                        <label class="fielName">Страна</label><br>
                        {assign var=region_tools_url value=$router->getUrl('shop-front-regiontools', ["Act" => 'listByParent'])}
                        {$order->getPropertyView('addr_country_id', ['data-region-url' => $region_tools_url])}
                    </div>
                    <div class="formLine">
                        <span class="inline">
                            <label class="fielName">Область/край</label><br>
                            {assign var=regcount value=$order->regionList()}
                            <span {if count($regcount) == 0}style="display:none"{/if} id="region-select">
                                {$order.__addr_region_id->formView()}
                            </span>
                            
                            <span {if count($regcount) > 0}style="display:none"{/if} id="region-input">
                                {$order.__addr_region->formView()}
                            </span>
                        </span>
                        <span class="inline">
                            <label class="fielName">Город</label><br>
                            {$order->getPropertyView('addr_city')}
                        </span>
                    </div>
                    <div class="formLine">
                        <span class="inline" style="width:30%">
                            <label class="fielName">Индекс</label><br>
                            {$order.__addr_zipcode->formView()}
                        </span>
                        <span class="inline" style="width:60%">
                            <label class="fielName">Адрес</label><br>
                            {$order->getPropertyView('addr_address')}
                        </span>
                    </div>                        
                </div>
                <div class="formLine">
                    <label class="fielName">Контактное лицо</label><br>
                    {$order->getPropertyView('contact_person')}
                    <p class="help">Лицо, которое встретит доставку. Например: Иван Иванович Пуговкин</p>
                </div>
                {if $have_pickup_points}
                    </div>   
                {/if}
                {if $order.__code->isEnabled()}
                    <div class="formLine captcha">
                        <label class="fielName">Защитный код</label><br>
                        {$order->getPropertyView('code')}
                        <div class="help">Необходим для защиты от спам роботов</div>
                    </div>
                {/if}            
                
                {if $conf_userfields->notEmpty()}
                <h2>Дополнительные сведения</h2>
                    {foreach $conf_userfields->getStructure() as $fld}
                    <div class="formLine">
                        <label class="fielName">{$fld.title}</label><br>
                        {$conf_userfields->getForm($fld.alias)}
                        {assign var=errname value=$conf_userfields->getErrorForm($fld.alias)}
                        {assign var=error value=$order->getErrorsByForm($errname, ', ')}
                        {if !empty($error)}
                            <span class="formFieldError">{$error}</span>
                        {/if}                        
                    </div>
                    {/foreach}
                {/if}
                
                
            </div>
        </div>
    </div>
    <div class="hasAccount">
        <div class="workArea">
            <h2>Вход</h2>        
            <input type="hidden" name="ologin" value="1" id="doAuth" {if $order.user_type!='user'}disabled{/if}>
            <div class="formLine">
                <label class="fielName">E-mail</label><br>
                {$order->getPropertyView('login')}
            </div>
            <div class="formLine">
                <label class="fielName">Пароль</label><br>
                {$order->getPropertyView('password')}
            </div>
        </div>
        <div class="buttonLine">
            <input type="submit" value="Войти">
        </div>
    </div>    
</div>