# Projekt metryczki dla plików JESZCZE W TRAKCIE FINALIZACJI
### Wtyczka stworzona dla witryny bip.polsl.pl
### Instalacja
Pobierz ZIP, następnie w WordPress wybierz opcję Wtyczki > Dodaj wtyczkę wybierz ZIP.
Alternatywnie rozpakowany folder z wtyczką wrzuć w pliki serwera *nazwa_wordpressa\wp-content\plugins*

PHP, JavaScript
Wtyczka polega na wczytaniu wszystkich odnośników zawartych w elemencie *a* na stronie, następnie sprawdzenie:
1) czy link zewnętrzny
2) czy na końcu rozszerzenie
3) czy zawiera atrybuty pliku do pobrania
4) czy link znajduje się w wykluczonym elemencie np. header, footer
Po zakończeniu sprawdzania, następne sprawdzenie:
1) czy link zawiera się w elemencie o klasie mn-document-download
unikalny element z przyciskiem na pobranie, dodanie tekstu 
metryczka, wymuszenie tabeli rozwijaniej
2) czy szerokość kontenera w którym się znajduje >=400 px
dodanie ikony obok linku, ikona rozwija tabelkę
3) czy szerokość kontenera w którym się znajduje >=300 px
dodanie ikony obok linku, ikona wywołuje modal
3) reszta
dodanie ikony pod linkiem, ikona wywołuje modal

## Dokumentacja JESZCZE SIĘ POZMIENIA
```
METRYCZKAPLIK/
├── includes/
│   ├── metryczkaAdmin.php
│   ├── metryczkaFormatowanie.php
│   ├── metryczkaMedia.php
│   ├── metryczkaWyglad.php
├── metryczkaPlik.php
└── README.md
```
### metryczkaPlik.php
Główny plik, dołączanie wymaganych technologii i innych plików
Funkcja wykrywająca linki, filtrująca je, a następnie całe działanie metryczek w tym: np. obsługa liczby pobrań.