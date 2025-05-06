<?php

// Aktualizacja funkcji domyślnych opcji
function pdf_metryczka_default_options()
{
    return array(
        'enable_auto_detection' => 1,
        'enable_extended_detection' => 1,
        'display_icon' => 1,
        'button_css' => ".pdf-container {
    display: inline-flex;
    align-items: center;
}

.pdf-link-text {
    color: blue;
    text-decoration: underline;
}

.fa-info-circle {
    color: #007bff;
    cursor: pointer;
    text-decoration: none;
}

.fa-info-circle:hover {
    color: #0056b3;
}",
        'modal_css' => ".modal-content {
    border: 2px solid #003c7d !important;
    border-radius: 0px 40px !important;
    margin: auto !important;
}

.modal-header,.modal-footer{
   border: none !important;
}

.modal-header{
   padding-bottom: 0 !important;
   margin-bottom: 0 !important;
}

.modal-footer{
   padding-top: 0 !important;
   margin-top: 0 !important;
}

#pdfTitle {
    font-weight: bold;
}

#pdfDetails table {
    width: 100%;
    margin-bottom: 0;
}

#pdfDetails table tr td:nth-child(even) {
    font-weight: bold;
}

#pdfDetails table tr:nth-child(odd) {
    background-color: #f2f2f2;
}

#pdfDetails table td {
    border-top: 1px solid #003c7d; 
    padding: 4px 6px;
    font-size: 13px;
}

#pdfDetails table tr:last-child {
    border-bottom: 1px solid #003c7d; 
}

#pdfDetails td {
    padding: 8px;
    border: none;
}

#pdfDetails table td:nth-child(4n) {
    white-space: nowrap !important;
}

@media (max-width: 600px) {
    #pdfDetails {
        font-size: 14px;
        padding: 5px;
    }

    #pdfDetails table td:nth-child(3n) {
        display: none !important; 
        /* do przeanalizowania */
    }
}",
        'table_css' => ".mn-document-metadata, .pdf-metadata-container {
    margin-top: 10px;
    padding: 10px;
    background-color: #f8f9fa;
    border: 2px solid #003c7d;
    border-radius: 0px 10px;
    transition: all 0.3s ease;
}

.mn-metadata-table {
    width: 100%;
    border-collapse: collapse;
}

.mn-metadata-table td {
    padding: 5px;
    border-top: 1px solid #003c7d; 
}

.mn-metadata-table tr td:nth-child(even) {
    font-weight: bold;
}

.mn-metadata-table tr:nth-child(odd) {
    background-color: #f2f2f2;
}

.mn-metadata-table tr:last-child {
    border-bottom: 1px solid #003c7d; 
}

@media (max-width: 600px) {
    .mn-document-metadata, .pdf-metadata-container {
        font-size: 14px;
        padding: 5px;
    }

    .mn-metadata-table tr:nth-child(odd) {
        background-color: white;
    }

    .mn-metadata-table td:nth-child(odd) {
        background-color: #f2f2f2;
    }
}",
        'excluded_elements' => '',
        'excluded_classes' => 'mn-document-download',
        'url_prefix' => 'https://bip.polsl.pl'
    );
}

?>