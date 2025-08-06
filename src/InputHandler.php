<?php
namespace App;

use React\EventLoop\LoopInterface;
use App\ViewManager;
use App\views\QueueView;
use App\views\WagonView;
use App\views\PersonnelView;
use App\views\ClientView;
use App\views\RedisLogView;

class InputHandler
{
	private static ?self $instance = null;
	private string $buffer = '';
	private LoopInterface $loop;

	private function __construct() {}

	public static function init(LoopInterface $loop): void
	{
		if (!self::$instance) {
			self::$instance = new self();
			self::$instance->loop = $loop;
			self::$instance->setup();
		}
	}

	private function setup(): void
	{
		$stdin = fopen('php://stdin', 'r');
		stream_set_blocking($stdin, false);

		$this->loop->addReadStream($stdin, function () use ($stdin) {
			$char = fgetc($stdin);

			if ($char === "\033") {
				$next1 = fgetc($stdin);
				$next2 = fgetc($stdin);

				if ($next1 === '[') {
					switch ($next2) {
						case 'A': // strzałka w górę
							ViewManager::getInstance()->get()->scroll(-1);
							break;
						case 'B': // strzałka w dół
							ViewManager::getInstance()->get()->scroll(+1);
							break;
					}
				}
				$this->buffer = '';
				$this->renderInputLine($this->buffer);
				return;
			}

			if ($char === "\n") {
				$this->handleCommand(trim($this->buffer));
				$this->buffer = '';
			} elseif (ord($char) === 127) { // backspace
				$this->buffer = substr($this->buffer, 0, -1);
			} else {
				$this->buffer .= $char;
			}

			$this->renderInputLine($this->buffer);
		});
	}

	private function handleCommand(string $input): void
	{
		$viewManager = ViewManager::getInstance();
		$view = $viewManager->get();

		switch ($input) {
			case 'q':
				leaveAlternateScreen();
				echo "Zamknięcie programu.\n";
				exit(0);

			case 'u':
				$view->render();
				break;

			case 't':
				$view->toggleMode();
				$view->render();
				break;

			case 'l':
				$newView = new QueueView($view->getEnv());
				$viewManager->set($newView);
				$newView->render();
				break;

			case 'p':
				$newView = new PersonnelView($view->getEnv());
				$viewManager->set($newView);
				$newView->render();
				break;

			case 'k':
				$currentView = new ClientView($view->getEnv());
				ViewManager::getInstance()->set($currentView);
				$currentView->render();
				break;

			case 'r':
				$newView = new RedisLogView($view->getEnv());
				$viewManager->set($newView);
				$newView->render();
				break;

			case (preg_match('/^w(?:agony)? (\d+)$/', $input, $matches) ? true : false):
				$newView = new WagonView($view->getEnv(), (int)$matches[1]);
				$viewManager->set($newView);
				$newView->render();
				break;
			default:
				break;
		}
	}

	private function renderInputLine(string $buffer): void
	{
		echo "\033[999;1H";
		echo "\033[2K"; 
		echo "> $buffer";
	}
}


?>