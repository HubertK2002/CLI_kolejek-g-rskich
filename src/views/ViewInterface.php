<?php
namespace App\views;

interface ViewInterface
{
	public function render(): void;
	public function toggleMode(): void;
	public function getEnv(): string;
	public function scroll(int $direction): void;
}

?>