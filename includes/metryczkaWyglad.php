<?php

// Aktualizacja funkcji domyślnych opcji
function pdf_metryczka_default_options()
{
    return array(
        'enable_auto_detection' => 1,
        'enable_mobile' => 0,
        'enable_extended_detection' => 1,
        'display_icon' => 1,
        'button_css' => ".pdf-container {
    display: inline-flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    align-items: flex-start;
    width: 100% !important;
}

.pdf-link-text {
    color: #154C8D !important;
    text-decoration: underline !important;
    margin-right: 5px !important;
}

.pdf-icon-container{
    cursor: pointer !important;
}

.pdf-icon-container-pod{
    margin: auto !important;
}
    
.pdf-metryczka-button{
    color: #154C8D !important;
    cursor: pointer !important;
    text-decoration: none !important;
    text-align-last: right !important;
}

.fa-info-circle {
    color: #154C8D !important;
    cursor: pointer !important;
    text-decoration: none !important;
}

.fa-info-circle:hover {
    color: #007bff !important;
}",
        'modal_css' => ".modal-content {
    border: 2px solid #154C8D !important;
    border-radius: 10px 10px 10px !important;
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
    border-top: 1px solid #154C8D !important; 
    padding: 4px 6px !important;
    font-size: 13px !important;
}

#pdfDetails table tr:last-child {
    border-bottom: 1px solid #154C8D !important; 
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
    border: 2px solid #154C8D !important;
    border-radius: 0px 0px 10px !important;
    transition: all 0.3s ease !important;
    order: 999 !important;
    flex-basis: 100% !important;
    width: 100% !important;
}

.mn-metadata-table {
    width: 100% !important;
    border-collapse: collapse !important;
}

.mn-metadata-table td {
    padding: 5px !important;
    border-top: 1px solid #154C8D !important; 
}

.mn-metadata-table tr td:nth-child(even) {
    font-weight: bold !important;
}

.mn-metadata-table tr:nth-child(odd) {
    background-color: #f2f2f2 !important;
}

.mn-metadata-table tr:last-child {
    border-bottom: 1px solid #154C8D !important; 
}

/* Style dla mn-document-download z flex-wrap */
.mn-document-download {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    gap: 10px !important;
}

.mn-document-download img {
    order: 1 !important;
    flex-shrink: 0 !important;
}

.mn-document-download .mn-document-name {
    order: 2 !important;
    flex: 1 1 auto !important;
    min-width: 200px !important;
}

.mn-document-download .mn-document-desc {
    order: 3 !important;
    flex: 1 1 100% !important;
}

.mn-document-download .mn-document-metryczka {
    order: 4 !important;
    flex-shrink: 0 !important;
    margin-left: auto !important;
    margin-right: 10px !important;
}

.mn-document-download>a[target='_blank']:not(.mn-document-metryczka) {
    order: 5 !important;
    flex-shrink: 0 !important;
    margin-left: 0 !important;
}

.pdf-metadata-container {
        clear: both !important;
        margin-top: 0px !important;
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
}
    
/* przycisk Metryczka w .mn-document-download */
.mn-document-metryczka{
    cursor: pointer !important;
    color: #154C8D !important;
    text-decoration: underline !important;
    margin-right: 10px !important;
    white-space: nowrap !important;
}



",
        'excluded_elements' => '',
        'excluded_classes' => 'mn-document-download',
        'url_prefix' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'], // Automatycznie pobierany z WordPress    
    );
}
