# Projekt metryczki dla plików

### Wtyczka stworzona dla witryny bip.polsl.pl

**Technologie:** PHP, JavaScript
Wtyczka polega na stworzeniu odpowiedniej metryczki dla każdego odnośnika który prowadzi bezpośrednio do pliku.
Wygląd metryczki można modyfikować w ustawieniach.
Istnieje kilka różnych wariantów metryczek, które strona wybiera na podstawie dostępnego miejsca wokół odnośnika.

### Instalacja

Pobierz ZIP, następnie w WordPress wybierz opcję **Wtyczki > Dodaj wtyczkę**, wybierz ZIP.  
Alternatywnie rozpakowany folder z wtyczką wrzuć w pliki serwera:  
*nazwa_wordpressa/wp-content/plugins*

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

## includes/metryczkaAdmin.php
Ustawienia > Metryczki załączników
Jeden wielki formularz, który decyduje o sposobach wykrywania plików, wykluczeniach, ich wyglądzie i wgląd w statystyki pobrań

- funkcja `pdf_metryczka_menu()` dodaje panel administracyjny
- funkcja `pdf_metryczka_add_help_tab()` dodaje przycisk 'pomoc' do ustawień
- funkcja `pdf_metryczka_register_settings()` rejestruje wprowadzone, lub domyślne ustawienia
- funkcja `pdf_metryczka_settings_page()` tworzy stronę z ustawieniami
   - jeśli elementy zawarte w metryczce nie mają wprowadzonych styli, to im nadaje domyślne
   - wczytanie wymaganych technologii
   - strona z ustawieniami
     - zakładka 'Ustawienia ogólne'
        - wybór czy 'Automatyczne wykrywanie załączników'
        - wybór czy 'Rozszerzone wykrywanie załączników'
        - wybór czy 'Wyświetlanie ikony metryczki' (JESZCZE NIE)
        - pole z 'Prefix URL'
        - 'Wykluczenia' - do określenia elementów i klasy, które mają zostać wykluczone z dodawania metryczek
             - pole 'Wykluczone elementy HTML'
             - pole 'Wykluczone elementy CSS'
     - zakładka 'Style i wygląd'
          - wygląd przycisku metryczki
               - pole do zmiany stylu
               - przycisk do resetowania stylu do wartości domyślnej
               - podgląd zmian przycisku na żywo
          - wygląd metryczki w oknie 'modal'
               - pole do zmiany stylu
               - przycisk do resetowania stylu do wartości domyślnej
               - podgląd zmian przycisku na żywo
          - wygląd metryczki w formie tabeli rozwijanej
               - pole do zmiany stylu
               - przycisk do resetowania stylu do wartości domyślnej
               - podgląd zmian przycisku na żywo
     - zakładka 'Statystyki'
          - funkcja pobierająca dane plików
          - tabelka wyświetlająca 5 najczęściej pobieranych plików
     - skrypt
          - obsługujący zakładki
          - resetujący domyślne wartości w 'wyglądzie'
          - obsługujący podgląd na żywo
- funkcja `pdf_metryczka_check_options()` aktualizuje ustawienia przy każdym załadowaniu ustawień

## includes/metryczkaFormatowani.php
Funkcje formatujące daty, wymuszanie poprawnego formatu dat w polach tekstowych, przekształcanie skróconego URL na pełny itd.
## includes/metryczkaMedia.php
Obsługa zakładki Media, dodanie nowych pól w widoku edycji plików, obsługa metadanych
## includes/metryczkaWyglad.php
Domyślny wygląd metryczek, ważne przy pierwszej instalacji wtyczki, lub po wciśnięciu przycisku 'Resetuj' w Ustawieniach
