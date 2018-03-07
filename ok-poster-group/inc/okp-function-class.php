<?php

/**
 * Класс с функционалом и обработками
 */
class OKPOSTERFUNCTION {

	const METHOD_URL_OK = 'https://api.ok.ru/fb.do?'; //Метод постинга сообщений
	const METHOD_URL_VKIMAGE = 'https://api.vk.com/method/photos.getWallUploadServer?'; //Метод получения сервера загрузок изображения
	const METHOD_URL_VKIMAGE_SAVE = 'https://api.vk.com/method/photos.saveWallPhoto?'; //Загружает изображение в группу

	/**
	 *
	 * @var string Прокси сервер для CURL IP:порт
	 */

	var $proxy;

	/**
	 *
	 * @var string Прокси сервер для CURL Пользователь:пароль
	 */
	var $proxy_userpaswd;

	/**
	 * Конструктор класса
	 */
	public function __construct() {
		$this->proxy = get_option('okposter_proxy');
		$this->proxy_userpaswd = get_option('okposter_proxy_userpaswd');
	}

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

		//$image = $this->setImageOK($text, $post_id)->id; //Получаем ID фотографии
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


		/*echo "<pre>";
		print_r($parameters);
		echo "</pre>";

		echo "<pre>";
		print_r($content);
		echo "</pre>";

		exit;
		
		/*$result = $this->sentRequesOK(self::METHOD_URL_OK, $parameters, $this->proxy);

		return $result;*/

		$curlinfo = wp_remote_post(self::METHOD_URL_OK, array('body' => $parameters));
		if (is_wp_error($curlinfo)) {
			$errMessage = $curlinfo->get_error_message();
			echo 'Ошибка отправки: ' . $errMessage;
		}

		return $curlinfo['body'];
	}


	/**
	 * Проверка Чекеда
	 * @param string $options Опция из базы данных
	 * @param string $value Текущее значение для сравнения (например значение из цикла)
	 * @return echo checked или пусто
	 */
	public function chekedOptions($options, $value) {
		if (!empty($options) or ! empty($value)) {
			if ($options == $value) {
				echo 'checked';
			} elseif ($options !== $value) {
				echo '';
			}
		}
	}

	/**
	 * Получение изображения поста из миниатюры или из прикрепленного изображения
	 */
	public function setImageOK($text, $post_id) {
		$okposter_id = get_option('okposter_id'); //ID группы или пользователя
		$images_post = get_attached_file(get_post_thumbnail_id($post_id));

		$okposter_accesstoken = get_option('okposter_accesstoken'); //Токен приложения
		if (empty($images_post)) {
			$media = get_attached_media('image', $post_id);
			$image = array_shift($media);
			$images_post = get_attached_file($image->ID); //Изображение прикреплённое к посту
			if (empty($images_post)) {
				$images_post = $this->searchImageText($text); // Ищем изображение в тексте
				if (empty($images_post)) {
					return false;
				}
			}
		}
		$argument = array(
			"group_id" => trim($okposter_id, '-'),
			"version" => "5.27"
		);
		$curlinfo = $this->sentRequesVK(self::METHOD_URL_VKIMAGE, $argument, $this->proxy);
		unset($argument);

		if (!empty($curlinfo)) {
			$UrlObj = json_decode($curlinfo);
			$urlimgvk = $UrlObj->response->upload_url;
		}
		if (!empty($urlimgvk)) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $urlimgvk);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			if (self::compareOldPHPVer('5.6.0', '<')) {
				curl_setopt($curl, CURLOPT_POSTFIELDS, array('photo' => '@' . $images_post));
			} elseif (self::compareOldPHPVer('5.5.0', '>')) {
				curl_setopt($curl, CURLOPT_POSTFIELDS, ['photo' => new CurlFile($images_post)]);
			}
			if (!empty($this->proxy)) {
				curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
				curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 12);
			}
			if (!empty($this->proxy_userpaswd)) {
				curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->proxy_userpaswd);
			}
			$curlinfo = curl_exec($curl); //Результат запроса
			$response = curl_getinfo($curl); //Информация о запросе
			curl_close($curl);
			$imageObject = json_decode($curlinfo);
			if (!empty($imageObject->server) && !empty($imageObject->photo) && !empty($imageObject->hash)) {

				$argument = array(
					"group_id" => trim($okposter_id, '-'),
					"server" => $imageObject->server,
					"photo" => $imageObject->photo,
					"hash" => $imageObject->hash
				);
				$curlinfo = $this->sentRequesVK(self::METHOD_URL_VKIMAGE_SAVE, $argument, $this->proxy);
				unset($argument);

				$resultObject = json_decode($curlinfo);

				if (isset($resultObject) && isset($resultObject->response[0]->id)) {
					return $resultObject->response[0];
				} else {
					return false;
				}
			}
		}
	}

	/**
	 * Поиск изображений в тексте
	 * @param string $text Текст поста с тегами HTML
	 * @return string абсолютный серверный путь до изображения
	 */
	public function searchImageText($text) {
		$first_img = '';
		ob_start();
		ob_end_clean();
		$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $text, $matches);
		$first_img = $matches [1] [0];
		$col1 = strlen(WP_CONTENT_URL);
		$col2 = strlen($first_img);
		$patch = substr($first_img, $col1, $col2);
		$result = WP_CONTENT_DIR . $patch;
//В перспективе изображение по умолчанию (сейчас не реализованно)
		if (empty($first_img)) {
			return false;
//$first_img = "/images/default.jpg";
		}
		return $result;
	}

	/**
	 * 
	 * Относительный путь до фото преобразует в абсолютный
	 * @return string абсолютный серверный путь до изображения
	 */
	public function searchImgaeHTTP($http) {
		$text = '<img src="' . $http . '">';
		$first_img = '';
		$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $text, $matches);
		$first_img = $matches [1] [0];
		$col1 = strlen(WP_CONTENT_URL);
		$col2 = strlen($first_img);
		$patch = substr($first_img, $col1, $col2);
		$result = WP_CONTENT_DIR . $patch;
// В перспективе изображение по умолчанию (сейчас не реализованно)
		if (empty($first_img)) {
			return false;
//$first_img = "/images/default.jpg";
		}
		return $result;
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
	 * Получает списко категорий постов (НЕ WOO)
	 */
	public static function getAllCategory() {
		$arg = array(
			'hide_empty' => '0',
			'order' => 'ASC'
		);
		$categories = get_categories($args);
		foreach ($categories as $cat) {
			?>

			<p><input name="okposter_prooptions[selectcat[<?php echo $cat->cat_ID; ?>]]" type="checkbox" value="<?php echo $cat->cat_ID; ?>" <?php
				if (isset($okposter_prooptions['selectcat'][$cat->cat_ID])) {
					checked($okposter_prooptions['selectcat'][$cat->cat_ID], $cat->cat_ID, 1);
				}
				?>><?php echo $cat->name; ?></p>
			<?php
		}
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
