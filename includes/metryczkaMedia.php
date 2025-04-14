<?php

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

?>