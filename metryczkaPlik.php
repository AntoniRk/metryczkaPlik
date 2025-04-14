<?php

/*
Plugin Name: Metryczki dla PDF
Description: Automatycznie wykrywa linki do <strong>PDF</strong> i dodaje przycisk do wyświetlania ich <strong>metryczek</strong>. Nowe dane przy dodawaniu plików i edycji. 
Version: 0.36
Author: Antoni Roskosz
*/

// Dodaj stałą lokalizację wtyczki
define( 'LOKALIZACJA_WTYCZKI', plugin_dir_path( __FILE__ ) );

// Załącz pozostałe pliki z funkcjami
include_once LOKALIZACJA_WTYCZKI . 'includes/admin-functions.php';
include_once LOKALIZACJA_WTYCZKI . 'includes/public-functions.php';
include_once LOKALIZACJA_WTYCZKI . 'includes/helper-functions.php';

if (!defined('ABSPATH')) die();

function pdf_metryczka_add_help_tab()
{
    $screen = get_current_screen();

    if ($screen->id != 'settings_page_pdf-metryczka-settings') {
        return;
    }

    $screen->add_help_tab(array(
        'id'      => 'pdf_metryczka_help_url_prefix',
        'title'   => 'Prefix URL',
        'content' => '<p><strong>Prefix URL</strong> - Ta opcja pozwala określić prefix, który będzie dodawany do względnych adresów URL plików PDF (np. zaczynających się od "/"). Jest to szczególnie przydatne w środowiskach, gdzie ścieżki względne wymagają dodatkowego prefiksu, aby być poprawnie rozpoznawane przez system WordPress.</p>
                     <p>Domyślna wartość to <code>https://bip.polsl.pl</code>, ale możesz ją zmienić zgodnie z konfiguracją swojej witryny.</p>
                     <p>Przykład: Jeśli masz link do pliku <code>/wp-content/uploads/2023/01/dokument.pdf</code>, wtyczka automatycznie przekształci go na <code>https://bip.polsl.pl/wp-content/uploads/2023/01/dokument.pdf</code> podczas próby identyfikacji załącznika.</p>'
    ));
}
add_action('admin_head', 'pdf_metryczka_add_help_tab');

// Inicjalizacja opcji przy aktywacji
register_activation_hook(__FILE__, function () {
    add_option('pdf_metryczka_options', pdf_metryczka_default_options());
});

function pdf_metryczka_format_date($date_str)
{
    if (!$date_str) {
        return current_time('mysql');
    }

    // Sprawdź czy data zawiera część czasową
    if (!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $date_str)) {
        // d-m-Y => RRRR-MM-DD
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date_str, $matches)) {
            $date_str = $matches[3] . '-' . $matches[2] . '-' . $matches[1] . ' 00:00:00';
        }
        // RRRR-MM-DD, dodaj 00:00:00
        else if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
            $date_str .= ' 00:00:00';
        }
    }

    return $date_str;
}

// Formatuje datę do wyświetlenia w formacie DD-MM-YYYY
function pdf_metryczka_format_display_date($date_str)
{
    if (!$date_str) return 'Nieznana';

    try {
        $timestamp = strtotime($date_str);
        if ($timestamp === false) return $date_str;

        return date('d-m-Y', $timestamp);
    } catch (Exception $e) {
        return $date_str ?: 'Nieznana';
    }
}

function pdf_metryczka_normalize_url($url)
{
    if (substr($url, 0, 1) === '/' && substr($url, 0, 2) !== '//') {
        $options = get_option('pdf_metryczka_options');
        $prefix = isset($options['url_prefix']) ? $options['url_prefix'] : site_url();
        $prefix = rtrim($prefix, '/');
        return $prefix . $url;
    }

    return $url;
}

function check_pdf_links()
{
    // Sprawdź, czy funkcja jest włączona w opcjach
    $options = get_option('pdf_metryczka_options');
    if (!$options['enable_auto_detection']) {
        return;
    }

    wp_enqueue_script('jquery');
    wp_enqueue_style('bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap-bundle', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/js/bootstrap.bundle.min.js', array('jquery'), null, true);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

    add_action('wp_footer', function () {
        $options = get_option('pdf_metryczka_options');
        $button_css = isset($options['button_css']) ? $options['button_css'] : '';
        $modal_css = isset($options['modal_css']) ? $options['modal_css'] : '';
        $table_css = isset($options['table_css']) ? $options['table_css'] : '';
        $display_icon = isset($options['display_icon']) ? $options['display_icon'] : 1;
        $excluded_elements = isset($options['excluded_elements']) ? $options['excluded_elements'] : '';
        $excluded_classes = isset($options['excluded_classes']) ? $options['excluded_classes'] : '';
        $enable_extended_detection = isset($options['enable_extended_detection']) ? $options['enable_extended_detection'] : 1;
?>
        <style>
            <?php echo $button_css; ?><?php if (!$display_icon): ?>.fa-info-circle {
                display: none;
            }

            <?php endif; ?><?php echo $modal_css; ?><?php echo $table_css; ?>.mn-document-metryczka {
                display: inline-block;
                color: #007bff;
                text-decoration: underline;
                cursor: pointer;
                margin-right: 10px;
            }

            .mn-document-metryczka:hover {
                color: #0056b3;
            }

            .mn-document-metryczka i {
                font-size: 1.2em;
            }

            .mn-document-download {
                flex-wrap: wrap;
            }

            .mn-document-metadata {
                flex-basis: 100%;
                width: 100%;
                clear: both;
            }

            .modal-backdrop {
                overflow: auto;
            }

            @media (max-width: 600px) {

                .mn-document-metadata,
                .pdf-metadata-container {
                    font-size: 14px !important;
                    padding: 5px !important;
                }

                .mn-document-metadata table.mn-metadata-table td,
                .pdf-metadata-container table.mn-metadata-table td {
                    display: block !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }
            }
        </style>
        <script>
            var enableExtendedDetection = <?php echo $enable_extended_detection ? 'true' : 'false'; ?>;
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var excludedElements = <?php
                                    $elements = array_filter(array_map('trim', explode(',', $excluded_elements)));
                                    // Dodaj domyślne elementy do wykluczenia (do usunięcia?)
                                    $elements = array_merge($elements, ['header', '.header', '#header', 'footer', '.footer', '#footer']);
                                    echo json_encode($elements);
                                    ?>;

            var excludedClasses = <?php
                                    echo json_encode(array_filter(array_map('trim', explode(',', $excluded_classes))));
                                    ?>;

            var debugMode = <?php echo isset($options['debug_mode']) && $options['debug_mode'] ? 'true' : 'false'; ?>;

            function logDebug(message, data) {
                if (debugMode) {
                    if (data) {
                        console.log(message, data);
                    } else {
                        console.log(message);
                    }
                }
            }

            function formatDateDMY(dateStr) {
                if (!dateStr) return 'Nieznana';

                try {
                    // Konwersja do obiektu Date
                    const date = new Date(dateStr);

                    // Sprawdzenie czy data jest poprawna
                    if (isNaN(date.getTime())) {
                        return dateStr || 'Nieznana';
                    }

                    // Formatowanie daty DD-MM-YYYY
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();

                    return `${day}-${month}-${year}`;
                } catch (e) {
                    console.error('Błąd formatowania daty:', e);
                    return dateStr || 'Nieznana';
                }
            }

            function formatDateDMYHM(dateStr) {
                if (!dateStr) return 'Nieznana';

                try {
                    // Konwersja do obiektu Date
                    const date = new Date(dateStr);

                    // Sprawdzenie czy data jest poprawna
                    if (isNaN(date.getTime())) {
                        return dateStr || 'Nieznana';
                    }

                    // Formatowanie daty DD-MM-YYYY HH:MM
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');

                    return `${day}-${month}-${year} ${hours}:${minutes}`;
                } catch (e) {
                    console.error('Błąd formatowania daty:', e);
                    return dateStr || 'Nieznana';
                }
            }

            function incrementDownloads(url) {
                jQuery.post(ajaxurl, {
                    action: 'increment_downloads',
                    url: url
                }, function(response) {
                    if (response.success) {
                        const countCell = document.querySelector('#pdfDetails #liczba_pobran');
                        if (countCell) countCell.textContent = response.data.count;
                    }
                });
            }

            // Dodaj zmienną globalną z opcją rozszerzonego wykrywania
            var enableExtendedDetection = <?php echo isset($options['enable_extended_detection']) && $options['enable_extended_detection'] ? 'true' : 'false'; ?>;

            // Zmodyfikuj kod wykrywania linków PDF
            document.addEventListener('DOMContentLoaded', function() {
                const $ = jQuery;

                // Funkcja sprawdzająca czy link to PDF
                function isPdfLink(link) {
                    let isPdf = false;

                    // Sprawdź standardowe rozszerzenie .pdf
                    if (link.href.toLowerCase().endsWith('.pdf')) {
                        isPdf = true;
                    }

                    // Jeśli już wiemy, że to PDF lub rozszerzone wykrywanie jest wyłączone, nie sprawdzaj dalej
                    if (isPdf || !enableExtendedDetection) {
                        return isPdf;
                    }

                    // Sprawdź atrybut download
                    const downloadAttr = link.getAttribute('download');
                    if (downloadAttr && downloadAttr.toLowerCase().endsWith('.pdf')) {
                        isPdf = true;
                    }

                    // Sprawdź atrybut type
                    if (!isPdf) {
                        const typeAttr = link.getAttribute('type');
                        if (typeAttr && (
                                typeAttr === 'application/pdf' ||
                                typeAttr === 'pdf' ||
                                typeAttr.toLowerCase().includes('pdf')
                            )) {
                            isPdf = true;
                        }
                    }

                    // Sprawdź klasę
                    if (!isPdf) {
                        if (link.classList.contains('pdf') ||
                            link.classList.contains('pdf-file') ||
                            link.classList.contains('download-pdf')) {
                            isPdf = true;
                        }
                    }

                    // Sprawdź tekst linku - tylko jeśli jeszcze nie zidentyfikowaliśmy jako PDF
                    if (!isPdf) {
                        const linkText = link.textContent.toLowerCase();
                        if (linkText.includes('pdf') ||
                            linkText.includes('pobierz dokument') ||
                            linkText.includes('download document')) {
                            logDebug('Wykryto PDF przez kontekst tekstu:', linkText);
                            isPdf = true;
                        }
                    }

                    return isPdf;
                }

                // Zmień sposób wybierania linków PDF
                const allLinks = document.querySelectorAll('a');
                const pdfLinks = Array.from(allLinks).filter(isPdfLink);

                logDebug('Wykryto linki PDF:', pdfLinks.length);

                pdfLinks.forEach(link => {

                    // Sprawdź czy link jest w wykluczonym elemencie
                    let isExcluded = false;
                    let isInDocumentDownload = false;

                    // Sprawdź czy link jest w elemencie o klasie mn-document-download
                    if ($(link).closest('.mn-document-download').length > 0) {
                        isInDocumentDownload = true;
                        // Nie wykluczamy, tylko obsługujemy specjalnie
                    }

                    // Sprawdź wykluczenia z opcji
                    if (!isInDocumentDownload && !isExcluded) {
                        // Sprawdź czy link jest w header lub footer
                        const $link = $(link);
                        const isInHeader = $link.closest('header, .header, #header').length > 0;
                        const isInFooter = $link.closest('footer, .footer, #footer').length > 0;

                        if (isInHeader || isInFooter) {
                            logDebug('Link wykluczony - znajduje się w header/footer:', link.textContent);
                            isExcluded = true;
                        }

                        // Sprawdź wykluczenia elementów
                        if (!isExcluded && excludedElements && excludedElements.length > 0) {
                            for (let i = 0; i < excludedElements.length; i++) {
                                const element = excludedElements[i].trim();
                                if (element && $link.closest(element).length > 0) {
                                    logDebug('Link wykluczony przez element:', element, link.textContent);
                                    isExcluded = true;
                                    break;
                                }
                            }
                        }

                        // Sprawdź wykluczenia klas
                        if (!isExcluded && excludedClasses && excludedClasses.length > 0) {
                            for (let i = 0; i < excludedClasses.length; i++) {
                                const className = excludedClasses[i].trim();
                                if (className) {
                                    // Sprawdź czy link ma wykluczoną klasę lub znajduje się w elemencie z wykluczoną klasą
                                    if ($link.hasClass(className) || $link.closest('.' + className).length > 0) {
                                        logDebug('Link wykluczony przez klasę:', className, link.textContent);
                                        isExcluded = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    // Obsługa elementów mn-document-download
                    if (isInDocumentDownload) {
                        const $downloadContainer = $(link).closest('.mn-document-download');
                        const documentUrl = link.getAttribute('href');
                        const documentName = $downloadContainer.find('.mn-document-name').text().trim();

                        // Pobierz URL bazowy strony
                        const baseUrl = window.location.origin;

                        // Normalizuj URL - upewnij się, że zaczyna się od "/"
                        let normalizedUrl = documentUrl;
                        if (!normalizedUrl.startsWith('/') && !normalizedUrl.startsWith('http')) {
                            normalizedUrl = '/' + normalizedUrl;
                        }

                        // Pełny URL do wykorzystania w AJAX
                        let fullUrl = normalizedUrl;

                        // Jeśli URL zaczyna się od "/" (ścieżka względna), dodaj bazowy URL
                        if (normalizedUrl.startsWith('/')) {
                            fullUrl = baseUrl + normalizedUrl;
                        }

                        // Sprawdź czy URL kończy się na .pdf lub prowadzi do załącznika PDF bez rozszerzenia
                        let isPdf = normalizedUrl.toLowerCase().endsWith('.pdf');

                        // Jeśli link nie kończy się na .pdf, zakładamy że to potencjalny PDF bez rozszerzenia
                        if (!isPdf) {
                            isPdf = true;
                        }

                        if (isPdf) {
                            // Dodaj przycisk "Metryczka" przed linkiem "Pobierz"
                            const metrykaButton = document.createElement('a');
                            metrykaButton.href = 'javascript:void(0)';
                            metrykaButton.className = 'mn-document-metryczka';
                            metrykaButton.textContent = 'Metryczka';
                            metrykaButton.style.marginRight = '10px';
                            metrykaButton.style.cursor = 'pointer';
                            metrykaButton.style.color = '#007bff';
                            metrykaButton.style.textDecoration = 'underline';

                            // Wstaw przycisk przed linkiem "Pobierz"
                            link.parentNode.insertBefore(metrykaButton, link);

                            // Dodaj separator
                            const separator = document.createTextNode(' | ');
                            link.parentNode.insertBefore(separator, link);

                            // Stwórz ukryty kontener na tabelę z metadanymi
                            const metadataContainer = document.createElement('div');
                            metadataContainer.className = 'pdf-metadata-container';
                            metadataContainer.style.display = 'none';
                            metadataContainer.style.width = '100%';

                            // Dodaj kontener na końcu .mn-document-download
                            $downloadContainer.append(metadataContainer);

                            // Funkcja do płynnego pokazywania/ukrywania tabelki z użyciem jQuery
                            function toggleMetadataContainer($container, show) {
                                if (show) {
                                    $container.css({
                                        display: 'block',
                                        overflow: 'hidden',
                                        maxHeight: '0',
                                        opacity: '0',
                                        paddingTop: '0',
                                        paddingBottom: '0',
                                        borderWidth: '1px'
                                    }).animate({
                                        maxHeight: '100%',
                                        opacity: 1,
                                        paddingTop: '10px',
                                        paddingBottom: '10px'
                                    }, 0, 'swing', function() {
                                        // Callback po zakończeniu animacji
                                        $container.css('overflow', 'visible');
                                    });
                                } else {
                                    $container.css('overflow', 'hidden').animate({
                                        maxHeight: '0',
                                        opacity: 0,
                                        paddingTop: '0',
                                        paddingBottom: '0'
                                    }, 0, 'swing', function() {
                                        // Callback po zakończeniu animacji
                                        $container.css('display', 'none');
                                    });
                                }
                            }

                            // Dodaj obsługę kliknięcia przycisku "Metryczka"
                            metrykaButton.addEventListener('click', function() {
                                const $metadataContainer = $(metadataContainer);

                                // Jeśli kontener jest już widoczny, ukryj go
                                if (metadataContainer.style.display === 'block') {
                                    toggleMetadataContainer($metadataContainer, false);
                                    return;
                                }

                                // Pokaż kontener z komunikatem ładowania
                                metadataContainer.innerHTML = '<div class="loading">Ładowanie metadanych...</div>';
                                toggleMetadataContainer($metadataContainer, true);

                                // Loguj informacje o URL dla celów debugowania
                                logDebug('URL dokumentu:', {
                                    original: documentUrl,
                                    normalized: normalizedUrl,
                                    full: fullUrl,
                                    name: documentName
                                });

                                // Pobierz metadane przez AJAX
                                $.post(ajaxurl, {
                                    action: 'get_pdf_data',
                                    url: fullUrl, // Używamy pełnego URL
                                    title: documentName, // Używamy nazwy z .mn-document-name
                                    is_document_download: 'true' // Flaga wskazująca, że to element .mn-document-download
                                }, function(data) {
                                    if (!data) {
                                        metadataContainer.innerHTML = `
                                            <div style="padding: 10px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 10px;">
                                                <strong>Uwaga:</strong> Nie znaleziono metadanych dla tego dokumentu.
                                            </div>
                                        `;
                                        return;
                                    }
                                    let metadataHTML = `
                                        <table class="mn-metadata-table" style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Wytworzył:</td>
                                                <td style="padding: 5px; border: 1px solid #ddd;">${data.autor || 'Brak danych'}</td>
                                                <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Data wytworzenia:</td>
                                                <td style="padding: 5px; border: 1px solid #ddd;">${formatDateDMY(data.data_wytworzenia) || 'Brak danych'}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Opublikowano przez:</td>
                                                <td style="padding: 5px; border: 1px solid #ddd;">${data.publikator || 'Brak danych'}</td>
                                                <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Data publikacji:</td>
                                                <td style="padding: 5px; border: 1px solid #ddd;">${formatDateDMYHM(data.data_publikacji) || 'Brak danych'}</td>
                                            </tr>`;
                                    if (data.zaktualizowal && data.data_aktualizacji) {
                                        console.log('Zaktualizowano:', data.zaktualizowal, data.data_aktualizacji);
                                        metadataHTML += `
                                            <tr>
                                                <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Zaktualizował:</td>
                                                <td style="padding: 5px; border: 1px solid #ddd;">${data.zaktualizowal}</td>
                                                <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Data aktualizacji:</td>
                                                <td style="padding: 5px; border: 1px solid #ddd; white-space: nowrap;">${formatDateDMYHM(data.data_aktualizacji)}</td>
                                            </tr>`;
                                    }
                                    metadataHTML += `
                                        <tr>
                                            <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Liczba pobrań:</td>
                                            <td colspan="3" style="padding: 5px; border: 1px solid #ddd;">${data.liczba_pobran || '0'}</td>
                                        </tr>
                                    </table>`;

                                    metadataContainer.innerHTML = metadataHTML;
                                }).fail(function(jqXHR, textStatus, errorThrown) {
                                    console.error('Błąd AJAX:', textStatus, errorThrown);
                                    metadataContainer.innerHTML = `
                                        <div style="padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 10px;">
                                            <strong>Błąd:</strong> Nie udało się pobrać metadanych dokumentu.
                                            <p>Szczegóły: ${textStatus} - ${errorThrown || 'Nieznany błąd'}</p>
                                        </div>
                                    `;
                                });
                            });

                            // Dodaj obsługę kliknięcia linku "Pobierz"
                            link.addEventListener('click', function(e) {
                                // Inkrementuj licznik pobrań
                                $.post(ajaxurl, {
                                    action: 'increment_downloads',
                                    url: fullUrl,
                                    is_document_download: 'true'
                                }, function(response) {
                                    if (response.success) {
                                        // Znajdź komórkę z liczbą pobrań w bieżącym kontenerze metadanych, tu można zmienić wyszukiwanie na np. id
                                        const downloadsCell = metadataContainer.querySelector('td[colspan="3"]');
                                        if (downloadsCell && metadataContainer.style.display === 'block') {
                                            // Aktualizuj licznik pobrań
                                            downloadsCell.textContent = response.data.count;
                                        }
                                    }
                                });
                            });
                        }
                    } else if (!isExcluded) {
                        // Sprawdź rozmiar kontenera
                        const parentWidth = $(link).parent().width();
                        logDebug('Link width:', parentWidth, link.textContent);

                        if (parentWidth >= 600) {
                            // Szerokość >= 600px: Tekst "Metryczka" obok
                            const container = document.createElement('div');
                            container.className = 'pdf-container';
                            container.style.display = 'flex';
                            container.style.flexDirection = 'row';
                            container.style.flexWrap = 'wrap';
                            container.style.alignItems = 'flex-start';
                            container.width = '100%';

                            // Skopiuj treść oryginalnego linku
                            const linkClone = document.createElement('a');
                            linkClone.href = link.href;
                            linkClone.innerHTML = link.innerHTML;
                            linkClone.target = '_blank';
                            linkClone.className = 'pdf-link-text';
                            linkClone.style.marginRight = '10px';

                            // Dodaj przycisk metryczki
                            const metrykaButton = document.createElement('a');
                            metrykaButton.href = 'javascript:void(0)';
                            metrykaButton.className = 'pdf-metryczka-button';
                            metrykaButton.innerHTML = '<i class="fa-solid fa-info-circle" title="Metryczka"></i>';
                            metrykaButton.style.color = '#007bff';
                            metrykaButton.style.cursor = 'pointer';
                            metrykaButton.style.textDecoration = 'none';
                            metrykaButton.style.textAlignLast = 'right';
                            container.appendChild(linkClone);
                            container.appendChild(metrykaButton);
                            const metadataContainer = document.createElement('div');
                            metadataContainer.className = 'pdf-metadata-container';
                            metadataContainer.style.display = 'none';

                            metadataContainer.style.flexBasis = '100%';

                            // Dodaj kontener metadanych do głównego kontenera
                            container.appendChild(metadataContainer);
                            link.parentNode.replaceChild(container, link);

                            // Obsługa kliknięcia przycisku metryczki
                            metrykaButton.addEventListener('click', function() {
                                if (metadataContainer.style.display === 'block') {
                                    metadataContainer.style.display = 'none';
                                    return;
                                }

                                // Pokaż loader
                                metadataContainer.style.display = 'block';
                                metadataContainer.innerHTML = '<div class="loading">Ładowanie metadanych...</div>';

                                // Pobierz metadane
                                $.post(ajaxurl, {
                                    action: 'get_pdf_data',
                                    url: linkClone.href,
                                    title: linkClone.textContent.trim()
                                }, function(data) {
                                    if (!data) {
                                        metadataContainer.innerHTML = '<div class="error">Nie znaleziono metadanych</div>';
                                        return;
                                    }
                                    let metadataHTML = `
                                        <table class="mn-metadata-table" style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Wytworzył:</td>
                                                <td style="padding: 5px; border: 1px solid #ddd;">${data.autor || 'Brak danych'}</td>
                                                <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Data wytworzenia:</td>
                                                <td style="padding: 5px; border: 1px solid #ddd;">${formatDateDMY(data.data_wytworzenia) || 'Brak danych'}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Opublikowano przez:</td>
                                                <td style="padding: 5px; border: 1px solid #ddd;">${data.publikator || 'Brak danych'}</td>
                                                <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Data publikacji:</td>
                                                <td style="padding: 5px; border: 1px solid #ddd;">${formatDateDMYHM(data.data_publikacji) || 'Brak danych'}</td>
                                            </tr>`;
                                    if (data.zaktualizowal != '' && data.data_aktualizacji != '') {
                                        console.log('Zaktualizowano:', data.zaktualizowal, data.data_aktualizacji);
                                        metadataHTML += `
                                            <tr>
                                                <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Zaktualizował:</td>
                                                <td style="padding: 5px; border: 1px solid #ddd;">${data.zaktualizowal}</td>
                                                <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Data aktualizacji:</td>
                                                <td style="padding: 5px; border: 1px solid #ddd; white-space: nowrap;">${formatDateDMYHM(data.data_aktualizacji)}</td>
                                            </tr>`;
                                    }
                                    metadataHTML += `
                                        <tr>
                                            <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold;">Liczba pobrań:</td>
                                            <td colspan="3" style="padding: 5px; border: 1px solid #ddd;">${data.liczba_pobran || '0'}</td>
                                        </tr>
                                    </table>`;

                                    metadataContainer.innerHTML = metadataHTML;

                                }).fail(function() {
                                    metadataContainer.innerHTML = '<div class="error">Błąd podczas pobierania metadanych</div>';
                                });
                            });

                            // Obsługa kliknięcia linku
                            linkClone.addEventListener('click', function(e) {
                                incrementDownloads(linkClone.href);
                            });

                        } else if (parentWidth >= 300) {
                            // Szerokość 300-600px: Ikona obok (z modalem)
                            const container = document.createElement('div');
                            container.className = 'pdf-container';
                            container.style.display = 'flex';
                            container.style.alignItems = 'center';

                            // Skopiuj treść oryginalnego linku
                            const linkClone = document.createElement('a');
                            linkClone.href = link.href;
                            linkClone.innerHTML = link.innerHTML;
                            linkClone.target = '_blank';
                            linkClone.className = 'pdf-link-text';
                            linkClone.style.marginRight = '10px';

                            // Dodaj ikonę metryczki (z modalem)
                            const iconSpan = document.createElement('span');
                            iconSpan.className = 'pdf-icon-container';
                            iconSpan.style.cursor = 'pointer';
                            iconSpan.innerHTML = '<i class="fa-solid fa-info-circle" title="Metryczka"></i>';
                            iconSpan.style.color = '#007bff';
                            iconSpan.dataset.url = link.href;
                            iconSpan.dataset.title = link.textContent.trim();

                            // Dodaj elementy do kontenera
                            container.appendChild(linkClone);
                            container.appendChild(iconSpan);

                            // Zastąp oryginalny link nowym kontenerem
                            link.parentNode.replaceChild(container, link);

                            // Obsługa kliknięcia ikony
                            iconSpan.addEventListener('click', function(e) {
                                e.stopPropagation();
                                const url = this.dataset.url;
                                const title = this.dataset.title;

                                $.post(ajaxurl, {
                                    action: 'get_pdf_data',
                                    url: url,
                                    title: title
                                }, function(data) {
                                    $('#pdfTitle').html(`<a href="${url}" target="_blank" onclick="incrementDownloads('${url}')">${title}</a>`);
                                    $('#pdfDetails').html(() => {
                                        let tableHTML = `
                                            <table class="table table-bordered table-hover">
                                                <tr>
                                                    <td>Wytworzył:</td>
                                                    <td>${data.autor}</td>
                                                    <td>Data wytworzenia:</td>
                                                    <td>${formatDateDMY(data.data_wytworzenia)}</td>
                                                </tr>
                                                <tr>
                                                    <td>Opublikowano przez:</td>
                                                    <td>${data.publikator}</td>
                                                    <td>Data publikacji:</td>
                                                    <td>${formatDateDMYHM(data.data_publikacji)}</td>
                                                </t>`;
                                        if (data.zaktualizowal && data.data_aktualizacji) {
                                            tableHTML += `
                                                <tr>
                                                    <td>Zaktualizował:</td>
                                                    <td>${data.zaktualizowal}</td>
                                                    <td>Data aktualizacji:</td>
                                                    <td>${formatDateDMYHM(data.data_aktualizacji)}</td>
                                                </tr>`;
                                        }

                                        tableHTML += `
                                                <tr>
                                                    <td>Liczba pobrań:</td>
                                                    <td id="liczba_pobran" colspan="3">${data.liczba_pobran || '0'}</td>
                                                </tr>
                                            </table>`;
                                        return tableHTML;
                                    });
                                    $('#pdfModal').modal('show');
                                });
                            });

                            // Obsługa kliknięcia linku
                            linkClone.addEventListener('click', function(e) {
                                incrementDownloads(linkClone.href);
                            });

                        } else {
                            // Szerokość < 300px: Ikona pod linkiem (z modalem)
                            const container = document.createElement('div');
                            container.className = 'pdf-container';
                            container.style.display = 'flex';
                            container.style.flexDirection = 'column';
                            container.style.alignItems = 'flex-start';

                            // Skopiuj treść oryginalnego linku
                            const linkClone = document.createElement('a');
                            linkClone.href = link.href;
                            linkClone.innerHTML = link.innerHTML;
                            linkClone.target = '_blank';
                            linkClone.className = 'pdf-link-text';
                            linkClone.style.marginBottom = '5px';

                            // Dodaj ikonę metryczki (z modalem)
                            const iconSpan = document.createElement('span');
                            iconSpan.className = 'pdf-icon-container';
                            iconSpan.style.cursor = 'pointer';
                            iconSpan.style.alignSelf = 'center';
                            iconSpan.innerHTML = '<i class="fa-solid fa-info-circle" title="Metryczka"></i>';
                            iconSpan.style.color = '#007bff';
                            iconSpan.dataset.url = link.href;
                            iconSpan.dataset.title = link.textContent.trim();
                            container.appendChild(linkClone);
                            container.appendChild(iconSpan);
                            link.parentNode.replaceChild(container, link);

                            // Obsługa kliknięcia ikony
                            iconSpan.addEventListener('click', function(e) {
                                e.stopPropagation();
                                const url = this.dataset.url;
                                const title = this.dataset.title;

                                $.post(ajaxurl, {
                                    action: 'get_pdf_data',
                                    url: url,
                                    title: title
                                }, function(data) {
                                    $('#pdfTitle').html(`<a href="${url}" target="_blank" onclick="incrementDownloads('${url}')">${title}</a>`);
                                    $('#pdfDetails').html(() => {
                                        let tableHTML = `
                                            <table class="table table-bordered table-hover">
                                                <tr>
                                                    <td>Wytworzył:</td>
                                                    <td>${data.autor}</td>
                                                    <td>Data wytworzenia:</td>
                                                    <td>${formatDateDMY(data.data_wytworzenia)}</td>
                                                </tr>
                                                <tr>
                                                    <td>Opublikowano przez:</td>
                                                    <td>${data.publikator}</td>
                                                    <td>Data publikacji:</td>
                                                    <td>${formatDateDMYHM(data.data_publikacji)}</td>
                                                </t>`;
                                        if (data.zaktualizowal && data.data_aktualizacji) {
                                            tableHTML += `
                                                <tr>
                                                    <td>Zaktualizował:</td>
                                                    <td>${data.zaktualizowal}</td>
                                                    <td>Data aktualizacji:</td>
                                                    <td>${formatDateDMYHM(data.data_aktualizacji)}</td>
                                                </tr>`;
                                        }

                                        tableHTML += `
                                                <tr>
                                                    <td>Liczba pobrań:</td>
                                                    <td id="liczba_pobran" colspan="3">${data.liczba_pobran || '0'}</td>
                                                </tr>
                                            </table>`;
                                        return tableHTML;
                                    });
                                    $('#pdfModal').modal('show');
                                });
                            });

                            // Obsługa kliknięcia linku
                            linkClone.addEventListener('click', function(e) {
                                incrementDownloads(linkClone.href);
                            });
                        }
                    }
                });

                // Obsługa kliknięcia na ikonę
                $(document).on('click', '.fa-info-circle', function(e) {
                    e.stopPropagation();
                    const url = this.parentNode.dataset.url;
                    const title = this.parentNode.querySelector('.pdf-link-text').textContent;

                    $.post(ajaxurl, {
                        action: 'get_pdf_data',
                        url: url,
                        title: title
                    }, function(data) {
                        $('#pdfTitle').html(`<a href="${url}" target="_blank" onclick="incrementDownloads('${url}')">${title}</a>`);
                        $('#pdfDetails').html(() => {
                            let tableHTML = `
                                            <table class="table table-bordered table-hover">
                                                <tr>
                                                    <td>Wytworzył:</td>
                                                    <td>${data.autor}</td>
                                                    <td>Data wytworzenia:</td>
                                                    <td>${formatDateDMY(data.data_wytworzenia)}</td>
                                                </tr>
                                                <tr>
                                                    <td>Opublikowano przez:</td>
                                                    <td>${data.publikator}</td>
                                                    <td>Data publikacji:</td>
                                                    <td>${formatDateDMYHM(data.data_publikacji)}</td>
                                                </t>`;
                            if (data.zaktualizowal && data.data_aktualizacji) {
                                tableHTML += `
                                                <tr>
                                                    <td>Zaktualizował:</td>
                                                    <td>${data.zaktualizowal}</td>
                                                    <td>Data aktualizacji:</td>
                                                    <td>${formatDateDMYHM(data.data_aktualizacji)}</td>
                                                </tr>`;
                            }

                            tableHTML += `
                                                <tr>
                                                    <td>Liczba pobrań:</td>
                                                    <td id="liczba_pobran" colspan="3">${data.liczba_pobran || '0'}</td>
                                                </tr>
                                            </table>`;
                            return tableHTML;
                        });
                        $('#pdfModal').modal('show');
                    });
                });

                // Obsługa kliknięcia na link PDF
                $(document).on('click', '.pdf-link', function(e) {
                    if (!$(e.target).hasClass('fa-info-circle')) {
                        window.open(this.dataset.url, '_blank');
                        incrementDownloads(this.dataset.url);
                    }
                });

                // Obsługa zamknięcia modalu
                $('.close-modal').on('click', function() {
                    $('#pdfModal').modal('hide');
                });
            });
        </script>

        <div class="modal fade" id="pdfModal" style="vertical-align: middle; align-content: center; ">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pdfTitle" style="font-size: 1rem;"></h5>
                    </div>
                    <div class="modal-body" id="pdfDetails"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Zamknij</button>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }, 99);
}

function get_pdf_data()
{
    $url = esc_url_raw($_POST['url']);
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $url = pdf_metryczka_normalize_url($url);

    // Znajdź attachment_id na podstawie URL
    $attachment_id = attachment_url_to_postid($url);

    if (!$attachment_id) {
        // Spróbuj znaleźć po nazwie pliku
        $slug = basename(parse_url($url, PHP_URL_PATH));
        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_wp_attached_file',
                    'value' => $slug,
                    'compare' => 'LIKE'
                )
            )
        );

        $attachments = get_posts($args);

        if (!empty($attachments)) {
            $attachment_id = $attachments[0]->ID;
        } else {
            // Jeśli nadal nie znaleziono, utwórz tymczasowy obiekt danych
            $virtual_data = array(
                'autor' => 'Nieznany',
                'data_wytworzenia' => current_time('mysql'),
                'data_publikacji' => current_time('mysql'),
                'publikator' => 'System',
                'liczba_pobran' => 0,
                'nazwa_wyswietlana' => $title,
                'nazwa_pliku' => $slug,
                'url' => $url,
                'zaktualizowal' => '',
                'data_aktualizacji' => ''
            );

            wp_send_json($virtual_data);
            return;
        }
    }

    // Pobierz obiekt załącznika bezpośrednio z bazy danych, aby uzyskać dostęp do niestandardowej kolumny
    global $wpdb;
    $attachment_post = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $wpdb->posts WHERE ID = %d",
        $attachment_id
    ));

    if (!$attachment_post) {
        wp_send_json_error('Nie znaleziono załącznika');
        return;
    }

    // Pobierz metadane
    $data = array(
        'autor' => get_post_meta($attachment_id, 'wytworzyl', true) ?: 'Nieznany',
        'data_wytworzenia' => get_post_meta($attachment_id, 'data_wytworzenia', true) ?: current_time('mysql'),
        'data_publikacji' => get_the_date('Y-m-d H:i:s', $attachment_id),
        'publikator' => get_the_author_meta('display_name', $attachment_post->post_author),
        'zaktualizowal' => isset($attachment_post->zaktualizowal) ? $attachment_post->zaktualizowal : '',
        'data_aktualizacji' => $attachment_post->post_modified ? date_i18n('d-m-Y H:i', strtotime($attachment_post->post_modified)) : '',
        'liczba_pobran' => intval(get_post_meta($attachment_id, 'pdf_liczba_pobran', true)),
        'nazwa_wyswietlana' => get_the_title($attachment_id),
        'nazwa_pliku' => basename(get_attached_file($attachment_id)),
        'url' => $url,
        'attachment_id' => $attachment_id
    );

    wp_send_json($data);
}

function increment_downloads()
{
    $url = esc_url_raw($_POST['url']);
    $url = pdf_metryczka_normalize_url($url);

    // Znajdź attachment_id na podstawie URL
    $attachment_id = attachment_url_to_postid($url);
    // Jak nie to po nazwie pliku
    if (!$attachment_id) {
        $slug = basename(parse_url($url, PHP_URL_PATH));
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'application/pdf',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_wp_attached_file',
                    'value' => $slug,
                    'compare' => 'LIKE'
                )
            )
        );

        $attachments = get_posts($args);

        if (!empty($attachments)) {
            $attachment_id = $attachments[0]->ID;
        } else {
            wp_send_json_error('Nie znaleziono załącznika');
            return;
        }
    }

    $count = intval(get_post_meta($attachment_id, 'pdf_liczba_pobran', true));
    $count++;
    update_post_meta($attachment_id, 'pdf_liczba_pobran', $count);

    wp_send_json_success(['count' => $count]);
}

add_action('wp_footer', 'check_pdf_links');
add_action('wp_ajax_get_pdf_data', 'get_pdf_data');
add_action('wp_ajax_nopriv_get_pdf_data', 'get_pdf_data');
add_action('wp_ajax_increment_downloads', 'increment_downloads');
add_action('wp_ajax_nopriv_increment_downloads', 'increment_downloads');

// Dodanie panelu administracyjnego
function pdf_metryczka_menu()
{
    add_options_page(
        'Ustawienia Metryczki PDF',
        'Metryczki PDF',
        'manage_options',
        'pdf-metryczka-settings',
        'pdf_metryczka_settings_page'
    );
}
add_action('admin_menu', 'pdf_metryczka_menu');

// Rejestracja ustawień
function pdf_metryczka_register_settings()
{
    register_setting('pdf_metryczka_options_group', 'pdf_metryczka_options', 'pdf_metryczka_sanitize_options');
}
add_action('admin_init', 'pdf_metryczka_register_settings');

// Strona ustawień
function pdf_metryczka_settings_page()
{
    $options = get_option('pdf_metryczka_options');
    if (!$options) {
        $options = pdf_metryczka_default_options();
        update_option('pdf_metryczka_options', $options);
    }

    // Domyślne wartości dla nowych pól
    if (!isset($options['button_css'])) {
        $options['button_css'] = ".pdf-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .pdf-link-text {
            color: blue;
            text-decoration: underline;
        }

        .fa-info-circle {
            color: #007bff;
            cursor: pointer;
            margin-left: 5px;
            text-decoration: none;
        }

        .fa-info-circle:hover {
            color: #0056b3;
        }";
    }

    if (!isset($options['modal_css'])) {
        $options['modal_css'] = "
        .modal-content {
            border-radius: 4px;
        }

        #pdfTitle {
            font-weight: bold;
        }

        #pdfDetails table {
            width: 100%;
            margin-bottom: 0;
        }

        #pdfDetails td {
            padding: 8px;
        }";
    }

    if (!isset($options['table_css'])) {
        $options['table_css'] = "
        .mn-document-metadata, .pdf-metadata-container {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .mn-metadata-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .mn-metadata-table td {
            padding: 5px;
            border: 1px solid #ddd;
        }
        
        .mn-metadata-table tr td:nth-child(odd) {
            font-weight: bold;
        }
        
        @media (max-width: 600px) {
            .mn-document-metadata, .pdf-metadata-container {
                font-size: 14px;
                padding: 5px;
            }
            
            .mn-document-metadata table.mn-metadata-table td,
            .pdf-metadata-container table.mn-metadata-table td {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
        }";
    }

    // Przykładowy pdf
    $sample_pdf_url = plugin_dir_url(__FILE__) . 'sample.pdf';
    $sample_pdf_title = 'Przykładowy dokument PDF';

    // Dodanie skryptów i stylów
    wp_enqueue_style('bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap-bundle', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/js/bootstrap.bundle.min.js', array('jquery'), null, true);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
    ?>
    <div class="wrap">
        <h1>Ustawienia Metryczki PDF</h1>

        <form method="post" action="options.php">
            <?php settings_fields('pdf_metryczka_options_group'); ?>

            <div class="nav-tab-wrapper">
                <a href="#general-settings" class="nav-tab nav-tab-active">Ustawienia ogólne</a>
                <a href="#style-settings" class="nav-tab">Style i wygląd</a>
                <a href="#stats" class="nav-tab">Statystyki</a>
            </div>

            <!-- Zakładka Ustawienia ogólne -->
            <div id="general-settings" class="tab-content" style="display:block;">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Automatyczne wykrywanie PDF</th>
                        <td>
                            <label>
                                <input type="checkbox" name="pdf_metryczka_options[enable_auto_detection]" value="1" <?php checked(1, $options['enable_auto_detection']); ?> />
                                Włącz automatyczne wykrywanie linków PDF na stronach
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Rozszerzone wykrywanie PDF</th>
                        <td>
                            <label>
                                <input type="checkbox" name="pdf_metryczka_options[enable_extended_detection]" value="1" <?php checked(1, $options['enable_extended_detection'] ?? 0); ?> />
                                Wykrywaj pliki PDF również po atrybutach download, type oraz kontekście
                            </label>
                            <p class="description">Włącz tę opcję, aby wykrywać pliki PDF również wtedy, gdy link nie kończy się na .pdf</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Wyświetlanie ikony metryczki</th>
                        <td>
                            <label>
                                <input type="checkbox" name="pdf_metryczka_options[display_icon]" value="1" <?php checked(1, $options['display_icon']); ?> />
                                Pokaż ikonę metryczki obok linków PDF
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Prefix URL</th>
                        <td>
                            <input type="text" name="pdf_metryczka_options[url_prefix]" class="regular-text" value="<?php echo esc_attr($options['url_prefix']); ?>" />
                            <p class="description">Prefix dodawany do względnych adresów URL (np. zaczynających się od "/"). Przydatne w środowiskach, gdzie ścieżki bezwzględne wymagają dodatkowego prefiksu.</p>
                        </td>
                    </tr>
                </table>

                <h3>Wykluczenia</h3>
                <p class="description">Określ elementy i klasy, które mają być wykluczone z dodawania metryczek. Oddziel wiele wartości przecinkami.</p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Wykluczone elementy HTML</th>
                        <td>
                            <input type="text" name="pdf_metryczka_options[excluded_elements]" class="large-text" value="<?php echo esc_attr($options['excluded_elements'] ?? ''); ?>" />
                            <p class="description">Przykład: div, span, article (elementy HTML)</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Wykluczone klasy CSS</th>
                        <td>
                            <input type="text" name="pdf_metryczka_options[excluded_classes]" class="large-text" value="<?php echo esc_attr($options['excluded_classes'] ?? ''); ?>" />
                            <p class="description">Przykład: no-metryczka, custom-pdf-link (klasy CSS bez kropek)</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Zakładka Style i wygląd - z dodanymi przyciskami resetowania -->
            <div id="style-settings" class="tab-content" style="display:none;">

                <!-- Nowy układ z tabelą dla CSS i podglądu -->
                <table class="widefat" style="margin-top: 20px;">
                    <tr>
                        <td>
                            <textarea name="pdf_metryczka_options[button_css]" id="button_css" rows="8" cols="50" class="large-text code"><?php echo esc_textarea($options['button_css']); ?></textarea>
                            <button type="button" class="button reset-css" data-target="button_css" data-type="textarea" style="width: 100%">Resetuj</button>
                            <p class="description">CSS do dostosowania wyglądu przycisku metryczki</p>
                        </td>
                        <td colspan="2" style="display: flexbox;">
                            <div id="button-preview" style="flex-basis: 100%;">
                                <span class="pdf-link" style="cursor:pointer; font-size: 1rem !important;" data-url="<?php echo $sample_pdf_url; ?>">
                                    <span class="pdf-link-text"><?php echo $sample_pdf_title; ?></span>
                                    <i class="fa-solid fa-info-circle"></i>
                                </span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <textarea name="pdf_metryczka_options[modal_css]" id="modal_css" rows="8" cols="50" class="large-text code"><?php echo esc_textarea($options['modal_css']); ?></textarea>
                            <button type="button" class="button reset-css" data-target="modal_css" data-type="textarea" style="width: 100%">Resetuj</button>
                            <p class="description">CSS do dostosowania wyglądu okna modalnego metryczki</p>
                        </td>
                        <td colspan="2">
                            <div id="modal-preview" style="border: 1px solid #ddd; border-radius: 5px; overflow: hidden; margin-bottom: 20px;">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="pdfTitle" style="font-size: 1rem;">
                                            <a href="<?php echo $sample_pdf_url; ?>" target="_blank"><?php echo $sample_pdf_title; ?></a>
                                        </h5>
                                    </div>
                                    <div class="modal-body" id="pdfDetails">
                                        <table class="table table-bordered table-hover">
                                            <tr>
                                                <td>Wytworzył:</td>
                                                <td>Jan Kowalski</td>
                                            </tr>
                                            <tr>
                                                <td>Data wytworzenia:</td>
                                                <td>01-01-2023</td>
                                            </tr>
                                            <tr>
                                                <td>Opublikowano przez:</td>
                                                <td>Administrator</td>
                                            </tr>
                                            <tr>
                                                <td>Data publikacji:</td>
                                                <td>05-01-2023</td>
                                            </tr>
                                            <tr>
                                                <td>Zaktualizował:</td>
                                                <td>Administrator</td>
                                            </tr>
                                            <tr>
                                                <td>Data aktualizacji:</td>
                                                <td>15-01-2023</td>
                                            </tr>
                                            <tr>
                                                <td>Liczba pobrań:</td>
                                                <td id="liczba_pobran" colspan="3">42</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary close-modal">Zamknij</button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <textarea name="pdf_metryczka_options[table_css]" id="table_css" rows="8" cols="50" class="large-text code"><?php echo esc_textarea($options['table_css']); ?></textarea>
                            <button type="button" class="button reset-css" data-target="table_css" data-type="textarea" style="width: 100%">Resetuj</button>
                            <p class="description">CSS do dostosowania wyglądu rozwijanej tabelki z metadanymi</p>
                        </td>

                        <td colspan="2">
                            <div id="table-preview">
                                <div class="mn-document-download">
                                    <div class="mn-document-metadata" style="display: block;">
                                        <table class="mn-metadata-table">
                                            <tr>
                                                <td>Wytworzył:</td>
                                                <td>Jan Kowalski</td>
                                                <td>Data wytworzenia:</td>
                                                <td>01-01-2023</td>
                                            </tr>
                                            <tr>
                                                <td>Opublikowano przez:</td>
                                                <td>Administrator</td>
                                                <td>Data publikacji:</td>
                                                <td>05-01-2023</td>
                                            </tr>
                                            <tr>
                                                <td>Zaktualizował:</td>
                                                <td>Administrator</td>
                                                <td>Data aktualizacji:</td>
                                                <td>15-01-2023</td>
                                            </tr>
                                            <tr>
                                                <td>Liczba pobrań:</td>
                                                <td id="liczba_pobran" colspan="3">42</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Zakładka Statystyki -->
            <div id="stats" class="tab-content" style="display:none; margin-top: 20px;">
                <?php
                // Pobierz statystyki z metadanych
                $args = array(
                    'post_type' => 'attachment',
                    'post_mime_type' => 'application/pdf',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => 'pdf_liczba_pobran',
                            'compare' => 'EXISTS'
                        )
                    )
                );

                $pdf_attachments = get_posts($args);
                $count = count($pdf_attachments);

                // Sortuj po liczbie pobrań
                usort($pdf_attachments, function ($a, $b) {
                    $a_count = intval(get_post_meta($a->ID, 'pdf_liczba_pobran', true));
                    $b_count = intval(get_post_meta($b->ID, 'pdf_liczba_pobran', true));
                    return $b_count - $a_count;
                });

                // Ogranicz do 5 najczęściej pobieranych
                $top_downloads = array_slice($pdf_attachments, 0, 5);
                ?>
                <p>Liczba zindeksowanych plików PDF: <strong><?php echo intval($count); ?></strong></p>

                <?php if (!empty($top_downloads)): ?>
                    <h3>Najczęściej pobierane pliki:</h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Nazwa</th>
                                <th>URL</th>
                                <th>Pobrania</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_downloads as $file):
                                $url = wp_get_attachment_url($file->ID);
                                $downloads = intval(get_post_meta($file->ID, 'pdf_liczba_pobran', true));
                            ?>
                                <tr>
                                    <td><?php echo esc_html($file->post_title); ?></td>
                                    <td><a href="<?php echo esc_url($url); ?>" target="_blank"><?php echo esc_url($url); ?></a></td>
                                    <td><?php echo intval($downloads); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <p class="submit">
                <input type="submit" class="button-primary" value="Zapisz zmiany" />
            </p>
        </form>

        <style id="live-button-css"></style>
        <style id="live-modal-css"></style>
        <style id="live-table-css"></style>

        <script>
            jQuery(document).ready(function($) {
                // Obsługa zakładek
                $('.nav-tab').on('click', function(e) {
                    e.preventDefault();
                    var target = $(this).attr('href');

                    // Ukryj wszystkie zakładki i pokaż docelową
                    $('.tab-content').hide();
                    $(target).show();

                    // Zmień aktywną zakładkę
                    $('.nav-tab').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                });

                // Domyślne wartości CSS
                var defaultCSS = <?php echo json_encode(pdf_metryczka_default_options()); ?>;

                // "Resetuj"
                $('.reset-css').on('click', function() {
                    var target = $(this).data('target');
                    var type = $(this).data('type') || 'input';

                    if (type === 'textarea') {
                        // textarea
                        $('#' + target).val(defaultCSS[target]);
                    } else {
                        var defaultValue = $(this).data('default') || defaultCSS[target];
                        $('#' + target).val(defaultValue);
                    }

                    updateLivePreview();
                });

                // Funkcja aktualizująca podgląd na żywo
                function updateLivePreview() {
                    var buttonCSS = $('#button_css').val();
                    var modalCSS = $('#modal_css').val();
                    var tableCSS = $('#table_css').val();

                    $('#live-button-css').html(buttonCSS);
                    $('#live-modal-css').html(modalCSS);
                    $('#live-table-css').html(tableCSS);
                }

                $('#button_css, #modal_css, #table_css').on('input change', updateLivePreview);

                updateLivePreview();
            });
        </script>
    </div>
<?php
}
// Aktualizacja funkcji sanityzującej opcje
function pdf_metryczka_sanitize_options($input)
{
    $output = array();

    $output['enable_auto_detection'] = isset($input['enable_auto_detection']) ? 1 : 0;
    $output['enable_extended_detection'] = isset($input['enable_extended_detection']) ? 1 : 0; // Dodajemy tę linię
    $output['display_icon'] = isset($input['display_icon']) ? 1 : 0;
    $output['button_css'] = wp_strip_all_tags($input['button_css']);
    $output['modal_css'] = wp_strip_all_tags($input['modal_css']);
    $output['table_css'] = wp_strip_all_tags($input['table_css']);
    $output['excluded_elements'] = sanitize_text_field($input['excluded_elements']);
    $output['excluded_classes'] = sanitize_text_field($input['excluded_classes']);
    $output['url_prefix'] = esc_url_raw($input['url_prefix']);

    return $output;
}

// Aktualizacja funkcji domyślnych opcji
function pdf_metryczka_default_options()
{
    return array(
        'enable_auto_detection' => 1,
        'enable_extended_detection' => 1,
        'display_icon' => 1,
        'button_css' => ".pdf-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.pdf-link-text {
    color: blue;
    text-decoration: underline;
}

.fa-info-circle {
    color: #007bff;
    cursor: pointer;
    margin-left: 5px;
    text-decoration: none;
}

.fa-info-circle:hover {
    color: #0056b3;
}",
        'modal_css' => ".modal-content {
    border-radius: 4px;
}

#pdfTitle {
    font-weight: bold;
}

#pdfDetails table {
    width: 100%;
}

#pdfDetails table tr td:nth-child(odd) {
    font-weight: bold;
}

#pdfDetails table tr td:nth-child(even) {
    white-space: nowrap;
}

#pdfDetails td {
    padding: 8px;
}

@media (max-width: 600px) {
    #pdfDetails {
        font-size: 14px;
        padding: 5px;
    }
            
    #pdfDetails td {
        display: block;
        width: 100%;
        box-sizing: border-box;
    }
}",
        'table_css' => ".mn-document-metadata, .pdf-metadata-container {
    margin-top: 10px;
    padding: 10px;
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.mn-metadata-table {
    width: 100%;
    border-collapse: collapse;
}

.mn-metadata-table td {
    padding: 5px;
    border: 1px solid #ddd;
}

.mn-metadata-table tr td:nth-child(odd) {
    font-weight: bold;
}

.mn-metadata-table tr td:nth-child(even) {
    white-space: nowrap;
}

@media (max-width: 600px) {
    .mn-document-metadata, .pdf-metadata-container {
        font-size: 14px;
        padding: 5px;
    }
            
    .mn-document-metadata table.mn-metadata-table td,
    .pdf-metadata-container table.mn-metadata-table td {
        display: block;
        width: 100%;
        box-sizing: border-box;
    }
}",
        'excluded_elements' => '',
        'excluded_classes' => 'mn-document-download',
        'url_prefix' => 'https://bip.polsl.pl'
    );
}


// Aktualizuj opcje przy każdym załadowaniu strony admina
function pdf_metryczka_check_options()
{
    if (is_admin()) {
        $options = get_option('pdf_metryczka_options');
        $defaults = pdf_metryczka_default_options();

        $needs_update = false;

        // Sprawdź czy mamy wszystkie wymagane pola
        foreach ($defaults as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
                $needs_update = true;
            }
        }

        if ($needs_update) {
            update_option('pdf_metryczka_options', $options);
        }
    }
}
add_action('admin_init', 'pdf_metryczka_check_options', 5);

// Dodanie pól w interfejsie edycji załącznika
function pdf_metryczka_attachment_fields($form_fields, $post)
{
    // Sprawdź czy to PDF
    if ($post->post_mime_type === 'application/pdf') {
        // Wytworzył i data wytworzenia
        // Można dodać do zawnsowanej edycji, ale nie jest to konieczne
        $autor = get_post_meta($post->ID, 'wytworzyl', true);
        $data_wytworzenia = get_post_meta($post->ID, 'data_wytworzenia', true);

        $formatted_date = '';
        if ($data_wytworzenia) {
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $data_wytworzenia, $matches)) {
                $formatted_date = $matches[1];
            } else {
                $formatted_date = $data_wytworzenia;
            }
        }

        // Pole Autor
        $form_fields['wytworzyl'] = array(
            'label' => 'Wytworzył',
            'input' => 'text',
            'value' => $autor,
            'helps' => 'Autor dokumentu'
        );

        // Pole Data stworzenia
        $form_fields['data_wytworzenia'] = array(
            'label' => 'Data wytworzenia',
            'input' => 'html',
            'html'  => "<input type='text' name='attachments[{$post->ID}][data_wytworzenia]' id='attachments-{$post->ID}-data_wytworzenia' value='{$formatted_date}' pattern='\\d{4}-\\d{2}-\\d{2}' />",
            'value' => $formatted_date,
            'helps' => 'RRRR-MM-DD'
        );
    }

    return $form_fields;
}
add_filter('attachment_fields_to_edit', 'pdf_metryczka_attachment_fields', 10, 2);

function add_pdf_date_formatting_script()
{
?>
    <script type="text/javascript">
        jQuery(document).on("keypress", "input[name*='data_wytworzenia']", function(e) {
            var charCode = (typeof e.which == "number") ? e.which : e.keyCode;
            if (charCode && (charCode < 48 || charCode > 57)) {
                e.preventDefault();
            }
        });

        jQuery(document).on("input", "input[name*='data_wytworzenia']", function() {
            var digits = jQuery(this).val().replace(/\D/g, ''); // Usunięcie wszystkiego poza cyframi
            var formatted = '';

            if (digits.length > 4) {
                let year = digits.substring(0, 4);
                let month = digits.substring(4, 6);
                let day = digits.substring(6, 8);

                // Walidacja miesiąca 
                if (parseInt(month) > 12) {
                    month = '12';
                } else if (parseInt(month) < 1 && month.length === 2) {
                    month = '01';
                }

                // Walidacja dnia
                if (parseInt(day) > 31) {
                    day = '31';
                } else if (parseInt(day) < 1 && day.length === 2) {
                    day = '01';
                }

                formatted = year + '-' + month;
                if (digits.length > 6) {
                    formatted += '-' + day;
                }
            } else {
                formatted = digits;
            }

            jQuery(this).val(formatted);
        });
    </script>
<?php
}
add_action('admin_footer', 'add_pdf_date_formatting_script');

// Zapisywanie pól załącznika
function pdf_metryczka_attachment_fields_save($post, $attachment)
{
    // Sprawdź czy to PDF
    if ($post['post_mime_type'] === 'application/pdf') {
        // Pobierz wartości pól
        $autor = isset($attachment['wytworzyl']) ? sanitize_text_field($attachment['wytworzyl']) : '';
        $data_wytworzenia = isset($attachment['data_wytworzenia']) ? sanitize_text_field($attachment['data_wytworzenia']) : '';

        // Użyj funkcji pomocniczej do formatowania daty
        $data_wytworzenia = pdf_metryczka_format_date($data_wytworzenia);

        // Aktualizuj metadane
        update_post_meta($post['ID'], 'wytworzyl', $autor);
        update_post_meta($post['ID'], 'data_wytworzenia', $data_wytworzenia);

        // Inicjalizuj licznik pobrań, jeśli nie istnieje
        if (!get_post_meta($post['ID'], 'pdf_liczba_pobran', true)) {
            update_post_meta($post['ID'], 'pdf_liczba_pobran', 0);
        }
    }

    return $post;
}
add_filter('attachment_fields_to_save', 'pdf_metryczka_attachment_fields_save', 10, 2);

// Funkcja do usuwania danych przy odinstalowaniu
function pdf_metryczka_uninstall()
{
    // Usuń tylko opcje wtyczki, zachowaj metadane
    delete_option('pdf_metryczka_options');
}
register_uninstall_hook(__FILE__, 'pdf_metryczka_uninstall');
