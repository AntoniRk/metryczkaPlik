<?php

/*
Plugin Name: Metryczki dla załączników
Description: Automatycznie wykrywa linki do <strong>załączników</strong> i dodaje przycisk do wyświetlania ich <strong>metryczek</strong>. Nowe dane przy dodawaniu plików i edycji. 
Version: 0.37
Author: Antoni Roskosz
*/

// Dodaj stałą lokalizację wtyczki
define( 'LOKALIZACJA_WTYCZKI', plugin_dir_path( __FILE__ ) );

// Załącz pozostałe pliki z funkcjami
require_once LOKALIZACJA_WTYCZKI . 'includes/metryczkaMedia.php';
require_once LOKALIZACJA_WTYCZKI . 'includes/metryczkaFormatowanie.php';
require_once LOKALIZACJA_WTYCZKI . 'includes/metryczkaWyglad.php';
require_once LOKALIZACJA_WTYCZKI . 'includes/metryczkaAdmin.php';

if (!defined('ABSPATH')) die();

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

// Funkcja do usuwania danych przy odinstalowaniu
function pdf_metryczka_uninstall()
{
    // Usuń tylko opcje wtyczki, zachowaj metadane
    delete_option('pdf_metryczka_options');
}
register_uninstall_hook(__FILE__, 'pdf_metryczka_uninstall');
