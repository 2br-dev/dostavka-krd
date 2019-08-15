{addjs file="%yandexmarketcpa%/request_test.js"}

<form class="ya-form-test clearfix" action="{$yandex_base_url}/cart?auth-token={$auth_token}">
    <h3 class="title">{t}Актуализация корзины (/cart){/t}</h3>
	<div class="row">
		<div class="col-sm-6">
			<div class="form-group">
			<label>{t}Запрос(JSON){/t}:</label><br>
			<textarea class="request w-100" rows="10" name="requestbody" placeholder="{t}Здесь должен быть запрос в формате JSON{/t}"></textarea></div>
			<div class="form-group">
				<button type="submit" class="btn btn-success">{t}Отправить{/t}</button>
			</div>
		</div>

		<div class="col-sm-6">
			<div class="form-group">
				<label>{t}Ответ{/t}:</label><br>
				<textarea class="response w-100" rows="10"></textarea>
				<div class="error c-red"></div>
			</div>
		</div>
	</div>
</form>


<br><br>
<form class="ya-form-test clearfix" action="{$yandex_base_url}/order/accept?auth-token={$auth_token}">
	<h3 class="title">{t}Резервирование заказа (/order/accept){/t}</h3>
	<div class="row">
		<div class="col-sm-6">
			<div class="form-group">
				<label>{t}Запрос(JSON){/t}:</label><br>
				<textarea class="request w-100" rows="10" name="requestbody" placeholder="{t}Здесь должен быть запрос в формате JSON{/t}"></textarea></div>
			<div class="form-group">
				<button type="submit" class="btn btn-success">{t}Отправить{/t}</button>
			</div>
		</div>

		<div class="col-sm-6">
			<div class="form-group">
				<label>{t}Ответ{/t}:</label><br>
				<textarea class="response w-100" rows="10"></textarea>
				<div class="error c-red"></div>
			</div>
		</div>
	</div>
</form>

<br><br>
<form class="ya-form-test clearfix" action="{$yandex_base_url}/order/status?auth-token={$auth_token}">
	<h3 class="title">{t}Изменение статуса заказа (/order/status){/t}</h3>
	<div class="row">
		<div class="col-sm-6">
			<div class="form-group">
				<label>{t}Запрос(JSON){/t}:</label><br>
				<textarea class="request w-100" rows="10" name="requestbody" placeholder="{t}Здесь должен быть запрос в формате JSON{/t}"></textarea></div>
			<div class="form-group">
				<button type="submit" class="btn btn-success">{t}Отправить{/t}</button>
			</div>
		</div>

		<div class="col-sm-6">
			<div class="form-group">
				<label>{t}Ответ{/t}:</label><br>
				<textarea class="response w-100" rows="10"></textarea>
				<div class="error c-red"></div>
			</div>
		</div>
	</div>
</form>
