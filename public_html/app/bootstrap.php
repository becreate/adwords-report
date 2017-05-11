<?php

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;

// nastavení prostředí laděnky
$configurator->setDebugMode(true);
// Do adresáře log/ se bodou ukládat výstupy Laděnky
$configurator->enableTracy(__DIR__ . '/../log');

$configurator->setTimeZone('Europe/Prague');

// místo kam se bude ukládat cache paměť
$configurator->setTempDirectory(__DIR__ . '/../temp');

// automatické načítání zdrojových souborů aplikace
$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

// přidání konfiguračního souboru
$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$configurator->addParameters(array(
	'storiesDir'    => __DIR__ . '/../stories/',
	'reportDir'     => __DIR__ . '/../stories/reports',
));

$container = $configurator->createContainer();

return $container;
