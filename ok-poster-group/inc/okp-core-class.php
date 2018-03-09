<?php

/**
 * Базовый Класс для плагина 
 */
class OKPOSTERBASE {

	const NAME_PLUGIN = 'OK Poster Group';
	const PATCH_PLUGIN = 'ok-poster-group';
	const URL_ADMIN_MENU_PLUGIN = 'okposter-page';
	const NAME_TITLE_PLUGIN_PAGE = 'OK Poster Group';
	const NAME_MENU_OPTIONS_PAGE = 'OKPosterGP';
	const NAME_SERVIC_ORIGINAL_TEXT = 'OK Poster Group plugin';
	const URL_PLUGIN_CONTROL = 'options-general.php?page=okposter-page';
	const URL_OK_DEVCREATE = 'https://ok.ru/devaccess'; //ССылка на создание придложения / Link to create an application on ok.ru

	/**
	 * URL страницы подменю
	 */
	const URL_SUB_MENU = 'okposter-page';

	/**
	 * Путь до страницы опций плагина HTML
	 */
	const OPTIONS_NAME_PAGE = 'page/option1.php';

	/**
	 * Констурктора класса
	 */
	public function __construct() {
		$this->addOptions();
		$this->addActios();
	}

	/**
	 * Добавление опций в базу данных
	 */
	public function addOptions() {
		add_option('okposter_aid'); //ID приложения (Application ID)
		add_option('okposter_gid'); //От группа в которую публиковать (gid)
		add_option('okposter_accesstoken'); //Токен приложения (access_token)
		add_option('okposter_seckey'); //секретный ключ приложения
		add_option('okposter_pubkey'); //публичный ключ приложения
		add_option('okposter_text_link', '0'); //ссылка - текст
		add_option('okposter_counttext', '40');
		add_option('okposter_onoff');

		add_option('okposter_signed', '1');
		add_option('okposter_jornal', array());
		add_option('okposter_posttype', array('post' => 'post')); //Типы выбранных записей
	}

	/**
	 * Опции вызываемые деактивацией
	 */
	public function deactivationPlugin() {
		delete_option('okposter_aid'); //ID приложения Application ID
		delete_option('okposter_gid'); //группа в которую публикуем gid
		delete_option('okposter_accesstoken'); //Токен приложения
		delete_option('okposter_seckey'); // секретный ключ приложения
		delete_option('okposter_pubkey'); // публичный ключ приложения
		delete_option('okposter_text_link'); //разворачивать ссылку
		delete_option('okposter_counttext');
		delete_option('okposter_onoff');

		delete_option('okposter_signed');
		delete_option('okposter_jornal');
		delete_option('okposter_posttype'); //Типы выбранных записей
	}

	/**
	 * Активация фишек
	 */
	public function addActios() {
		add_action('admin_menu', array($this, 'adminOptions'));
		add_filter('plugin_action_links', array($this, 'pluginLinkSetting'), 10, 2); //Настройка на странице плагинов
		add_action('add_meta_boxes', array($this, 'settingMetabox')); //Добавляем метабокс в пост

		$array_posts = get_option('okposter_posttype'); //Типы постов
		foreach ($array_posts as $k => $v) {
			add_action('save_' . $v, array($this, 'metaboxSavePost'), 9); //Сохранение галки в постах
			add_action('publish_' . $v, array($this, 'metaboxSavePost'), 9); //Сохранение галки в постах
			add_action('publish_' . $v, array($this, 'metaboxSentOK'), 11); //Отправка значений На стену ВК
			add_action('publish_future_' . $v, array($this, 'futureSentOk'), 10, 2); //Публикация отложенной записи
		}

		//фильтры для добавления ссылки  отправки записи
		add_filter('post_row_actions', array($this, 'okp_action_row'), 10, 2);
		add_filter('page_row_actions', array($this, 'okp_action_row'), 10, 2);

		//Ссылка на отправку записи на стену, появляется на страницах выбранных посттайпов
		//Отправка записи по клику на ссылку. Действие происходит при загрузке страницы посттайпа.
		add_action('admin_head-edit.php', array($this, 'admin_head_post_listing'));
	}

	/**
	 * Отправка запланированной записи
	 * @param type $post_id
	 * @param type $post
	 * @return type
	 */
	public function futureSentOk($post_id, $post) {

		$postData = get_post($post_id);
		$title = $postData->post_title;
		$okposter_onoff = get_option('okposter_onoff');
		$okpunk = new OKPOSTERFUNCTION;
		$status_post = $postData->post_status; //Статус поста
		if ($status_post == 'draft' || $status_post == 'private' || $status_post == 'trash') {
			return $post_id;
		}
		if ($okposter_onoff == 'on') {
			$status_sent = $okpunk->setOkWall($post_id); //Отправка текста
			$okpunk->logJornal($post_id, $title, $status_sent); //Логируем результаты
		}
		return $post_id;
	}

	/**
	 * 
	 */
	public function admin_head_post_listing() {

		global $post;
		if (in_array($post->post_type, get_option('okposter_posttype')) AND isset($_GET['okp_repost'])) {
			$post_id = $_GET['okp_repost'];
			$okpunk = new OKPOSTERFUNCTION;
			$status_sent = $okpunk->setOkWall($post_id); //Отправка текста
			$status_arr = json_decode($status_sent);
			//Удаляем из строки запроса номер записи
			$parts = parse_url($_SERVER["REQUEST_URI"]);
			$queryParams = array();
			parse_str($parts['query'], $queryParams);
			unset($queryParams['okp_repost']);
			$queryString = http_build_query($queryParams);
			$_SERVER["REQUEST_URI"] = $parts['path'] . '?' . $queryString;
			//--Конец обработки строки запроса
			$okpunk->logJornal($post_id, $post->post_title, $status_sent); //Логируем результаты

			if (OKPOSTERFUNCTION::compareOldPHPVer('5.3.0', '<') == FALSE) { //PHP>5.3
				if (!$status_arr->{'error'}) {
					add_action('all_admin_notices', function() {
						echo '<div class="notice notice-success"><p>Запись #' . $_GET['okp_repost'] . ' отправлена в ok.ru!</p></div>';
					});
				} else {
					add_action('all_admin_notices', function() {

						echo '<div class="notice notice-error"><p>Запись #' . $_GET['okp_repost'] . ' не отправлена ok.ru! Подробнее см. в <a href="' . get_admin_url() . okpOSTERBASE::URL_PLUGIN_CONTROL . '&tab=jornal">журнале</a></p></div>';
					});
				}
			}
		}
	}

	/**
	 * Фильтр для добавления ссылки на отправку записи в листинг постов/страниц
	 * @param array $actions Массив ссылок текущий
	 * @param object $post Информаця о текущем посте
	 * @return array Массив ссылок для листинга + добавленная ссылка
	 * 
	 */
	public function okp_action_row($actions, $post) {
		//Для указанных в настройках посттайпов добавляем ссылку
		if (in_array($post->post_type, get_option('okposter_posttype'))) {
			//Добавляем в строку запроса номер записи, которую надо репостить.
			$parts = parse_url($_SERVER["REQUEST_URI"]);
			$queryParams = array();
			parse_str($parts['query'], $queryParams);
			$queryParams['okp_repost'] = $post->ID;
			$queryString = http_build_query($queryParams);
			$url = $parts['path'] . '?' . $queryString;

			//--Конец обработки строки запроса
			//Добавляем ссылку
			$actions['okp-post'] = '<a href="http://' . $_SERVER["HTTP_HOST"] . $url . '">Отправить в ok.ru</a>';
		}
		return $actions;
	}

	/**
	 * Добавляет пункт настроек на странице активированных плагинов
	 */
	public function pluginLinkSetting($links, $file) {
		$this_plugin = self::PATCH_PLUGIN . '/index-okp.php';
		if ($file == $this_plugin) {
			$settings_link1 = '<a href="' . self::URL_PLUGIN_CONTROL . '">' . __("Settings", "default") . '</a>';
			array_unshift($links, $settings_link1);
		}
		return $links;
	}

	/**
	 * Параметры активируемого меню
	 */
	public function adminOptions() {
		$page_option = add_options_page(self::NAME_TITLE_PLUGIN_PAGE, self::NAME_MENU_OPTIONS_PAGE, 8, self::URL_ADMIN_MENU_PLUGIN, array($this, 'showSettingPage'));
	}

	/**
	 * Страница меню
	 */
	public function showSettingPage() {
		include_once WP_PLUGIN_DIR . '/' . self::PATCH_PLUGIN . '/' . self::OPTIONS_NAME_PAGE;
	}

	/**
	 * Метабокс в записи
	 */
	public function settingMetabox() {
		$array_posts = get_option('okposter_posttype'); //Типы постов
		foreach ($array_posts as $k => $v) { //Появляется только там где выбрал пользователь
			add_meta_box('okposter-metabox', self::NAME_SERVIC_ORIGINAL_TEXT, array($this, 'metaboxHtml'), "$v", 'side', 'high');
		}
	}

	/**
	 * Отрисовка МетаБокса
	 */
	public function metaboxHtml($post) {
		$okposter_onoff = get_option('okposter_onoff');
		// Используем nonce для верификации
		wp_nonce_field(plugin_basename(__FILE__), 'okposter_noncename');
		// Поля формы для введения данных
		if (empty($okposter_onoff)) {
			if (get_post_meta($post->ID, '_okposter_meta_value_key', true) == 'on') {
				$cheked = 'checked';
			} else {
				$cheked = '';
			}
		} elseif (!empty($okposter_onoff)) {
			$cheked = 'checked';
		}
		echo '<input type="checkbox" name="okposter_new_field" ' . $cheked . '/>';
		echo '<span class="description">Добавлять текст на стену OK при публикации?</span>';
		echo '<br><input type="radio" name="okposter_new_field_radio" checked value="1">При публикации</>';
		echo '<br><input type="radio" name="okposter_new_field_radio" value="2">При обновлении</>';
	}

	/**
	 * Сохранение данных Метабокса при сохрание записи
	 */
	public function metaboxSavePost($post_id) {

		// проверяем nonce нашей страницы, потому что save_post может быть вызван с другого места.
		if (!wp_verify_nonce($_POST['okposter_noncename'], plugin_basename(__FILE__))) {
			return $post_id;
		}

		// проверяем, если это автосохранение ничего не делаем с данными нашей формы.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}

		// проверяем разрешено ли пользователю указывать эти данные
		if (!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}

		$data1 = (isset($_POST['okposter_new_field'])):'on'?'';
		//Обновление данных в базе даннхы
		update_post_meta($post_id, '_okposter_meta_value_key', $data1);
	}

	/**
	 * Отправка данных в группу ОК при публикации записи
	 */
	public function metaboxSentOK($post_id) {

		$postData = get_post($post_id);
		$title = $postData->post_title;

		$okposter_onoff = get_option('okposter_onoff');
		$okpunk = new OKPOSTERFUNCTION;
		$status_post = $postData->post_status; //Статус поста
		$date_create = $postData->post_date_gmt; //Дата создания записи
		$date_modificed = $postData->post_modified_gmt; //Дата изменения записи
		$radio_chek = $_POST['okposter_new_field_radio']; // получаем значение РадиоБутон

		if (!wp_verify_nonce($_POST['okposter_noncename'], plugin_basename(__FILE__))) {

			return $post_id;
		}

		// проверяем, если это автосохранение ничего не делаем с данными нашей формы.

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {

			return $post_id;
		}

		// проверяем разрешено ли пользователю указывать эти данные
		if (!current_user_can('edit_post', $post_id)) {

			return $post_id;
		}

		if ($status_post == 'draft' OR $status_post == 'private' OR $status_post == 'trash') {

			return $post_id;
		}

		$chek = get_post_meta($post_id, '_okposter_meta_value_key', true);

		if ($chek == 'on') {

			if ($date_create == $date_modificed) {
				$status_sent = $okpunk->setOkWall($post_id); //Отправка текста
				$okpunk->logJornal($post_id, $title, $status_sent); //Логируем результаты
			} elseif ($date_create !== $date_modificed and $radio_chek !== '1') {
				$status_sent = $okpunk->setOkWall($post_id); //Отправка текста
				$okpunk->logJornal($post_id, $title, $status_sent); //Логируем результаты
			}

		} else {
			return $post_id;
		}

		return;
	}

	/**
	 * Активная вкладка в админпанели плагина
	 * @return string css Класс для активной вкладки
	 */
	static public function adminActiveTab($tab_name = null, $tab = null) {

		if (isset($_GET['tab']) && !$tab)
			$tab = $_GET['tab'];
		else
			$tab = 'general';

		$output = '';
		if (isset($tab_name) && $tab_name) {
			if ($tab_name == $tab)
				$output = ' nav-tab-active';
		}
		echo $output;
	}

	/**
	 * Подключает нужную страницу исходя из вкладки на страницы настроек плагина
	 * @result include_once tab{номер вкладки}-option1.php
	 */
	static public function tabViwer() {
		$tab = $_GET['tab'];
		switch ($tab) {
			case 'jornal':
				include_once WP_PLUGIN_DIR . '/' . self::PATCH_PLUGIN . '/page/tab2-option1.php';
				break;
			case 'help':
				include_once WP_PLUGIN_DIR . '/' . self::PATCH_PLUGIN . '/page/tab3-option1.php';
				break;
			default :
				include_once WP_PLUGIN_DIR . '/' . self::PATCH_PLUGIN . '/page/tab1-option1.php';
		}
	}

}