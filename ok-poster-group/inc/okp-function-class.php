<?php
class OKPOSTERFUNCTION {

	const METHOD_URL_OK = 'https://api.ok.ru/fb.do?'; //Метод постинга сообщений
	const METHOD_URL_VKIMAGE = 'https://api.vk.com/method/photos.getWallUploadServer?'; //Метод получения сервера загрузок изображения
	const METHOD_URL_VKIMAGE_SAVE = 'https://api.vk.com/method/photos.saveWallPhoto?'; //Загружает изображение в группу

	/**
	 * Условия для обновления версии плагина
	 */
	public function IfElseUpdate() {
		$okposter_posttype = get_option('okposter_posttype'); //Типы постов
		if (empty($okposter_posttype)) { //установка опции по умолчанию
			update_option('okposter_posttype', array('post' => 'post'));
		}
	}

	/**
	 * Основная функция
	 * Создавалка записи на стене вконтакте
	 */
	public function setOkWall($post_id) {

		$postData = get_post($post_id);
		$title = $postData->post_title;
		$text = $postData->post_content;
		$link_post = get_permalink($post_id); // ссылка на запись (теперь ЧПУ) 

		$okposter_aid = get_option('okposter_aid'); //Токен приложения Application ID
		$okposter_gid = get_option('okposter_gid'); //От чьего имени публиковать
		$okposter_accesstoken = get_option('okposter_accesstoken'); //Токен приложения access_token
		$okposter_seckey = get_option('okposter_seckey'); //Секретный ключ приложения
		$okposter_pubkey = get_option('okposter_pubkey'); //Публичный ключ приложения
		$okposter_text_link = get_option('okposter_text_link'); //разворачивать ссылку
		
		$okposter_id = get_option('okposter_id'); //ID группы или пользователя
		$okposter_signed = get_option('okposter_signed');
		$okposter_counttext = get_option('okposter_counttext');
		$postType = get_post_type($post_id);

		$text = str_replace('<!--more-->', '', strip_tags(strip_shortcodes($text))) . "\n\n"; //вырезаем шорткоды, теги, "далее"//

		$content = array('media' => array());

		if ($okposter_counttext == 0) { //пост без ограничений
			$text_clear = wp_kses($title, 'strip') . "\n\n " . wp_kses($text, 'strip');
			$content['media'][] = array('type' => 'text', 'text' => $text_clear);
		} elseif ($okposter_counttext > 0) { //Пост с обрезкой до кол-ва знаков указанных пользователем
			$text_clear = wp_kses($title, 'strip') . "\n\n " . wp_trim_words(wp_kses($text, 'strip'), $okposter_counttext, '...');
			$content['media'][] = array('type' => 'text', 'text' => $text_clear);
		} else {
			$text_clear = '';
		}

		unset($text);

		$content['caption'] = $title;

		$link = ($okposter_text_link)?'true':'false';

		$content['media'][] = array('type' => 'link', 'url' => $link_post);

		$jsonContent = json_encode($content); 

		$signature = '';

		$parameters = array(
			'application_key'   => $okposter_pubkey, 
			'attachment'        => $jsonContent, 
			'format'            => 'json', 
			'gid'               => $okposter_gid, 
			'method'            => 'mediatopic.post', 
			'text_link_preview' => $link, 
			'type'              => 'GROUP_THEME'
		);
		foreach ($parameters as $key => $value) {
			$signature .= $key.'='.$value;
		}
		$secKey = md5($okposter_accesstoken.$okposter_seckey);
		$sig = md5($signature.$secKey);
		$parameters['sig'] = $sig;
		$parameters['access_token'] = $okposter_accesstoken;

		$curlinfo = wp_remote_post(self::METHOD_URL_OK, array('body' => $parameters));
		if (is_wp_error($curlinfo)) {
			$errMessage = $curlinfo->get_error_message();
			echo 'Ошибка отправки: ' . $errMessage;
		}

		return $curlinfo['body'];
	}

	/**
	 * Функция логирования, для вкладки журнал
	 */
	public function logJornal($idpost, $title, $status) {

		$okposter_jornal_old = get_option('okposter_jornal');
		if (count($okposter_jornal_old) >= 50) {
			$okposter_jornal_old = array_slice($okposter_jornal_old, -40);
		}
		$time = current_time('mysql');
		$okposter_jornal_temp = array('time' => $time, 'idpost' => $idpost, 'title' => $title, 'status' => $status);
		$okposter_jornal_new = $okposter_jornal_old;
		array_push($okposter_jornal_new, $okposter_jornal_temp);
		update_option('okposter_jornal', $okposter_jornal_new);
	}

	/**
	 * Сравнивает версии PHP
	 * Пример 5.3.0
	 * Возвращает true если версия PHP меньше или больше указанной, зависит от знака
	 * @param $zn < или >
	 * @param $php_v версия
	 * @return bool true если текущая PHP менье указаной
	 */
	static public function compareOldPHPVer($php_v, $zn) {
		//PHP<5.3
		if (!defined('PHP_VERSION_ID')) {
			$version = explode('.', PHP_VERSION);
			define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
		}
		if (version_compare(PHP_VERSION, $php_v, "{$zn}")) {
			return true;
		} else {
			return false;
		}
	}

}