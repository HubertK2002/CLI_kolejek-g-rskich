<?php
namespace App;

use App\views\ViewInterface;

class ViewManager
{
	private static ?ViewManager $instance = null;
	private ViewInterface $currentView;

	private function __construct() {}

	public static function getInstance(): self
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function set(ViewInterface $view): void
	{
		$this->currentView = $view;
	}

	public function get(): ViewInterface
	{
		return $this->currentView;
	}
}
