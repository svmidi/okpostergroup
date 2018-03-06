<?php
/*
  Plugin Name: OK Poster Group
  Plugin URI: 
  Description: Добавляет ваши записи на страницу группы Однокласники, простой и удобный кроспостинг в социальную сеть
  Version: 1.0
  Author: svmidi
  Author URI: http://svm-zone.ru
 */

require_once (WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)) . '/inc/okp-core-class.php');
require_once (WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)) . '/inc/okp-function-class.php');
$okpbase = new OKPOSTERBASE();
$okpfun = new OKPOSTERFUNCTION;
register_deactivation_hook(__FILE__, array($okpbase, 'deactivationPlugin'));