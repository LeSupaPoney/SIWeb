<?php
	use Doctrine\ORM\Tools\Setup;
	use Doctrine\ORM\EntityManager;
	date_default_timezone_set('Europe/Berlin');
	require_once "vendor/autoload.php";
	$isDevMode = true;
	$config = Setup::createYAMLMetadataConfiguration(array(__DIR__ . "/config/yaml"), $isDevMode);
	$conn = array(
	'host' => 'dpg-cm23heun7f5s73erf0c0-a.frankfurt-postgres.render.com',

	'driver' => 'pdo_pgsql',
	'user' => 'cnam_db_wclc_user',
	'password' => 'UEuZEEo8Oo2kWP3l4TbYPi3W9Ihgi1jb',
	'dbname' => 'cnam_db_wclc',
	'port' => '5432'
	);


	$entityManager = EntityManager::create($conn, $config);



