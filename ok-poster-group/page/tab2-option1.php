<h2>Журнал работы плагина</h2>
<?php
$okposter_id = get_option('okposter_id'); //ID группы или пользователя
$okposter_friends_only = get_option('okposter_friends_only'); //Доступность записи, 0 - всем
$okposter_from_group = get_option('okposter_from_group'); //От чьего имени публиковать
$okposter_signed = get_option('okposter_signed');
$okposter_counttext = get_option('okposter_counttext');
$okposter_onoff = get_option('okposter_onoff');
$okposter_jornal = get_option('okposter_jornal');

$okposter_idsoft = get_option('okposter_aid'); //ID приложения
$okposter_token = get_option('okposter_accesstoken'); //Токен приложения

$active_plugin = get_option('active_plugins'); //Активные плагины
//
$plugins_url = admin_url() . 'options-general.php?page='.OKPOSTERBASE::URL_SUB_MENU.'&tab=jornal'; //URL страницы плагина
$dir_plugin_absolut = plugin_dir_path(__FILE__);
?>

<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Активированные плагины</th> 
        </tr>
    </thead>
    <tbody>
        <?php foreach ($active_plugin as $plug_name) { ?>
            <tr class="info">
                <td><?php echo $plug_name; ?></td>
            </tr>
        <?php } ?>
    </tbody>

</table>

<?php
if (isset($_GET['clearjornal'])) {
    update_option('okposter_jornal', array());
    ?>
    <script type = "text/javascript">
        document.location.href = "<?php echo $plugins_url; ?>";
    </script>
    <?php
}
?>
<h3>Журнал отправленных записей в OK.ru</h3>
<?php if (count($okposter_jornal)) { ?>
<a class="button" href="<?php echo $plugins_url . '&clearjornal'; ?>">Очистить журнал</a>

<table class="wp-list-table widefat fixed striped posts">
    <thead>
        <tr>
            <th>Дата и время добавления</th> 
            <th>Номер записи (id поста В Wordpress)</th>
            <th>Заголовок записи</th>
            <th>Ответ сервера OK.ru (статус добавления)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($okposter_jornal as $jornalprint) { ?>
            <tr class="info">
                <th><?php echo $jornalprint['time']; ?></th>
                <th><?php echo $jornalprint['idpost']; ?></th>
                <th><?php echo $jornalprint['title']; ?></th>
                <th><?php echo $jornalprint['status']; ?></th>
            </tr>
        <?php } ?>
    </tbody>
<?php } else { ?> 
<p>Нет записей</p>
<?php } ?>



</table>

