<?php

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

?>