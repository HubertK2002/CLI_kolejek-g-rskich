<?php
    function clearScreen(): void {
        echo "\033[2J\033[H";
		echo "🔄 Serwer CLI ReactPHP uruchomiony...\n";
    }

    function enterAlternateScreen() {
        system('tput smcup');
		system('stty -icanon -echo');
    }

    function leaveAlternateScreen() {
		system('stty sane');
    	system('tput rmcup');
    }
?>
