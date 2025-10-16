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

## Dokumentacja (jeszcze nie koniec)

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

- funkcja `check_pdf_links()`
   - sprawdza, czy jest włączone sprawdzanie odnośników
   - wczytuje wymagane technologie
   - wczytuje zapisane ustawienia i wygląd
   - DO USUNIĘCIA / KONTROLI admin-ajax.php
   - funkcja `formatDateDMY(dateStr)` konwertuje datę do obiektu Date i formatuje DD-MM-RRRR
   - funkcja `formatDateDMYHM(dateStr)` konwertuje datę do obiektu Date i formatuje DD-MM-RRRR GG:MM
   - funkcja `incrementDownloads(url)` aktualizuje ilość pobrań po kliknięciu w odnośnik z plikiem
   - rozszerzone wykrywanie (jeśli włączone w ustawieniach) polega na wykonaniu serii dłużej zajmujących sprawdzeń czy z drugiej strony odnośnika jest plik
   - funkcja ` isFileLink(link)` sprawdza czy odnośnik to plik, podstawowe wykrywanie, lub rozszerzone
   - pobranie wszystkich elementów `<a>`
   - przeskanowanie wszystkich elementów `<a>` funkcją ` isFileLink(link)` z wyłączeniem wykluczeń
   - stworzenie odpowiednich metryczek dla odnośników z plikiem
      - `<a>` z klasą '.mn-document-download'
         - stworzenie przycisku do wyświetlenia metryczki obok `<a>`
         - funkcja `toggleMetadataContainer($container, show)` do możliwie jak najpłynniejszego pokazywania metryczki
         - obsługa kliknięcia przycisku 'Metryczka'
            - pobranie danych o pliku
            - wyświetlenie danych w tabeli
         - obsługa kliknięcia przycisku 'Pobierz'
            - wywołanie funkcji `incrementDownloads` zwiększając ilość pobrań
      - sprawdzenie rozmiaru kontenera
         - kontener większy od 400 pikseli
            - pobranie treści oryginalnego linku
            - dodanie przycisku 'Metryczka'
            - obsługa przycisku
               - zanim dane zostaną wczytane pokaż 'Wczytywanie danych'
               - zapytanie o dane za pomocą ajax
               - wyświetlenie tabeli z danymi, lub błąd pobierania
            - obsługa kliknięcia odnośnika
               - wywołanie funkcji `incrementDownloads` zwiększając ilość pobrań
         - kontener większy, lub równy 300 pikseli
            - kopia treści oryginalnego linku
            - przycisk w stylu ikona obok odnośnika
            - zastąpienie oryginalny link kontenerem
            - obsługa kliknięcia ikony
               - pobranie danych pliku
               - wyświetla się okno 'modal' z tabelką z danymi
               - obsługa kliknięcia odnośnika
                  - inkrementacja ilości pobrań
         - kontener mniejszy od 300 pikseli
            - kopia treści oryginalnego linku
            - przycisk w stylu ikona pod odnośnika
            - zastąpienie oryginalny link kontenerem
            - obsługa kliknięcia ikony
               - pobranie danych pliku
               - wyświetla się okno 'modal' z tabelką z danymi
               - obsługa kliknięcia odnośnika
                  - inkrementacja ilości pobrań
   - obsługa kliknięcia w ikonę
   - obsługa kliknięcia w plik
   - obsługa zamknięcia modalu
   - struktura modalu
- funkcja `get_pdf_data()` pobiera metadane pliku zapisane w WordPress
- funkcja `increment_downloads()` przeszukuje metadane pliku i zwiększa ilość pobrań
- funkcja `pdf_metryczka_uninstall()` usuwa opcje wtyczki, ale zachowuje metadane plików

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

- funkcja `pdf_metryczka_format_date()` zwraca wprowadzoną datę w formacie RRRR-MM-DD 00:00:00, lub aktualną jeśli nie podano daty
- funkcja `pdf_metryczka_format_display_date()` zwraca datę w formacie DD-MM-RRRR, lub 'Nieznana'
- funkcja `pdf_metryczka_normalize_url()` jeśli adres pliku zaczyna się od '/' to dodaje prefix podany w ustawieniach przed '/'
- funkcja `function pdf_metryczka_sanitize_options($input)` upraszcza opcje pobrane z ustawnień
- funkcja `add_pdf_date_formatting_script()` tworzy skrypt jQuery do formatowania daty

## includes/metryczkaMedia.php
Obsługa zakładki Media, dodanie nowych pól w widoku edycji plików, obsługa metadanych

- funkcja `pdf_metryczka_attachment_fields($form_fields, $post)` dodaje pola 'Wytworzył' i 'Data wytworzenia' do edycji plików w zakładce Media.
- funkcja `pdf_metryczka_attachment_fields_save($post, $attachment)` pobiera wartość pól 'Wytworzył' i 'Data wytworzenia', formatuje daty, aktualizuje te dane w metadanych i tworzy licznik pobrań, jeśli jeszcze nie istnieje

## includes/metryczkaWyglad.php
Domyślny wygląd metryczek, ważne przy pierwszej instalacji wtyczki, lub po wciśnięciu przycisku 'Resetuj' w Ustawieniach

- funkcja `pdf_metryczka_default_options()` zawiera wszystkie domyślne style wykorzystywane przy pierwszym uruchomieniu, lub po wciśnięciu przycisków 'Resetuj' w zakładce 'Style i wygląd' w ustawieniach wtyczki
