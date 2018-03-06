<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
	<a class="nav-tab <?php OKPOSTERBASE::adminActiveTab('general'); ?>" href="<?php echo add_query_arg( array( 'page' => OKPOSTERBASE::URL_SUB_MENU, 'tab' => 'general' ), 'options-general.php' ); ?>"><span class="glyphicon glyphicon-cog"></span> Настройки</a>

	<a class="nav-tab <?php OKPOSTERBASE::adminActiveTab('jornal'); ?>" href="<?php echo add_query_arg( array( 'page' => OKPOSTERBASE::URL_SUB_MENU, 'tab' => 'jornal' ), 'options-general.php' ); ?>"><span class="glyphicon glyphicon-wrench"></span> Журнал</a>
	<a class="nav-tab <?php OKPOSTERBASE::adminActiveTab('help'); ?>" href="<?php echo add_query_arg( array( 'page' => OKPOSTERBASE::URL_SUB_MENU, 'tab' => 'help' ), 'options-general.php' ); ?>"><span class="glyphicon glyphicon-list"></span> Справка</a>
</h2>
<?php OKPOSTERBASE::tabViwer();//Показать страницу в зависимости от закладки ?>