<?php
$okpfun = new OKPOSTERFUNCTION;
$okpfun->IfElseUpdate();

$okposter_id = get_option('okposter_id'); //ID группы или пользователя
$okposter_seckey = get_option('okposter_seckey'); //секретный ключ
$okposter_pubkey = get_option('okposter_pubkey'); //публичный ключ
$okposter_text_link = get_option('okposter_text_link'); //разворачивать ссылку
$okposter_gid = get_option('okposter_gid'); //группа в которую публиковать
$okposter_counttext = get_option('okposter_counttext');

$okposter_onoff = get_option('okposter_onoff');
$okposter_jornal = get_option('okposter_jornal');

$okposter_aid = get_option('okposter_aid'); //ID приложения
$okposter_accesstoken = get_option('okposter_accesstoken'); //Токен приложения

$active_plugin = get_option('active_plugins'); //Активные плагины
$okposter_posttype = get_option('okposter_posttype'); //Типы записей, где будет доступна отправка на стену

$plugins_url = admin_url() . 'options-general.php?page=' . OKPOSTERBASE::URL_ADMIN_MENU_PLUGIN; //URL страницы плагина
$dir_plugin_absolut = plugin_dir_path(__FILE__);
?>

<h2><?php _e('Настройка вашего сайта на работу с плагином ' . OKPOSTERBASE::NAME_TITLE_PLUGIN_PAGE) ?></h2>
<?php if (empty($okposter_aid)) { ?>
<p>
	<a class="button" href="<?php echo OKPOSTERBASE::URL_OK_DEVCREATE; ?>" target="_blank" title="После прохождения вы получите ID приложения, который нужно будет ввести в поле ниже и сохранить">Создать приложение</a>
</p>
<?php } ?>
<p>&nbsp;</p>
<form method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>

	<table class="form-table">
		<tr valign="top">
			<th scope="row">ID приложения (Application ID)</th>
			<td>
				<input id="idsofttip" title="Application ID из письма, после создания приложения" type="text" pattern="[0-9]*" name="okposter_aid" value="<?php echo $okposter_aid; ?>" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Токен приложения (access_token)</th>
			<td>
				<input id="tekentip" title="Это поле вы заполните данными пункта Вечный access_token" size="90" type="text" name="okposter_accesstoken" value="<?php echo $okposter_accesstoken; ?>" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Секретный ключ приложения</th>
			<td>
				<input id="toltipidsec" title="Введите сюда Секретный ключ приложения из письма" type="text" name="okposter_seckey" value="<?php echo $okposter_seckey; ?>" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Публичный ключ приложения</th>
			<td>
				<input id="toltipidsec" title="Введите сюда Публичный ключ приложения из письма" type="text" name="okposter_pubkey" value="<?php echo $okposter_pubkey; ?>" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">ID группы (gid)</th>
			<td>
				<input id="toltipid1" title="Введите сюда ID группы" type="text" name="okposter_gid" value="<?php echo $okposter_gid; ?>" />
			</td>
		</tr>

		<tr valign="top">
			<th scope="row">Ссылка</th>
			<td>
				<select name="okposter_text_link">
					<option value="0" <?php selected($okposter_text_link, '0', true); ?>>Обычная ссылка</option>
					<option value="1" <?php selected($okposter_text_link, '1', true); ?>>Разворачивать ссылку (картинка, заголовок, часть текста)</option>
				</select>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row">Размер сообщения</th>
			<td>
				<input id="toltiptitle" title="Количество слов для отправки на стену" type="text"  pattern="[-0-9]*" name="okposter_counttext" value="<?php
				if (empty($okposter_counttext)) {
					echo 0;
				} else {
					echo $okposter_counttext;
				}
				?>" />
				<p class="description">Количество отправляемых слов. Без ограничений — 0. Не отправлять текст — -1.".</p>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row">Действие по умолчанию</th>
			<td>
				<input type="checkbox" name="okposter_onoff" <?php checked($okposter_onoff, 'on', 1); ?>/>
				<span class="description">Публикация по умолчанию включена. Настройка так же влияет на публикацию запланированных записей.</span>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row">Типы записей</th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><span>Типы записей</span></legend>
				<?php
				$array_posts = get_post_types('', 'names', 'and');
				foreach ($array_posts as $v) {
					?>

					<label for="cbox-<?php echo $v; ?>">
						<input type="checkbox" name="okposter_posttype[<?php echo $v; ?>]" id="cbox-<?php echo $v; ?>" value="<?php echo $v; ?>" <?php
						if (isset($okposter_posttype[$v])) {
							checked($okposter_posttype[$v], $v, 1);
						} ?>>
						<?php echo $v; ?> </label><br>
					<?php } ?>
				</fieldset>
				<p class="description">Выберите типы «записей» при добавление которых будет работать функция отправки в OK.ru. По умолчанию всегда активен тип записей «Post». Если вам необходимо что бы была возможность отправлять данные из «Произвольных типов записей» поставте напротив «галочку». Если вы не знаете что такое «Произвольный тип записей» - ни чего не трогайте.</p>
			</td>
		</tr>
	</table>

	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="okposter_aid, okposter_accesstoken, okposter_seckey, okposter_id, okposter_pubkey, okposter_text_link, okposter_gid, okposter_counttext, okposter_onoff, okposter_posttype" />
	<p class="submit">
		<input type="submit" class="button" value="<?php _e('Save Changes') ?>" />
	</p>
</form>