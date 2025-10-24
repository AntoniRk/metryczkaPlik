<?php

/*
Plugin Name: Metryczki dla załączników
Description: Automatycznie wykrywa linki do <strong>załączników</strong> i dodaje przycisk do wyświetlania ich <strong>metryczek</strong>. Nowe pola do wprowadzenia danych przy dodawaniu plików i edycji. 
Version: 1.0
Author: Antoni Roskosz
*/

// Dodaj stałą lokalizację wtyczki
define('LOKALIZACJA_WTYCZKI', plugin_dir_path(__FILE__));

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
    if (wp_is_mobile() && !$options['enable_mobile']) {
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
        $url_prefix = isset($options['url_prefix']) ? $options['url_prefix'] : '';
?>
        <style>
            <?php echo $button_css; ?><?php if (!$display_icon): ?>.fa-info-circle {
                display: none;
            }

            <?php endif; ?><?php echo $modal_css; ?><?php echo $table_css; ?>
        </style>
        <script>
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var urlPrefix = '<?php echo esc_js($url_prefix); ?>';
            if (urlPrefix == '') {
                urlPrefix = '<?php echo ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']); ?>';
            }
            // Normalizuj urlPrefix - usuń protokół i trailing slash
            function normalizePrefix(prefix) {
                if (!prefix) return '';

                // Usuń białe znaki
                prefix = prefix.trim();

                // Usuń protokół (http://, https://)
                prefix = prefix.replace(/^https?:\/\//, '');

                // Usuń trailing slash
                prefix = prefix.replace(/\/$/, '');
                prefix = prefix.toLowerCase();

                return prefix;
            }

            var normalizedUrlPrefix = normalizePrefix(urlPrefix);
            var excludedElements =
                <?php
                $elements = array_filter(array_map('trim', explode(',', $excluded_elements)));
                echo json_encode($elements);
                ?>;

            var excludedClasses =
                <?php
                echo json_encode(array_filter(array_map('trim', explode(',', $excluded_classes))));
                ?>;

            var debugMode = <?php echo isset($options['debug_mode']) && $options['debug_mode'] ? 'true' : 'false'; ?>;

            // Funkcja pomocnicza do wyświetlania modala z danymi
            function displayModalWithData(url, title, data) {
                jQuery('#pdfTitle').html(`<a href="${url}" target="_blank" onclick="incrementDownloads('${url}')">${title}</a>`);
                let tableHTML = `
                    <table class="table">`;
                if (data.autor && data.data_wytworzenia) {
                    tableHTML += `
                        <tr>
                            <td>Wytworzył:</td>
                            <td>${data.autor}</td>
                            <td>Data wytworzenia:</td>
                            <td>${formatDateDMY(data.data_wytworzenia)}</td>
                        </tr>`;
                }
                tableHTML += `
                    <tr>
                        <td>Opublikowano przez:</td>
                        <td>${data.publikator}</td>
                        <td>Data publikacji:</td>
                        <td>${formatDateDMYHM(data.data_publikacji)}</td>
                    </tr>`;
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

                jQuery('#pdfDetails').html(tableHTML);
                jQuery('#pdfModal').modal('show');
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

                    // Formatowanie daty DD-MM-RRRR
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();

                    return `${day}-${month}-${year}`;
                } catch (e) {
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

                    // Formatowanie daty DD-MM-RRRR GG:MM
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');

                    return `${day}-${month}-${year} ${hours}:${minutes}`;
                } catch (e) {
                    return dateStr || 'Nieznana';
                }
            }

            function incrementDownloads(url) {
                jQuery.post(ajaxurl, {
                    action: 'increment_downloads',
                    url: url
                }, function(response) {
                    if (response.success) {
                        // Aktualizuj licznik w modalnym oknie
                        const countCell = document.querySelector('#pdfModal #liczba_pobran');
                        if (countCell) countCell.textContent = response.data.count;

                        // Aktualizuj licznik we wszystkich kontenerach metadanych na stronie
                        const allMetadataContainers = document.querySelectorAll('.pdf-metadata-container');
                        allMetadataContainers.forEach(container => {
                            // Sprawdź, czy ten kontener metadanych jest dla tego samego URL
                            const relatedLink = container.closest('.pdf-container')?.querySelector('a.pdf-link-text');
                            if (relatedLink && relatedLink.href === url) {
                                // Znajdź komórkę z liczbą pobrań
                                const downloadsCell = container.querySelector('td[colspan="3"]');
                                if (downloadsCell) {
                                    downloadsCell.textContent = response.data.count;
                                }
                            }
                        });

                        // Aktualizuj licznik w kontenerach mn-document-download
                        const documentContainers = document.querySelectorAll('.mn-document-download');
                        documentContainers.forEach(container => {
                            const downloadLink = container.querySelector('a[href="' + url + '"]');
                            if (downloadLink) {
                                const metadataTable = container.querySelector('.mn-metadata-table');
                                if (metadataTable) {
                                    const downloadsCell = metadataTable.querySelector('td[colspan="3"]');
                                    if (downloadsCell) {
                                        downloadsCell.textContent = response.data.count;
                                    }
                                }
                            }
                        });
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                const $ = jQuery;

                function isFileLink(link) {
                    const href = link.href.toLowerCase();

                    // tutaj mocne uproszczenie, zakładam, że każdy plik bez adresu przed /wp-content/uploads/ jest wewnętrzny
                    if (href.includes('/wp-content/uploads/')) {
                        const uploadIndex = href.indexOf('/wp-content/uploads/');
                        if (uploadIndex > 0) {
                            const beforeUpload = href.substring(0, uploadIndex);
                            // Normalizuj obie wartości do porównania
                            const normalizedBeforeUpload = normalizePrefix(beforeUpload);
                            if (normalizedUrlPrefix && !normalizedBeforeUpload.includes(normalizedUrlPrefix)) {
                                return false;
                            }
                        }
                        return true;
                    }
                }

                // Zmień sposób wybierania linków PDF
                const allLinks = document.querySelectorAll('a');
                const pdfLinks = Array.from(allLinks).filter(isFileLink);

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

                        // Sprawdź wykluczenia elementów
                        if (!isExcluded && excludedElements && excludedElements.length > 0) {
                            for (let i = 0; i < excludedElements.length; i++) {
                                const element = excludedElements[i].trim();
                                if (element && $link.parents(element).length > 0) {
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
                                    if ($link.hasClass(className) || $link.parents('.' + className).length > 0) {
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

                        // Normalizuj URL
                        let normalizedUrl = documentUrl;
                        if (!normalizedUrl.startsWith('/') && !normalizedUrl.startsWith('http')) {
                            normalizedUrl = '/' + normalizedUrl;
                        }

                        // Pełny URL do wykorzystania w AJAX
                        let fullUrl = normalizedUrl;
                        if (normalizedUrl.startsWith('/')) {
                            fullUrl = baseUrl + normalizedUrl;
                        }

                        // Zawsze traktuj linki w mn-document-download jako pliki
                        let isFile = true;

                        // Dodaj przycisk "Metryczka" przed linkiem "Pobierz"
                        const metrykaButton = document.createElement('a');
                        metrykaButton.href = 'javascript:void(0)';
                        metrykaButton.className = 'mn-document-metryczka';
                        metrykaButton.textContent = 'Metryczka';

                        // Wstaw przycisk przed linkiem "Pobierz"
                        link.parentNode.insertBefore(metrykaButton, link);

                        // Stwórz ukryty kontener na tabelę z metadanymi
                        const metadataContainer = document.createElement('div');
                        metadataContainer.className = 'pdf-metadata-container';
                        metadataContainer.style.display = 'none';

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

                            // Zabezpieczenie przed wielokrotnym kliknięciem
                            if (metadataContainer.dataset.loading === 'true') {
                                return;
                            }

                            // Jeśli już zweryfikowane i załadowane - tylko toggle
                            if (metadataContainer.dataset.verified === 'true') {
                                toggleMetadataContainer($metadataContainer, metadataContainer.style.display !== 'block');
                                return;
                            }

                            // Pierwsze kliknięcie - sprawdź i załaduj
                            metadataContainer.dataset.loading = 'true';
                            metadataContainer.innerHTML = '<div class="loading">Ładowanie metadanych...</div>';
                            toggleMetadataContainer($metadataContainer, true);

                            // Pobierz metadane przez AJAX
                            $.post(ajaxurl, {
                                action: 'get_pdf_data',
                                url: fullUrl,
                                title: documentName,
                                is_document_download: 'true'
                            }, function(data) {
                                metadataContainer.dataset.loading = 'false';

                                if (!data || !data.attachment_id) {
                                    // Nie jest plikiem WordPress
                                    metadataContainer.innerHTML = '<div class="error">Ten link nie prowadzi do pliku w systemie.</div>';
                                    setTimeout(function() {
                                        metrykaButton.style.display = 'none';
                                        toggleMetadataContainer($metadataContainer, false);
                                        setTimeout(() => metadataContainer.remove(), 300);
                                    }, 2000);
                                    return;
                                }

                                // Jest plikiem - pokaż metryczki
                                let metadataHTML = `
                                    <table class="mn-metadata-table">`;
                                if (data.autor && data.data_wytworzenia) {
                                    metadataHTML += `
                                        <tr>
                                            <td>Wytworzył:</td>
                                            <td>${data.autor}</td>
                                            <td>Data wytworzenia:</td>
                                            <td>${formatDateDMY(data.data_wytworzenia)}</td>
                                        </tr>`;
                                }
                                metadataHTML += `
                                    <tr>
                                        <td>Opublikowano przez:</td>
                                        <td>${data.publikator}</td>
                                        <td>Data publikacji:</td>
                                        <td>${formatDateDMYHM(data.data_publikacji)}</td>
                                    </tr>`;
                                if (data.zaktualizowal && data.data_aktualizacji) {
                                    metadataHTML += `
                                        <tr>
                                            <td>Zaktualizował:</td>
                                            <td>${data.zaktualizowal}</td>
                                            <td>Data aktualizacji:</td>
                                            <td>${formatDateDMYHM(data.data_aktualizacji)}</td>
                                        </tr>`;
                                }
                                metadataHTML += `
                                    <tr>
                                        <td>Liczba pobrań:</td>
                                        <td colspan="3">${data.liczba_pobran || '0'}</td>
                                    </tr>
                                </table>`;

                                metadataContainer.innerHTML = metadataHTML;
                                metadataContainer.dataset.verified = 'true';

                            }).fail(function(jqXHR, textStatus, errorThrown) {
                                metadataContainer.dataset.loading = 'false';
                                metadataContainer.innerHTML = '<div class="error">Błąd podczas pobierania metadanych</div>';
                            });
                        });

                        // Dodaj obsługę kliknięcia linku "Pobierz"
                        link.addEventListener('click', function(e) {
                            incrementDownloads(link.href);
                        });

                    } else if (!isExcluded) {
                        // Sprawdź rozmiar kontenera
                        const parentWidth = $(link).parent().width();

                        if (parentWidth >= 400) {
                            const container = document.createElement('div');
                            container.className = 'pdf-container';

                            // Skopiuj treść oryginalnego linku
                            const linkClone = document.createElement('a');
                            linkClone.href = link.href;
                            linkClone.innerHTML = link.innerHTML;
                            linkClone.target = '_blank';
                            linkClone.className = 'pdf-link-text';

                            // Dodaj przycisk metryczki
                            const metrykaButton = document.createElement('a');
                            metrykaButton.href = 'javascript:void(0)';
                            metrykaButton.className = 'pdf-metryczka-button';
                            metrykaButton.innerHTML = '<i class="fa-solid fa-info-circle" title="Metryczka"></i>';
                            container.appendChild(linkClone);
                            container.appendChild(metrykaButton);
                            const metadataContainer = document.createElement('div');
                            metadataContainer.className = 'pdf-metadata-container';
                            metadataContainer.style.display = 'none';

                            // Dodaj kontener metadanych do głównego kontenera
                            container.appendChild(metadataContainer);
                            link.parentNode.replaceChild(container, link);

                            // Obsługa kliknięcia przycisku metryczki
                            metrykaButton.addEventListener('click', function() {
                                const $metadataContainer = $(metadataContainer);

                                // Zabezpieczenie przed wielokrotnym kliknięciem
                                if (metadataContainer.dataset.loading === 'true') {
                                    return;
                                }

                                // Jeśli już zweryfikowane i załadowane - tylko toggle
                                if (metadataContainer.dataset.verified === 'true') {
                                    if (metadataContainer.style.display === 'block') {
                                        metadataContainer.style.display = 'none';
                                    } else {
                                        metadataContainer.style.display = 'block';
                                    }
                                    return;
                                }

                                // Pierwsze kliknięcie - sprawdź i załaduj
                                metadataContainer.dataset.loading = 'true';
                                metadataContainer.style.display = 'block';
                                metadataContainer.innerHTML = '<div class="loading">Ładowanie metadanych...</div>';

                                // Pobierz metadane i zweryfikuj czy to plik
                                $.post(ajaxurl, {
                                    action: 'get_pdf_data',
                                    url: linkClone.href,
                                    title: linkClone.textContent.trim()
                                }, function(data) {
                                    metadataContainer.dataset.loading = 'false';

                                    if (!data || !data.attachment_id) {
                                        // Nie jest plikiem WordPress - ukryj przycisk
                                        metadataContainer.innerHTML = '<div class="error">Ten link nie prowadzi do pliku w systemie.</div>';
                                        setTimeout(function() {
                                            metrykaButton.style.display = 'none';
                                            metadataContainer.remove();
                                        }, 2000);
                                        return;
                                    }

                                    // Jest plikiem - pokaż metryczki
                                    let metadataHTML = `
                                        <table class="mn-metadata-table">`;
                                    if (data.autor && data.data_wytworzenia) {
                                        metadataHTML += `
                                            <tr>
                                                <td>Wytworzył:</td>
                                                <td>${data.autor}</td>
                                                <td>Data wytworzenia:</td>
                                                <td>${formatDateDMY(data.data_wytworzenia)}</td>
                                            </tr>`;
                                    }
                                    metadataHTML += `
                                        <tr>
                                            <td>Opublikowano przez:</td>
                                            <td>${data.publikator}</td>
                                            <td>Data publikacji:</td>
                                            <td>${formatDateDMYHM(data.data_publikacji)}</td>
                                        </tr>`;
                                    if (data.zaktualizowal && data.data_aktualizacji) {
                                        metadataHTML += `
                                            <tr>
                                                <td>Zaktualizował:</td>
                                                <td>${data.zaktualizowal}</td>
                                                <td>Data aktualizacji:</td>
                                                <td>${formatDateDMYHM(data.data_aktualizacji)}</td>
                                            </tr>`;
                                    }
                                    metadataHTML += `
                                            <tr>
                                                <td>Liczba pobrań:</td>
                                                <td colspan="3">${data.liczba_pobran || '0'}</td>
                                            </tr>
                                        </table>`;

                                    metadataContainer.innerHTML = metadataHTML;
                                    metadataContainer.dataset.verified = 'true';

                                }).fail(function() {
                                    metadataContainer.dataset.loading = 'false';
                                    metadataContainer.innerHTML = '<div class="error">Błąd podczas pobierania metadanych</div>';
                                    setTimeout(function() {
                                        metrykaButton.style.display = 'none';
                                        metadataContainer.remove();
                                    }, 2000);
                                });
                            });

                            // Obsługa kliknięcia linku
                            linkClone.addEventListener('click', function(e) {
                                incrementDownloads(linkClone.href);
                            });

                        } else if (parentWidth >= 300) {
                            // Szerokość 300-400px: Ikona obok (z modalem)
                            const container = document.createElement('div');
                            container.className = 'pdf-container';

                            // Skopiuj treść oryginalnego linku
                            const linkClone = document.createElement('a');
                            linkClone.href = link.href;
                            linkClone.innerHTML = link.innerHTML;
                            linkClone.target = '_blank';
                            linkClone.className = 'pdf-link-text';

                            // Dodaj ikonę metryczki (z modalem)
                            const iconSpan = document.createElement('span');
                            iconSpan.className = 'pdf-icon-container';
                            iconSpan.innerHTML = '<i class="fa-solid fa-info-circle" title="Metryczka"></i>';
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

                                // Zabezpieczenie przed wielokrotnym kliknięciem
                                if (this.dataset.loading === 'true') {
                                    return;
                                }

                                const url = this.dataset.url;
                                const title = this.dataset.title;

                                // Sprawdź czy dane są już załadowane
                                if (this.dataset.verified === 'true' && this.dataset.cachedData) {
                                    const data = JSON.parse(this.dataset.cachedData);
                                    displayModalWithData(url, title, data);
                                    return;
                                }

                                this.dataset.loading = 'true';
                                $('#pdfDetails').html('<div class="loading">Ładowanie metadanych...</div>');
                                $('#pdfTitle').html(title);

                                const self = this;
                                $.post(ajaxurl, {
                                    action: 'get_pdf_data',
                                    url: url,
                                    title: title
                                }, function(data) {
                                    self.dataset.loading = 'false';

                                    if (!data || !data.attachment_id) {
                                        $('#pdfModal').modal('hide');
                                        iconSpan.style.display = 'none';
                                        return;
                                    }

                                    // Zapisz dane do cache
                                    self.dataset.verified = 'true';
                                    self.dataset.cachedData = JSON.stringify(data);

                                    displayModalWithData(url, title, data);
                                }).fail(function() {
                                    self.dataset.loading = 'false';
                                    $('#pdfModal').modal('hide');
                                    iconSpan.style.display = 'none';
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
                            container.style.flexDirection = 'column !important';

                            // Skopiuj treść oryginalnego linku
                            const linkClone = document.createElement('a');
                            linkClone.href = link.href;
                            linkClone.innerHTML = link.innerHTML;
                            linkClone.target = '_blank';
                            linkClone.className = 'pdf-link-text';
                            linkClone.style.marginBottom = '5px';

                            // Dodaj ikonę metryczki (z modalem)
                            const iconSpan = document.createElement('span');
                            iconSpan.className = 'pdf-icon-container pdf-icon-container-pod';
                            iconSpan.innerHTML = '<i class="fa-solid fa-info-circle" title="Metryczka"></i>';
                            iconSpan.dataset.url = link.href;
                            iconSpan.dataset.title = link.textContent.trim();
                            container.appendChild(linkClone);
                            container.appendChild(iconSpan);
                            link.parentNode.replaceChild(container, link);

                            // Obsługa kliknięcia ikony
                            iconSpan.addEventListener('click', function(e) {
                                e.stopPropagation();

                                // Zabezpieczenie przed wielokrotnym kliknięciem
                                if (this.dataset.loading === 'true') {
                                    return;
                                }

                                const url = this.dataset.url;
                                const title = this.dataset.title;

                                // Sprawdź czy dane są już załadowane
                                if (this.dataset.verified === 'true' && this.dataset.cachedData) {
                                    const data = JSON.parse(this.dataset.cachedData);
                                    displayModalWithData(url, title, data);
                                    return;
                                }

                                this.dataset.loading = 'true';
                                $('#pdfDetails').html('<div class="loading">Ładowanie metadanych...</div>');
                                $('#pdfTitle').html(title);

                                const self = this;
                                $.post(ajaxurl, {
                                    action: 'get_pdf_data',
                                    url: url,
                                    title: title
                                }, function(data) {
                                    self.dataset.loading = 'false';

                                    if (!data || !data.attachment_id) {
                                        $('#pdfModal').modal('hide');
                                        iconSpan.style.display = 'none';
                                        return;
                                    }

                                    // Zapisz dane do cache
                                    self.dataset.verified = 'true';
                                    self.dataset.cachedData = JSON.stringify(data);

                                    displayModalWithData(url, title, data);
                                }).fail(function() {
                                    self.dataset.loading = 'false';
                                    $('#pdfModal').modal('hide');
                                    iconSpan.style.display = 'none';
                                });
                            });

                            // Obsługa kliknięcia linku
                            linkClone.addEventListener('click', function(e) {
                                incrementDownloads(linkClone.href);
                            });
                        }
                    }
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
                        <h5 class="modal-title" id="pdfTitle"></h5>
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
            // NIE ZNALEZIONO - zwróć info że to nie jest plik WP
            wp_send_json(array('attachment_id' => null));
            return;
        }
    }

    // Pobierz obiekt załącznika
    global $wpdb;
    $attachment_post = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $wpdb->posts WHERE ID = %d",
        $attachment_id
    ));

    if (!$attachment_post) {
        wp_send_json(array('attachment_id' => null));
        return;
    }

    // Pobierz metadane
    $data = array(
        'autor' => get_post_meta($attachment_id, 'wytworzyl', true) ?: '',
        'data_wytworzenia' => get_post_meta($attachment_id, 'data_wytworzenia', true) ?: '',
        'data_publikacji' => get_the_date('Y-m-d H:i:s', $attachment_id),
        'publikator' => get_the_author_meta('display_name', $attachment_post->post_author),
        'zaktualizowal' => isset($attachment_post->zaktualizowal) ? $attachment_post->zaktualizowal : '',
        'data_aktualizacji' => isset($attachment_post->post_modified) ? $attachment_post->post_modified : '',
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
