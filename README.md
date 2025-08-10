Aby uruchomić aplikację należy wejść do katalogu src

      php main.php dev/prod

Po uruchomieniu aplikacji wyświetlą się logi z odpowiedniego środowiska.

Przy pierwszym uruchomieniu pojawi się informacja, że plik z logiem nie istnieje. Jest to normalna wiadomość. Plik utworzy się, kiedy zostanie zarejestrowana pierwsza zmiana dotycząca kolejki.

Każde dodanie kolejki, zaktualizowanie kolejki, dodanie/zaktualizowanie wagonu powodują przeliczenie statystyk dla zadanej kolejki. 
Po wykryciu błedu pojawia się na ekranie i jest zapisywany odpowiedni plik z logiem odpowiadającym danej kolejce.

Po aplikacji można poruszać się za pomocą user input. Każde polecenie trzeba zatwierdzić eneterem

- Odpowiednio l wyświetli listę kolejek
- w liczba wyświetli wagony dla odpowiedniej kolejki
- r wróci do widoku głównego gdzie pojawiają się logi
- za pomocą t można przełączać tryby. W przypadku l będą pojawiać się informacje szczególne lub chować, dla r konsola będzie automatycznie zjeżdzać na dół, lub będzie można poruszać się po pliku z logiem
- p wyświetli statystyki dotyzcące personelu dla kolejek
- k wyświetli statystyki dotyczące klientów dla kolejek

z aplikacji można wyjść za pomocą q zatwierdzonego eneterm lub ctrl + c
