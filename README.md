# Projekt metryczki dla plików (JESZCZE W TRAKCIE FINALIZACJI)

### Wtyczka stworzona dla witryny bip.polsl.pl

### Instalacja

Pobierz ZIP, następnie w WordPress wybierz opcję **Wtyczki > Dodaj wtyczkę**, wybierz ZIP.  
Alternatywnie rozpakowany folder z wtyczką wrzuć w pliki serwera:  
*nazwa_wordpressa/wp-content/plugins*

**Technologie:** PHP, JavaScript
Wtyczka polega na stworzeniu odpowiedniej metryczki, dla każdego odnośnika spełniającego wprowadzone wymagania.

---

## Dokumentacja (JESZCZE SIĘ POZMIENIA)

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

polega na wczytaniu wszystkich odnośników zawartych w elemencie `<a>` na stronie, następnie sprawdzenie:

#### Etap 1: Sprawdzanie linku

1. Czy link jest zewnętrzny  
2. Czy na końcu występuje rozszerzenie (np. .pdf)  
3. Czy zawiera atrybuty pliku do pobrania  
4. Czy link znajduje się w wykluczonym elemencie (np. `header`, `footer`)  

#### Etap 2: Umieszczanie metryczki w zależności od kontekstu

1. Jeśli link znajduje się w elemencie o klasie `.mn-document-download`  
   → Unikalny przycisk do pobrania + dodanie tekstu „metryczka” + wymuszenie tabeli rozwijanej  
2. Jeśli szerokość kontenera ≥ 400 px  
   → Dodanie ikony obok linku, ikona rozwija tabelkę  
3. Jeśli szerokość kontenera ≥ 300 px  
   → Dodanie ikony obok linku, ikona wywołuje modal  
4. Inaczej (pozostałe przypadki)  
   → Dodanie ikony pod linkiem, ikona wywołuje modal  



### includes/metryczkaAdmin.php
Ustawienia > Metryczki załączników
Jeden wielki formularz, który decyduje o sposobach wykrywania plików, wykluczeniach, ich wyglądzie i wgląd w statystyki pobrań
### includes/metryczkaFormatowani.php
Funkcje formatujące daty, wymuszanie poprawnego formatu dat w polach tekstowych, przekształcanie skróconego URL na pełny itd.
### includes/metryczkaMedia.php
Obsługa zakładki Media, dodanie nowych pól w widoku edycji plików, obsługa metadanych
### includes/metryczkaWyglad.php
Domyślny wygląd metryczek, ważne przy pierwszej instalacji wtyczki, lub po wciśnięciu przycisku 'Resetuj' w Ustawieniach