<?php

// Aktualizacja funkcji domyślnych opcji
function pdf_metryczka_default_options()
{
    return array(
        'enable_auto_detection' => 1,
        'enable_extended_detection' => 1,
        'display_icon' => 1,
        'button_css' => ".pdf-container {
    display: inline-flex !important;
    align-items: center !important;
}

.pdf-link-text {
    color: blue !important;
    text-decoration: underline !important;
}

.fa-info-circle {
    color: #007bff !important;
    cursor: pointer !important;
    text-decoration: none !important;
}

.fa-info-circle:hover {
    color: #0056b3 !important;
}",
        'modal_css' => ".modal-content {
    border: 2px solid #003c7d !important;
    border-radius: 10px 40px 10px !important;
    margin: auto !important;
}

.modal-content>*{
   padding: 10px !important;
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
    font-weight: bold !important;
}

#pdfDetails table {
    width: 100% !important;
    margin-bottom: 0 !important;
}

#pdfDetails table tr td:nth-child(even) {
    font-weight: bold !important;
}

#pdfDetails table tr:nth-child(odd) {
    background-color: #f2f2f2 !important;
}

#pdfDetails table td {
    border-top: 1px solid #003c7d !important; 
    padding: 4px 6px !important;
    font-size: 13px !important;
}

#pdfDetails table tr:last-child {
    border-bottom: 1px solid #003c7d !important; 
}

#pdfDetails td {
    padding: 8px !important;
    border: none !important;
}

#pdfDetails table td:nth-child(4n) {
    white-space: nowrap !important;
}

@media (max-width: 420px) {
    #pdfDetails table td:nth-child(4n) {
        white-space: normal !important;
    }
}

@media (max-width: 600px) {
    #pdfDetails {
        font-size: 14px !important;
        padding: 5px !important;
    }

    #pdfDetails table td:nth-child(3n) {
        display: none !important; 
        /* do przeanalizowania */
    }
}",
        'table_css' => ".mn-document-metadata, .pdf-metadata-container {
    margin-top: 10px !important;
    padding: 10px !important;
    background-color: #f8f9fa !important;
    border: 2px solid #003c7d !important;
    border-radius: 0px 0px 10px !important;
    transition: all 0.3s ease !important;
}

.mn-metadata-table {
    width: 100% !important;
    border-collapse: collapse !important;
}

.mn-metadata-table td {
    padding: 5px !important;
    border-top: 1px solid #003c7d !important; 
}

.mn-metadata-table tr td:nth-child(even) {
    font-weight: bold !important;
}

.mn-metadata-table tr:nth-child(odd) {
    background-color: #f2f2f2 !important;
}

.mn-metadata-table tr:last-child {
    border-bottom: 1px solid #003c7d !important; 
}

@media (max-width: 600px) {
    .mn-document-metadata, .pdf-metadata-container {
        font-size: 14px !important;
        padding: 5px !important;
    }

    .mn-metadata-table tr:nth-child(odd) {
        background-color: white !important;
    }

    .mn-metadata-table td:nth-child(odd) {
        background-color: #f2f2f2 !important;
    }

    .mn-document-metadata table.mn-metadata-table td,
    .pdf-metadata-container table.mn-metadata-table td {
        display: block !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
}",
        'excluded_elements' => '',
        'excluded_classes' => 'mn-document-download',
        'url_prefix' => 'https://bip.polsl.pl'
    );
}

?>