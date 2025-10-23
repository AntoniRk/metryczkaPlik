<?php

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
        $prefix = isset($options['url_prefix']) ? $options['url_prefix'] : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $prefix = rtrim($prefix, '/');
        return $prefix . $url;
    }

    return $url;
}

// Aktualizacja funkcji sanityzującej opcje
function pdf_metryczka_sanitize_options($input)
{
    $output = array();

    $output['enable_auto_detection'] = isset($input['enable_auto_detection']) ? 1 : 0;
    $output['enable_mobile'] = isset($input['enable_mobile']) ? 1 : 0;
    $output['enable_extended_detection'] = isset($input['enable_extended_detection']) ? 1 : 0; // Dodajemy tę linię
    $output['display_icon'] = isset($input['display_icon']) ? 1 : 0;
    $output['button_css'] = wp_strip_all_tags($input['button_css']);
    $output['modal_css'] = wp_strip_all_tags($input['modal_css']);
    $output['table_css'] = wp_strip_all_tags($input['table_css']);
    $output['excluded_elements'] = sanitize_text_field($input['excluded_elements']);
    $output['excluded_classes'] = sanitize_text_field($input['excluded_classes']);
    $output['url_prefix'] = !empty($input['url_prefix']) ? esc_url_raw($input['url_prefix']) : '';
    return $output;
}

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

?>