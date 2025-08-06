<?php

require_once '../vendor/autoload.php';
require_once 'utils.php';
require_once 'redis_connector.php';
require_once 'views/QueueView.php';
require_once 'views/WagonView.php';
require_once 'InputHandler.php';
require_once 'views/ViewManager.php';
require_once 'views/PersonnelView.php';
require_once 'views/ClientView.php';
require_once 'views/RedisLogView.php';
require_once 'event/RedisEventListener.php';
require_once 'cli/RedisEventLogger.php';
require_once 'notifications/NotificationManager.php';
require_once 'ConfigLoader.php';

use App\InputHandler;
use App\views\RedisLogView;
use React\EventLoop\Loop;
use App\ViewManager;
use App\Event\RedisEventListener;

$loop = Loop::get();
$env = $argv[1];
ConfigLoader::init($env);
enterAlternateScreen();

$currentView = new RedisLogView($env);
ViewManager::getInstance()->set($currentView);
$currentView->render();

InputHandler::init($loop); 

$listener = new RedisEventListener($loop);
$listener->listen();

register_shutdown_function(function () {
	leaveAlternateScreen();
	system('stty echo icanon ixon icrnl inlcr');
	$error = error_get_last();
	if ($error !== null) {
		echo "\n💥 Błąd krytyczny:\n";
		print_r($error);
	}
});

set_exception_handler(function ($e) {
	leaveAlternateScreen();
	system('stty echo icanon ixon icrnl inlcr');
	echo "\n🚨 Wyjątek: " . $e->getMessage() . "\n";
	exit(1);
});

$loop->addSignal(SIGINT, function () {
	leaveAlternateScreen();
	system('stty echo icanon ixon icrnl inlcr');
	echo "\n👋 Zamykanie programu przez Ctrl+C\n";
	exit(0);
});

$loop->run();
