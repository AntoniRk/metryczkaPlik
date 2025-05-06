<?php

// Dodanie panelu administracyjnego
function pdf_metryczka_menu()
{
    add_options_page(
        'Ustawienia Metryczek załączników',
        'Metryczki załączników',
        'manage_options',
        'pdf-metryczka-settings',
        'pdf_metryczka_settings_page'
    );
}
add_action('admin_menu', 'pdf_metryczka_menu');

function pdf_metryczka_add_help_tab()
{
    $screen = get_current_screen();

    if ($screen->id != 'settings_page_pdf-metryczka-settings') {
        return;
    }

    $screen->add_help_tab(array(
        'id'      => 'pdf_metryczka_help_url_prefix',
        'title'   => 'Prefix URL',
        'content' => '<p><strong>Prefix URL</strong> - Ta opcja pozwala określić prefix, który będzie dodawany do względnych adresów URL plików (np. zaczynających się od "/"). Jest to szczególnie przydatne w środowiskach, gdzie ścieżki względne wymagają dodatkowego prefiksu, aby być poprawnie rozpoznawane przez system WordPress.</p>
                     <p>Domyślna wartość to <code>https://bip.polsl.pl</code>, ale możesz ją zmienić zgodnie z konfiguracją swojej witryny.</p>
                     <p>Przykład: Jeśli masz link do pliku <code>/wp-content/uploads/2023/01/dokument.pdf</code>, wtyczka automatycznie przekształci go na <code>https://bip.polsl.pl/wp-content/uploads/2023/01/dokument.pdf</code> podczas próby identyfikacji załącznika.</p>'
    ));
}
add_action('admin_head', 'pdf_metryczka_add_help_tab');

// Inicjalizacja opcji przy aktywacji
register_activation_hook(__FILE__, function () {
    add_option('pdf_metryczka_options', pdf_metryczka_default_options());
});

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
        <h1>Ustawienia Metryczek załączników</h1>

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
                        <th scope="row">Automatyczne wykrywanie załączników</th>
                        <td>
                            <label>
                                <input type="checkbox" name="pdf_metryczka_options[enable_auto_detection]" value="1" <?php checked(1, $options['enable_auto_detection']); ?> />
                                Włącz automatyczne wykrywanie linków z załącznikami na stronach
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Rozszerzone wykrywanie załączników</th>
                        <td>
                            <label>
                                <input type="checkbox" name="pdf_metryczka_options[enable_extended_detection]" value="1" <?php checked(1, $options['enable_extended_detection'] ?? 0); ?> />
                                Wykrywaj pliki również po atrybutach download, type oraz kontekście
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Wyświetlanie ikony metryczki</th>
                        <td>
                            <label>
                                <input type="checkbox" name="pdf_metryczka_options[display_icon]" value="1" <?php checked(1, $options['display_icon']); ?> />
                                Pokaż ikonę metryczki obok linków do plików
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
                        <td colspan="2">
                            <div class="pdf-container">
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
                            <div id="modal-preview">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="pdfTitle">
                                            <a href="<?php echo $sample_pdf_url; ?>" target="_blank"><?php echo $sample_pdf_title; ?></a>
                                        </h5>
                                    </div>
                                    <div class="modal-body" id="pdfDetails">
                                        <table class="table">
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
                                                <td>05-01-2023 19:30</td>
                                            </tr>
                                            <tr>
                                                <td>Zaktualizował:</td>
                                                <td>Administrator</td>
                                            </tr>
                                            <tr>
                                                <td>Data aktualizacji:</td>
                                                <td>15-01-2023 07:15</td>
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
                                    <div class="mn-document-metadata">
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
                                                <td>05-01-2023 19:30</td>
                                            </tr>
                                            <tr>
                                                <td>Zaktualizował:</td>
                                                <td>Administrator</td>
                                                <td>Data aktualizacji:</td>
                                                <td>15-01-2023 07:15</td>
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
                <p>Liczba zindeksowanych plików: <strong><?php echo intval($count); ?></strong></p>

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

?>