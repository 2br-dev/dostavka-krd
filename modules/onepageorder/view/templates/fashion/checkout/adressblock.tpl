<div class="onePageAdressBlock">
    {if !$is_auth}
        <div class="userType">
            <input type="radio" id="type-user" name="user_type" value="person" {if $order.user_type=='person'}checked{/if}><label for="type-user">{t}Частное лицо{/t}</label>
            <input type="radio" id="type-company" name="user_type" value="company" {if $order.user_type=='company'}checked{/if}><label for="type-company">{t}Компания{/t}</label>
            <input type="radio" id="type-noregister" name="user_type" value="noregister" {if $order.user_type=='noregister'}checked{/if}><label for="type-noregister">{t}Без регистрации{/t}</label>
            <input type="radio" id="type-account" name="user_type" value="user" {if $order.user_type=='user'}checked{/if}><label for="type-account">{t}Я регистрировался ранее{/t}</label>
        </div>
    {/if}    
    <div class="newAccount">    
        <h2>{t}Контактные данные{/t}</h2>
        {if $is_auth}
            <table class="themeTable companyTable">    
            {if $user.is_company}
                <tr>
                    <td>{t}Наименование компании{/t}</td>
                    <td>{$user.company}</td>
                </tr>        
                <tr>
                    <td>{t}ИНН{/t}</td>
                    <td>{$user.company_inn}</td>
                </tr>
            {/if}
                <tr>
                    <td>{t}Имя{/t}</td>
                    <td>{$user.name}</td>
                </tr>
                <tr>
                    <td>{t}Фамилия{/t}</td>
                    <td>{$user.surname}</td>
                </tr>
                <tr>
                    <td>{t}Отчество{/t}</td>
                    <td>{$user.midname}</td>
                </tr>
                <tr>
                    <td>{t}Телефон{/t}</td>
                    <td>{$user.phone}</td>
                </tr>            
                <tr>
                    <td>E-mail</td>
                    <td>{$user.e_mail}</td>
                </tr>                        
            </table>                    
            <div class="formLine changeUser">
                <a href="{urlmake logout=true}" class="link">{t}Сменить пользователя{/t}</a>
            </div>
        {else}
            <div class="userRegister">
                <div class="organization">
                    <div class="formLine">
                        <label class="fieldName">{t}Наименование компании{/t}</label>
                        {$order->getPropertyView('reg_company')}
                        <div class="help">{t}Например: ООО Аудиторская фирма "Аудитор"{/t}</div>
                    </div>
                    <div class="formLine">
                        <label class="fieldName">{t}ИНН{/t}</label>
                        {$order->getPropertyView('reg_company_inn')}
                        <div class="help">{t}10 или 12 цифр{/t}</div>
                    </div>
                </div>
                <div class="formLine">
                    <label class="fieldName">{t}Имя{/t}</label>
                    {$order->getPropertyView('reg_name')}
                    <div class="help">{t}Имя покупателя, владельца аккаунта{/t}</div>
                </div>
                <div class="formLine">
                    <label class="fieldName">{t}Фамилия{/t}</label>
                    {$order->getPropertyView('reg_surname')}
                    <div class="help">{t}Фамилия покупателя, владельца аккаунта{/t}</div>
                </div>
                <div class="formLine">
                    <label class="fieldName">{t}Отчество{/t}</label>
                    {$order->getPropertyView('reg_midname')}
                </div>        
                <div class="formLine">
                    <label class="fieldName">{t}Телефон{/t}</label>
                    {$order->getPropertyView('reg_phone')}
                    <div class="help">{t}В формате: +7(123)9876543{/t}</div>
                </div>
                <div class="formLine">
                    <label class="fieldName">e-mail</label>
                    {$order->getPropertyView('reg_e_mail')}
                </div>
                
                <div class="formLine">
                    <label class="fieldName">{t}Пароль{/t}</label>
                    <input type="checkbox" name="reg_autologin" {if $order.reg_autologin}checked{/if} value="1" id="reg-autologin">
                    <label for="reg-autologin">{t}Получить автоматически на e-mail{/t}</label>
                    <div class="help">{t}Нужен для проверки статуса заказа, обращения в поддержку, входа в кабинет{/t}</div>
                    <div id="manual-login" {if $order.reg_autologin}style="display:none"{/if}>
                        <div class="inline">
                            {$order.__reg_openpass->formView(['form'])}
                            <div class="help">{t}Пароль{/t}</div>
                        </div>
                        <div class="inline">
                            {$order.__reg_pass2->formView()}
                            <div class="help">{t}Повтор пароля{/t}</div>
                        </div>
                        
                        <div class="formFieldError">{$order->getErrorsByForm('reg_openpass', ', ')}</div>
                    </div>                
                </div>
                
                {foreach $reg_userfields->getStructure() as $fld}
                    <div class="formLine">
                    <label class="fieldName">{$fld.title}</label>
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
                    <label class="fieldName">{t}Ф.И.О.{/t}</label>
                    {$order->getPropertyView('user_fio')}
                    <div class="help">{t}Фамилия, Имя и Отчество покупателя, владельца аккаунта{/t}</div>
               </div>
               <div class="formLine">
                    <label class="fieldName">E-mail</label>
                    {$order->getPropertyView('user_email')}
                    <div class="help">{t}E-mail покупателя, владельца аккаунта{/t}</div>
               </div>
               <div class="formLine">
                    <label class="fieldName">{t}Телефон{/t}</label>
                    {$order->getPropertyView('user_phone')}
                    <div class="help">{t}В формате: +7(123)9876543{/t}</div>
               </div>
            </div>
        {/if}

        <div class="address">
            {if $have_to_address_delivery && $shop_config->isCanShowAddress()}
                <h2>{t}Адрес{/t}</h2>
                {if $have_pickup_points} {* Если есть пункты самовывоза *}
                   <div class="formPickUpTypeWrapper"> 
                       <input id="onlyPickUpPoints" type="radio" name="only_pickup_points" value="1" {if $order.only_pickup_points}checked{/if}/> <label for="onlyPickUpPoints">{t}Забрать заказ самостоятельно{/t}</label><br/>  
                       <input id="onlyDelivery" type="radio" name="only_pickup_points" value="0" {if !$order.only_pickup_points}checked{/if}/> <label for="onlyDelivery">{t}Доставить по адресу{/t}</label>  
                   </div>
                {/if}
                <div id="formAddressSectionWrapper" class="formAddressSectionWrapper {if $order.only_pickup_points}hidden{/if}">
                    {if count($address_list)>0}
                        <div class="formLine lastAddress">
                            {foreach $address_list as $address}
                            <span class="addressItem">
                                <input type="radio" name="use_addr" value="{$address.id}" id="adr_{$address.id}" {if $order.use_addr == $address.id}checked{/if}><label for="adr_{$address.id}">{$address->getLineView()}</label>
                                <a href="{$router->getUrl('shop-front-checkout', ['Act' =>'deleteAddress', 'id' => $address.id])}" class="deleteAddress"/></a>
                                <br>
                            </span>
                            {/foreach}
                            <span class="addressItem">
                                <input type="radio" name="use_addr" value="0" id="use_addr_new" {if $order.use_addr == 0}checked{/if}><label for="use_addr_new">{t}Другой адрес{/t}</label><br>
                            </span>
                        </div>
                    {else}
                        <input type="hidden" name="use_addr" value="0">
                    {/if}
                    
                    <div class="newAddress{if $order.use_addr>0 && $address_list} hide{/if}">
                        {if $shop_config.require_country}
                            <div class="formLine">
                                <label class="fieldName">{t}Страна{/t}</label>
                                {assign var=region_tools_url value=$router->getUrl('shop-front-regiontools', ["Act" => 'listByParent'])}
                                {$order->getPropertyView('addr_country_id', ['data-region-url' => $region_tools_url])}
                            </div>
                        {/if}
                        {if $shop_config.require_region || $shop_config.require_city}
                            <div class="formLine">
                                {if $shop_config.require_region}
                                    <span class="inline">
                                        <label class="fieldName">{t}Область/край{/t}</label>
                                        {assign var=regcount value=$order->regionList()}
                                        <span {if count($regcount) == 0}style="display:none"{/if} id="region-select">
                                            {$order.__addr_region_id->formView()}
                                        </span>

                                        <span {if count($regcount) > 0}style="display:none"{/if} id="region-input">
                                            {$order.__addr_region->formView()}
                                        </span>
                                    </span>
                                {/if}
                                {if $shop_config.require_city}
                                    <span class="inline">
                                        <label class="fieldName">{t}Город{/t}</label>
                                        {$order->getPropertyView('addr_city')}
                                    </span>
                                {/if}
                            </div>
                        {/if}
                        {if $shop_config.require_zipcode || $shop_config.require_address}
                            <div class="formLine">
                                {if $shop_config.require_zipcode}
                                    <span class="inline">
                                        <label class="fieldName">{t}Индекс{/t}</label>
                                        {$order.__addr_zipcode->formView()}
                                    </span>
                                {/if}
                                {if $shop_config.require_address}
                                    <span class="inline">
                                        <label class="fieldName">{t}Адрес{/t}</label>
                                        {$order->getPropertyView('addr_address')}
                                    </span>
                                {/if}
                            </div>
                        {/if}
                    </div>
                    {if $shop_config.show_contact_person}
                        <div class="formLine">
                            <label class="fieldName">{t}Контактное лицо{/t}</label>
                            {$order->getPropertyView('contact_person')}
                            <p class="help">{t}Лицо, которое встретит доставку. Например: Иван Иванович Пуговкин{/t}</p>
                        </div>
                    {/if}
                </div>
            {else}
                <input type="hidden" name="only_pickup_points" value="1"/>
            {/if}
            
            {if $order->__code->isEnabled()}
                <div class="formLine captcha">
                    <label class="fieldName">{$order->__code->getTypeObject()->getFieldTitle()}</label>
                    {$order->getPropertyView('code')}
                </div>
            {/if}
            
            {if $conf_userfields->notEmpty()}
            <div class="additional">
                <h2>{t}Дополнительные сведения{/t}</h2>
                {foreach $conf_userfields->getStructure() as $fld}
                <div class="formLine">
                    <label class="fieldName">{$fld.title}</label>
                    {$conf_userfields->getForm($fld.alias)}
                    {assign var=errname value=$conf_userfields->getErrorForm($fld.alias)}
                    {assign var=error value=$order->getErrorsByForm($errname, ', ')}
                    {if !empty($error)}
                        <span class="formFieldError">{$error}</span>
                    {/if}                        
                </div>
                {/foreach}
            </div>
            {/if}
        </div>
    </div>
    <div class="hasAccount">
        <div class="workArea">
            <h2>{t}Вход{/t}</h2>
            <input type="hidden" name="ologin" value="1" id="doAuth" {if $order.user_type!='user'}disabled{/if}>
            <div class="formLine">
                <label class="fieldName">E-mail</label>
                {$order->getPropertyView('login')}
            </div>
            <div class="formLine">
                <label class="fieldName">{t}Пароль{/t}</label>
                {$order->getPropertyView('password')}
            </div>
            <div class="buttonLine">
                <input type="submit" value="{t}Войти{/t}">
            </div>
        </div>
    </div> 
</div>