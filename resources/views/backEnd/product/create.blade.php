@extends('backEnd.layouts.master')

@section('title', 'Product Create')

@section('css')
<link href="{{ asset('public/backEnd/assets/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('public/backEnd/assets/libs/summernote/summernote-lite.min.css') }}" rel="stylesheet" type="text/css" />
<style>
  :root {
    --product-card-border: #d6e4ff;
    --product-card-bg: #f7fbff;
    --variant-card-bg: #f8fafc;
    --variant-card-border: #dbeafe;
    --variant-accent: #0b6bcb;
    --field-height: 48px;
    --field-padding: 12px 16px;
    --field-font: 1rem;
    --label-weight: 600;
    --label-color: #1e3a5f;
    --input-radius: 10px;
  }

  body.wc-admin-shell .product-section-card {
    border: 1px solid var(--product-card-border);
    box-shadow: 0 8px 20px rgba(11, 107, 203, 0.08);
  }

  body.wc-admin-shell .product-section-card .card-header {
    background: linear-gradient(90deg, var(--product-card-bg) 0%, #ffffff 100%);
    border-bottom: 1px solid var(--product-card-border);
    padding: 1.25rem 1.5rem;
  }

  body.wc-admin-shell .variant-card {
    background: var(--variant-card-bg);
    border: 1px solid var(--variant-card-border);
    border-radius: 12px;
    padding: 1.25rem;
  }

  body.wc-admin-shell .variant-builder-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 0.7rem;
    align-items: end;
  }

  body.wc-admin-shell .variant-grid-item {
    min-width: 0;
  }

  body.wc-admin-shell .variant-field-color {
    grid-column: span 3;
  }

  body.wc-admin-shell .variant-field-size,
  body.wc-admin-shell .variant-field-age,
  body.wc-admin-shell .variant-field-sku {
    grid-column: span 2;
  }

  body.wc-admin-shell .variant-field-price {
    grid-column: span 2;
  }

  body.wc-admin-shell .variant-field-images {
    grid-column: span 6;
  }

  body.wc-admin-shell .variant-field-add {
    grid-column: span 2;
  }

  body.wc-admin-shell .variant-card-grid {
    display: flex;
    flex-direction: column;
    gap: 0;
  }

  /* Variant row-wise table */
  body.wc-admin-shell .variant-table-wrapper {
    overflow-x: auto;
    border: 1px solid #dbe5f5;
    border-radius: 10px;
    background: #fff;
  }

  body.wc-admin-shell .variant-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.84rem;
  }

  body.wc-admin-shell .variant-table thead th {
    background: #f0f5fc;
    color: #1b365d;
    font-weight: 700;
    font-size: 0.74rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.65rem 0.7rem;
    border-bottom: 2px solid #d5e2f5;
    white-space: nowrap;
    text-align: left;
  }

  body.wc-admin-shell .variant-table tbody tr {
    border-bottom: 1px solid #e8eef9;
    transition: background 0.15s ease;
  }

  body.wc-admin-shell .variant-table tbody tr:hover {
    background: #f8fbff;
  }

  body.wc-admin-shell .variant-table tbody tr:last-child {
    border-bottom: none;
  }

  body.wc-admin-shell .variant-table tbody td {
    padding: 0.55rem 0.7rem;
    vertical-align: middle;
    color: #233c5f;
    font-weight: 600;
  }

  body.wc-admin-shell .variant-table .variant-row-num {
    background: #edf4ff;
    color: #23436d;
    border: 1px solid #d5e2f5;
    border-radius: 999px;
    padding: 0.15rem 0.5rem;
    font-size: 0.72rem;
    font-weight: 700;
    display: inline-block;
  }

  body.wc-admin-shell .variant-table .variant-row-actions {
    display: flex;
    gap: 0.3rem;
    white-space: nowrap;
  }

  body.wc-admin-shell .variant-table .variant-row-images-cell {
    min-width: 120px;
  }

  /* Attribute Set compact toggle list */
  body.wc-admin-shell .attribute-toggle-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  body.wc-admin-shell .attribute-toggle-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid #dce7f7;
    border-radius: 8px;
    background: #fff;
    padding: 0.5rem 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
  }

  body.wc-admin-shell .attribute-toggle-item:hover {
    border-color: #0b6bcb;
    background: #f3f8ff;
  }

  body.wc-admin-shell .attribute-toggle-item.is-active {
    border-color: #0b6bcb;
    background: linear-gradient(135deg, #eef5ff, #dbeafe);
    box-shadow: 0 2px 6px rgba(11, 107, 203, 0.12);
  }

  body.wc-admin-shell .attribute-toggle-item .attr-name {
    font-size: 0.86rem;
    font-weight: 600;
    color: #1f3c62;
  }

  body.wc-admin-shell .attribute-toggle-item.is-active .attr-name {
    color: #0b6bcb;
  }

  body.wc-admin-shell #variant-section .btn-choose-variant-images,
  body.wc-admin-shell #variant-section .btn-edit-variant,
  body.wc-admin-shell #variant-section .btn-remove-variant {
    min-height: 34px;
    font-size: 0.78rem;
    padding: 0.25rem 0.55rem;
  }

  @media (max-width: 1199.98px) {
    body.wc-admin-shell .variant-field-color {
      grid-column: span 4;
    }

    body.wc-admin-shell .variant-field-size,
    body.wc-admin-shell .variant-field-age,
    body.wc-admin-shell .variant-field-sku {
      grid-column: span 3;
    }

    body.wc-admin-shell .variant-field-price,
    body.wc-admin-shell .variant-field-add {
      grid-column: span 2;
    }

    body.wc-admin-shell .variant-table-wrapper {
      font-size: 0.82rem;
    }
  }

  @media (max-width: 767.98px) {
    body.wc-admin-shell .variant-builder-grid {
      grid-template-columns: 1fr;
    }

    body.wc-admin-shell .variant-grid-item,
    body.wc-admin-shell .variant-field-color,
    body.wc-admin-shell .variant-field-size,
    body.wc-admin-shell .variant-field-age,
    body.wc-admin-shell .variant-field-sku,
    body.wc-admin-shell .variant-field-price,
    body.wc-admin-shell .variant-field-add,
    body.wc-admin-shell .variant-field-images {
      grid-column: auto;
    }

    body.wc-admin-shell .variant-table {
      font-size: 0.78rem;
    }
  }

  body.wc-admin-shell .variant-title {
    color: var(--variant-accent);
    letter-spacing: 0.02em;
    font-weight: 700;
    font-size: 1.25rem;
  }

  body.wc-admin-shell .required-star {
    color: #dc3545;
    font-weight: 700;
  }

  body.wc-admin-shell .wc-form-grid .form-control,
  body.wc-admin-shell .wc-form-grid .form-select,
  body.wc-admin-shell .wc-form-grid select,
  body.wc-admin-shell .wc-form-grid input[type="text"],
  body.wc-admin-shell .wc-form-grid input[type="number"],
  body.wc-admin-shell .wc-form-grid input[type="date"] {
    min-height: var(--field-height);
    height: auto;
    padding: var(--field-padding);
    font-size: var(--field-font);
    border-radius: var(--input-radius);
    border: 1.5px solid #d6e4ff;
    background: #fff;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    width: 100%;
  }

  body.wc-admin-shell .wc-form-grid .form-control:hover,
  body.wc-admin-shell .wc-form-grid .form-select:hover,
  body.wc-admin-shell .wc-form-grid select:hover {
    border-color: #0b6bcb;
    background: #fafbff;
  }

  body.wc-admin-shell .wc-form-grid .form-control:focus,
  body.wc-admin-shell .wc-form-grid .form-select:focus,
  body.wc-admin-shell .wc-form-grid select:focus {
    border-color: #0b6bcb;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(11, 107, 203, 0.15);
    outline: none;
  }

  body.wc-admin-shell .wc-form-grid .form-control.is-invalid,
  body.wc-admin-shell .wc-form-grid .form-select.is-invalid,
  body.wc-admin-shell .wc-form-grid select.is-invalid {
    border-color: #dc3545;
    background-image: none;
  }

  body.wc-admin-shell .wc-form-grid .form-control.is-invalid:focus,
  body.wc-admin-shell .wc-form-grid .form-select.is-invalid:focus,
  body.wc-admin-shell .wc-form-grid select.is-invalid:focus {
    box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.15);
  }

  body.wc-admin-shell .wc-form-grid textarea.form-control,
  body.wc-admin-shell .wc-form-grid textarea {
    min-height: 120px;
    padding: 14px 16px;
    font-size: var(--field-font);
    border-radius: var(--input-radius);
    border: 1.5px solid #d6e4ff;
    line-height: 1.6;
  }

  body.wc-admin-shell .wc-form-grid textarea.form-control:focus,
  body.wc-admin-shell .wc-form-grid textarea:focus {
    border-color: #0b6bcb;
    box-shadow: 0 0 0 4px rgba(11, 107, 203, 0.15);
  }

  /* Enhanced Select2 styling */
  body.wc-admin-shell .wc-form-grid .select2-container .select2-selection--single,
  body.wc-admin-shell .wc-form-grid .select2-container .select2-selection--multiple {
    min-height: var(--field-height) !important;
    border: 1.5px solid #d6e4ff !important;
    border-radius: var(--input-radius) !important;
    background: #fff;
    transition: all 0.2s ease;
  }

  body.wc-admin-shell .wc-form-grid .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(var(--field-height) - 2px);
    width: 40px;
  }

  body.wc-admin-shell .wc-form-grid .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: calc(var(--field-height) - 2px) !important;
    padding-left: 14px;
    padding-right: 14px;
    font-size: var(--field-font);
    color: #1e3a5f;
  }

  body.wc-admin-shell .wc-form-grid .select2-container--default .select2-selection--multiple {
    padding: 8px 10px;
    min-height: calc(var(--field-height) + 12px) !important;
  }

  body.wc-admin-shell .wc-form-grid .select2-container--default .select2-selection--multiple .select2-selection__rendered {
    padding: 4px 8px;
    font-size: calc(var(--field-font) * 0.95);
  }

  body.wc-admin-shell .wc-form-grid .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background: linear-gradient(135deg, #eef5ff 0%, #dbeafe 100%) !important;
    border: 1px solid #d6e4ff !important;
    color: #1e3a5f;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 6px;
    margin-top: 3px;
    margin-bottom: 3px;
  }

  body.wc-admin-shell .wc-form-grid .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #0b6bcb;
    margin-right: 6px;
    font-weight: 700;
  }

  body.wc-admin-shell .wc-form-grid .select2-container--open .select2-selection--single,
  body.wc-admin-shell .wc-form-grid .select2-container--open .select2-selection--multiple {
    border-color: #0b6bcb !important;
    box-shadow: 0 0 0 3px rgba(11, 107, 203, 0.15) !important;
  }

  body.wc-admin-shell .select2-dropdown {
    border: 1.5px solid #d6e4ff !important;
    border-radius: 8px !important;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12) !important;
  }

  body.wc-admin-shell .select2-results__option {
    padding: 10px 14px;
    font-size: 0.95rem;
  }

  body.wc-admin-shell .select2-results__option--highlighted[aria-selected] {
    background-color: #eef5ff !important;
    color: #1e3a5f !important;
  }

  /* Labels */
  body.wc-admin-shell .wc-form-grid .wc-form-label,
  body.wc-admin-shell .wc-form-grid .form-label {
    font-weight: var(--label-weight);
    color: var(--label-color);
    font-size: 0.95rem;
    padding-top: 12px;
    margin-bottom: 0;
    display: block;
  }

  /* Error feedback */
  body.wc-admin-shell .wc-form-grid .invalid-feedback {
    font-size: 0.85rem;
    font-weight: 600;
    padding-top: 6px;
    margin-bottom: 0;
    color: #dc3545;
  }

  body.wc-admin-shell .wc-form-grid .wc-form-help {
    font-size: 0.85rem;
    font-weight: 600;
    color: #5f789f;
    margin-top: 6px;
    display: block;
  }

  /* Variant table inputs */
  body.wc-admin-shell .variant-card .form-control {
    min-height: 40px;
    font-size: 0.9rem;
    border-radius: 6px;
  }

  body.wc-admin-shell .variant-value-picker {
    border: 1px solid #e2eaf6;
    border-radius: 8px;
    background: #fff;
    padding: 0.5rem 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  body.wc-admin-shell .variant-value-picker-head {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
  }

  body.wc-admin-shell .variant-value-picker-title {
    font-size: 0.84rem;
    font-weight: 700;
    color: #1f3c62;
  }

  body.wc-admin-shell .variant-value-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    flex: 1;
  }

  body.wc-admin-shell .variant-value-option {
    border: 1px solid #dce7f7;
    border-radius: 6px;
    background: #f9fbff;
    padding: 0.3rem 0.5rem;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: #314a70;
    font-size: 0.82rem;
    font-weight: 600;
  }

  body.wc-admin-shell .variant-attribute-toggle,
  body.wc-admin-shell .variant-builder-value-check,
  body.wc-admin-shell .variant-builder-all-values {
    position: static !important;
    display: inline-block;
    flex: 0 0 18px;
    width: 18px;
    height: 18px;
    min-width: 18px;
    margin: 0 !important;
    vertical-align: middle;
    cursor: pointer;
  }

  body.wc-admin-shell .variant-builder-all-values {
    flex-basis: 16px;
    width: 16px;
    height: 16px;
    min-width: 16px;
  }

  /* File input styling */
  body.wc-admin-shell .wc-form-grid input[type="file"] {
    padding: 10px 14px;
    font-size: 0.9rem;
    border: 1.5px dashed #c2d7ff;
    border-radius: var(--input-radius);
    background: linear-gradient(135deg, #f7fbff 0%, #f0f6ff 100%);
    cursor: pointer;
    transition: all 0.2s ease;
  }

  body.wc-admin-shell .wc-form-grid input[type="file"]:hover {
    border-color: #0b6bcb;
    background: linear-gradient(135deg, #eef5ff 0%, #dbeafe 100%);
  }

  body.wc-admin-shell .wc-form-grid input[type="file"]::file-selector-button {
    background: #eef5ff;
    border: 1px solid #d6e4ff;
    border-radius: 6px;
    padding: 4px 12px;
    margin-right: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #0b6bcb;
    cursor: pointer;
  }

  body.wc-admin-shell .wc-form-grid input[type="file"]::file-selector-button:hover {
    background: #dbeafe;
    border-color: #0b6bcb;
  }

  /* Summernote editor */
  body.wc-admin-shell .summernote {
    min-height: 220px;
    border-radius: var(--input-radius) !important;
    border: 1.5px solid #d6e4ff !important;
  }

  body.wc-admin-shell .note-editor {
    border-radius: var(--input-radius) !important;
  }

  body.wc-admin-shell .note-toolbar {
    background: #f7fbff;
    border-bottom: 1.5px solid #d6e4ff !important;
    border-radius: var(--input-radius) var(--input-radius) 0 0 !important;
  }

  body.wc-admin-shell .note-btn {
    font-size: 0.9rem;
  }

  /* Card body */
  body.wc-admin-shell .product-section-card .card-body {
    padding: 1.5rem 1.5rem 1.25rem;
  }

  /* Row spacing */
  body.wc-admin-shell .wc-form-row {
    padding: 16px 0;
    gap: 0;
  }

  body.wc-admin-shell .wc-form-row + .wc-form-row {
    border-top: 1px dashed rgba(199, 216, 242, 0.7);
    margin-top: 4px;
    padding-top: 16px;
  }

  /* Section title */
  body.wc-admin-shell .wc-form-section-title {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: 0.01em;
    color: #123867;
  }

  /* Action buttons */
  body.wc-admin-shell .wc-form-actions-inner .btn {
    min-height: 48px;
    padding: 0 32px;
    font-size: 1.05rem;
    font-weight: 700;
    border-radius: 8px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }

  body.wc-admin-shell .btn-choose-variant-images {
    height: 42px;
    font-size: 0.9rem;
    font-weight: 600;
    padding: 0 18px;
    border-radius: 6px;
  }

  body.wc-admin-shell .wc-file-meta.variant-images-meta {
    font-size: 0.9rem;
    font-weight: 600;
    color: #375a86;
  }

  /* Image Preview Cards */
  body.wc-admin-shell .image-preview-card {
    position: relative;
    width: 100px;
    height: 100px;
    min-width: 100px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #d6e4ff;
    background: #f8fafc;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }

  body.wc-admin-shell .image-preview-card:hover {
    border-color: #0b6bcb;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(11, 107, 203, 0.15);
  }

  body.wc-admin-shell .image-preview-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  body.wc-admin-shell .image-preview-card .remove-btn {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    font-size: 16px;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all 0.2s ease;
    padding: 0;
  }

  body.wc-admin-shell .image-preview-card:hover .remove-btn {
    opacity: 1;
  }

  body.wc-admin-shell .image-preview-card .remove-btn:hover {
    background: #dc3545;
    transform: scale(1.15);
  }

  /* Thumbnail preview */
  #thumbnail-preview {
    margin-top: 12px;
    display: inline-block;
  }

  #thumbnail-img {
    border-radius: 8px;
    border: 2px solid #d6e4ff;
    transition: all 0.2s ease;
    max-width: 150px;
    max-height: 150px;
  }

  #thumbnail-preview button {
    margin-top: 8px;
  }

  /* Gallery preview container */
  #gallery-preview {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  /* Variant images preview */
  .variant-images-preview {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  /* Variant builder preview */
  .variant-builder-preview {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  /* Better spacing for file input help text */
  .wc-form-help {
    margin-top: 6px;
    margin-bottom: 0;
    font-size: 0.85rem;
    font-weight: 600;
    color: #5f789f;
    display: block;
    line-height: 1.4;
  }

  /* Compact dashboard-style layout refresh */
  body.wc-admin-shell .wc-create-layout {
    align-items: flex-start;
  }

  body.wc-admin-shell .wc-compact-card {
    border-color: #dbe5f5;
    border-radius: 14px;
    box-shadow: 0 8px 22px rgba(18, 56, 103, 0.08);
  }

  body.wc-admin-shell .wc-compact-card .card-header {
    padding: 0.8rem 1rem;
    background: #f3f6fb;
    border-bottom: 1px solid #e5edf9;
  }

  body.wc-admin-shell .wc-compact-card .card-body {
    padding: 1rem;
  }

  body.wc-admin-shell .wc-form-section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1b365d;
  }

  body.wc-admin-shell .wc-form-grid .form-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #425672;
    margin-bottom: 0.35rem;
    padding-top: 0;
  }

  body.wc-admin-shell .wc-form-grid .form-control,
  body.wc-admin-shell .wc-form-grid .form-select,
  body.wc-admin-shell .wc-form-grid select,
  body.wc-admin-shell .wc-form-grid input[type="text"],
  body.wc-admin-shell .wc-form-grid input[type="number"],
  body.wc-admin-shell .wc-form-grid input[type="date"] {
    min-height: 40px;
    padding: 8px 11px;
    font-size: 0.85rem;
    border-radius: 8px;
    border: 1px solid #cfdbef;
    box-shadow: none;
  }

  body.wc-admin-shell .wc-form-grid textarea.form-control,
  body.wc-admin-shell .wc-form-grid textarea {
    min-height: 96px;
    padding: 10px 12px;
    font-size: 0.86rem;
    border-radius: 8px;
  }

  body.wc-admin-shell .wc-form-grid .select2-container .select2-selection--single,
  body.wc-admin-shell .wc-form-grid .select2-container .select2-selection--multiple {
    min-height: 40px !important;
    border-radius: 8px !important;
    border-color: #cfdbef !important;
  }

  body.wc-admin-shell .wc-form-grid .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px !important;
    font-size: 0.85rem;
    color: #243a5e;
  }

  body.wc-admin-shell .wc-form-grid .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 38px;
  }

  body.wc-admin-shell .wc-form-grid input[type="file"] {
    padding: 8px 10px;
    min-height: 40px;
    font-size: 0.82rem;
    border-style: dashed;
    border-color: #c6d6f2;
    background: #f8fbff;
  }

  body.wc-admin-shell .wc-type-switch {
    border: 1px solid #d5e2f6;
    border-radius: 10px;
    background: #f8fbff;
    padding: 0.62rem 0.8rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
  }

  body.wc-admin-shell .wc-type-switch .form-check-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #314a70;
  }

  body.wc-admin-shell .wc-type-switch .form-check-input {
    margin-top: 0.1rem;
  }

  body.wc-admin-shell .wc-sidebar-card .card-body {
    padding: 0.9rem 1rem 1rem;
  }

  body.wc-admin-shell .wc-toggle-list .form-check {
    padding: 0.5rem 0.55rem;
    border: 1px solid #e8eef9;
    border-radius: 8px;
    background: #fff;
  }

  body.wc-admin-shell .wc-toggle-list .form-check.form-switch {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    min-height: 44px;
    padding-left: 0.7rem;
  }

  body.wc-admin-shell .wc-toggle-list .form-check.form-switch .form-check-input {
    float: none;
    margin: 0;
    margin-left: 0;
    flex-shrink: 0;
    order: 2;
  }

  body.wc-admin-shell .wc-toggle-list .form-check + .form-check {
    margin-top: 0.45rem;
  }

  body.wc-admin-shell .wc-toggle-list .form-check-label {
    font-size: 0.84rem;
    font-weight: 600;
    color: #3a5174;
    margin-bottom: 0;
    order: 1;
  }

  body.wc-admin-shell .summernote {
    min-height: 180px;
  }

  body.wc-admin-shell #thumbnail-preview {
    display: block;
    width: 100%;
  }

  body.wc-admin-shell #thumbnail-img {
    max-width: 100%;
    max-height: 170px;
    width: 100%;
    object-fit: cover;
  }

  body.wc-admin-shell .wc-form-actions-inner {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
  }

  body.wc-admin-shell .wc-form-actions-inner .btn {
    min-height: 40px;
    font-size: 0.86rem;
    text-transform: none;
    letter-spacing: 0;
    padding: 0 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  @media (max-width: 1199.98px) {
    body.wc-admin-shell .wc-sidebar-card {
      margin-bottom: 1rem;
    }
  }
</style>
@endsection

@section('content')
@php
  $resolvedProductType = old('product_type', $productType ?? request()->get('product_type', 'simple'));
  $resolvedProductType = strtolower((string) $resolvedProductType);
  $resolvedProductType = in_array($resolvedProductType, ['simple', 'variable'], true) ? $resolvedProductType : 'simple';
  $isVariableProduct = $resolvedProductType === 'variable';
  
  // Initialize variant data for variable products
  $oldVariants = old('variants');
  if (!is_array($oldVariants)) {
    $oldVariants = [];
  }
  $catalogAttributeCollection = collect($catalogAttributes ?? collect())->map(function ($attribute) {
    $values = collect($attribute->values ?? [])->map(function ($value) {
      return [
        'id' => (int) $value->id,
        'value' => (string) $value->value,
        'meta' => is_array($value->meta ?? null) ? $value->meta : null,
      ];
    })->values()->all();

    return [
      'id' => (int) $attribute->id,
      'name' => (string) $attribute->name,
      'slug' => (string) $attribute->slug,
      'is_required' => (bool) ($attribute->is_required ?? false),
      'values' => $values,
    ];
  })->values();
  $catalogAttributesJson = $catalogAttributeCollection->toJson();
  $catalogAttributeMap = $catalogAttributeCollection
    ->mapWithKeys(fn ($attribute) => [(int) $attribute['id'] => $attribute])
    ->all();
  $catalogValueMap = $catalogAttributeCollection
    ->flatMap(function ($attribute) {
      return collect($attribute['values'] ?? [])->mapWithKeys(function ($value) use ($attribute) {
        return [
          (int) $value['id'] => [
            'id' => (int) $value['id'],
            'value' => (string) $value['value'],
            'attribute_id' => (int) $attribute['id'],
            'attribute_name' => (string) $attribute['name'],
            'attribute_slug' => (string) $attribute['slug'],
          ],
        ];
      });
    })
    ->all();
  $selectedCatalogAttributeIds = collect(old('selected_attribute_ids', []))
    ->map(fn ($id) => (int) $id)
    ->filter(fn ($id) => $id > 0)
    ->values()
    ->all();
  $oldVariants = array_values(array_filter($oldVariants, function ($variant) {
    if (!is_array($variant)) {
      return false;
    }
    if (!empty($variant['attribute_value_map']) && is_array($variant['attribute_value_map'])) {
      foreach ($variant['attribute_value_map'] as $valueId) {
        if ((int) $valueId > 0) {
          return true;
        }
      }
    }
    foreach (['sku', 'sku_code', 'price'] as $field) {
      if (trim((string) ($variant[$field] ?? '')) !== '') {
        return true;
      }
    }
    if (!empty($variant['existing_images']) && is_array($variant['existing_images'])) {
      return true;
    }
    return false;
  }));
@endphp
@php
  $galleryAssetBaseUrl = rtrim(request()->getSchemeAndHttpHost() . request()->getBaseUrl(), '/');
  $galleryFallbackAssetPath = 'public/uploads/default/no-image.png';
  $galleryFallbackAssetUrl = $galleryAssetBaseUrl . '/' . ltrim($galleryFallbackAssetPath, '/');
  $existingGalleryImages = collect([]);
  try {
    $legacyGallery = \Illuminate\Support\Facades\File::glob(public_path('uploads/product/*.{jpg,jpeg,png,webp,gif}'), GLOB_BRACE) ?: [];
    $storageGallery = \Illuminate\Support\Facades\File::glob(public_path('storage/products/gallery/*.{jpg,jpeg,png,webp,gif}'), GLOB_BRACE) ?: [];
    $variantGallery = \Illuminate\Support\Facades\File::glob(public_path('storage/products/variants/*.{jpg,jpeg,png,webp,gif}'), GLOB_BRACE) ?: [];

    $existingGalleryImages = collect(array_merge($storageGallery, $variantGallery, $legacyGallery))
      ->map(function ($absolutePath) {
        $normalized = str_replace('\\', '/', (string) $absolutePath);
        $storageToken = '/public/storage/';
        $uploadsToken = '/public/uploads/';

        if (str_contains($normalized, $storageToken)) {
          return 'storage/' . ltrim((string) str_replace('\\', '/', substr($normalized, strpos($normalized, $storageToken) + strlen($storageToken))), '/');
        }

        if (str_contains($normalized, $uploadsToken)) {
          return 'public/uploads/' . ltrim((string) str_replace('\\', '/', substr($normalized, strpos($normalized, $uploadsToken) + strlen($uploadsToken))), '/');
        }

        return null;
      })
      ->filter()
      ->unique()
      ->values();
  } catch (\Throwable $e) {
    $existingGalleryImages = collect([]);
  }

  $resolveExistingGalleryPreviewUrl = static function (?string $path) use ($galleryAssetBaseUrl, $galleryFallbackAssetUrl): string {
    $normalizedPath = ltrim(str_replace('\\', '/', trim((string) $path)), '/');

    if ($normalizedPath === '') {
      return $galleryFallbackAssetUrl;
    }

    if (\Illuminate\Support\Str::startsWith($normalizedPath, ['http://', 'https://', 'data:'])) {
      return $normalizedPath;
    }

    if (\Illuminate\Support\Str::startsWith($normalizedPath, 'public/')) {
      return $galleryAssetBaseUrl . '/' . ltrim($normalizedPath, '/');
    }

    if (\Illuminate\Support\Str::startsWith($normalizedPath, ['storage/', 'uploads/'])) {
      return $galleryAssetBaseUrl . '/public/' . ltrim($normalizedPath, '/');
    }

    return $galleryAssetBaseUrl . '/public/' . ltrim($normalizedPath, '/');
  };

  $fieldErrorKeys = collect($errors->keys())->reject(function ($key) {
    return $key === 'error';
  });
@endphp

<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <div class="page-title-right">
          <a href="{{ route('admin.products.index') }}" class="btn btn-primary rounded-pill">Manage Products</a>
        </div>
        <h4 class="page-title">Create Product</h4>
      </div>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if ($errors->has('error'))
    <div class="alert alert-danger">{{ $errors->first('error') }}</div>
  @endif

  @if ($fieldErrorKeys->isNotEmpty())
    <div class="alert alert-danger">
      <strong>Please fix the validation errors below.</strong>
    </div>
  @endif

  <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" id="product-create-form" class="wc-form-grid">
    @csrf

    <div class="row g-3 wc-create-layout">
      <div class="col-12 col-xl-9">
        <div class="card product-section-card wc-compact-card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 wc-form-section-title">General</h5>
            <span class="badge rounded-pill text-bg-light">Add Product</span>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Product Type <span class="required-star">*</span></label>
                <div class="wc-type-switch">
                  <div class="form-check mb-0">
                    <input class="form-check-input js-product-type" type="radio" name="product_type" id="product_type_simple" value="simple" {{ $resolvedProductType === 'simple' ? 'checked' : '' }}>
                    <label class="form-check-label" for="product_type_simple">Simple Product</label>
                  </div>
                  <div class="form-check mb-0">
                    <input class="form-check-input js-product-type" type="radio" name="product_type" id="product_type_variable" value="variable" {{ $resolvedProductType === 'variable' ? 'checked' : '' }}>
                    <label class="form-check-label" for="product_type_variable">Variable Product</label>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <label for="name" class="form-label">Product Name <span class="required-star">*</span></label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-3">
                <label for="category_id" class="form-label">Category <span class="required-star">*</span></label>
                <select id="category_id" name="category_id" class="form-control select2-single @error('category_id') is-invalid @enderror" required>
                  <option value="">Select category</option>
                  @if($categories->count() > 0)
                    @foreach($categories as $category)
                      <option value="{{ $category->id }}" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                      </option>
                    @endforeach
                  @else
                    <option value="" disabled>No categories available</option>
                  @endif
                </select>
                @if($categories->count() === 0)
                  <small class="text-danger wc-form-help">No active categories found</small>
                @endif
                @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-3">
                <label for="brand_id" class="form-label">Brand</label>
                <select id="brand_id" name="brand_id" class="form-control select2-single @error('brand_id') is-invalid @enderror">
                  <option value="">Select brand</option>
                  @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ (string) old('brand_id') === (string) $brand->id ? 'selected' : '' }}>
                      {{ $brand->name }}
                    </option>
                  @endforeach
                </select>
                @error('brand_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-4">
                <label for="subcategory_id" class="form-label">Sub Category</label>
                <select id="subcategory_id" name="subcategory_id" class="form-control select2-single @error('subcategory_id') is-invalid @enderror">
                  <option value="">Select sub category</option>
                  @foreach(($subcategories ?? collect()) as $subcategory)
                    <option value="{{ $subcategory->id }}" {{ (string) old('subcategory_id') === (string) $subcategory->id ? 'selected' : '' }}>
                      {{ $subcategory->subcategoryName }}
                    </option>
                  @endforeach
                </select>
                @error('subcategory_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-4">
                <label for="childcategory_id" class="form-label">Child Category</label>
                <select id="childcategory_id" name="childcategory_id" class="form-control select2-single @error('childcategory_id') is-invalid @enderror">
                  <option value="">Select child category</option>
                  @foreach(($childcategories ?? collect()) as $childcategory)
                    <option value="{{ $childcategory->id }}" {{ (string) old('childcategory_id') === (string) $childcategory->id ? 'selected' : '' }}>
                      {{ $childcategory->childcategoryName }}
                    </option>
                  @endforeach
                </select>
                @error('childcategory_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-4">
                <label for="purchase_price" class="form-label">Purchase Price <span class="required-star">*</span></label>
                <input type="number" step="0.01" min="0" id="purchase_price" name="purchase_price" class="form-control @error('purchase_price') is-invalid @enderror" value="{{ old('purchase_price') }}" required>
                @error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-4">
                <label for="old_price" class="form-label">Old Price</label>
                <input type="number" step="0.01" min="0" id="old_price" name="old_price" class="form-control @error('old_price') is-invalid @enderror" value="{{ old('old_price') }}">
                @error('old_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-4 js-simple-only {{ $isVariableProduct ? 'd-none' : '' }}">
                <label for="new_price" class="form-label">New Price <span class="required-star">*</span></label>
                <input type="number" step="0.01" min="0" id="new_price" name="new_price" class="form-control @error('new_price') is-invalid @enderror" value="{{ old('new_price') }}">
                @error('new_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </div>

        @include('backEnd.product.partials._shipping_card', ['product' => null, 'shippingProfiles' => $shippingProfiles ?? collect()])

        <div class="card product-section-card wc-compact-card mb-3">
          <div class="card-header">
            <h5 class="mb-0 wc-form-section-title">Description</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12">
                <label for="short_description" class="form-label">Short Description</label>
                <textarea id="short_description" name="short_description" rows="3" class="form-control @error('short_description') is-invalid @enderror">{{ old('short_description') }}</textarea>
                @error('short_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label for="meta_keyword" class="form-label">Meta Keywords</label>
                <textarea id="meta_keyword" name="meta_keyword" rows="3" class="form-control @error('meta_keyword') is-invalid @enderror">{{ old('meta_keyword') }}</textarea>
                <div class="wc-form-help">Enter keywords separated by commas (example: shoes, men, running).</div>
                @error('meta_keyword')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label for="meta_description" class="form-label">Meta Description</label>
                <textarea id="meta_description" name="meta_description" rows="3" class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description') }}</textarea>
                @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12 @error('description') is-invalid-row @enderror">
                <label for="description" class="form-label">Description <span class="required-star">*</span></label>
                <textarea id="description" name="description" rows="6" class="summernote form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label for="note" class="form-label">Note</label>
                <textarea id="note" name="note" rows="4" class="form-control @error('note') is-invalid @enderror">{{ old('note') }}</textarea>
                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-3">
        <div class="card product-section-card wc-compact-card wc-sidebar-card mb-3">
          <div class="card-header">
            <h6 class="mb-0">Thumbnail</h6>
          </div>
          <div class="card-body">
            <label for="thumbnail" class="form-label">Product Thumbnail</label>
            <input type="file" id="thumbnail" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" accept="image/*">
            <small class="wc-form-help">JPG/PNG, recommended 800x800px.</small>
            @error('thumbnail')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div id="thumbnail-preview" class="mt-2" style="display: none; border: 1px solid #ddd; padding: 5px; width: 150px; border-radius: 4px;">
              <img id="thumbnail-img" src="#" alt="Thumbnail preview" style="max-width: 100%; height: auto; display: block; margin-bottom: 5px;">
              <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeThumbnail()">Remove</button>
            </div>
          </div>
        </div>

        <div class="card product-section-card wc-compact-card wc-sidebar-card mb-3">
          <div class="card-header">
            <h6 class="mb-0">Gallery</h6>
          </div>
          <div class="card-body">
            <label for="image" class="form-label">Gallery Images</label>
            <input type="file" id="image" name="image[]" class="form-control @error('image') is-invalid @enderror" accept="image/*" multiple>
            <small class="wc-form-help">Upload multiple product images.</small>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="open-existing-gallery-btn">
              Select From Existing Gallery
            </button>
            @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div id="gallery-preview" class="mt-2 d-flex flex-wrap gap-2"></div>
            <div id="existing-gallery-selected" class="d-flex flex-wrap gap-2 mt-2"></div>
            @error('gallery_existing')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="card product-section-card wc-compact-card wc-sidebar-card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Status</h6>
            <span class="badge rounded-pill text-bg-success">Live</span>
          </div>
          <div class="card-body wc-toggle-list">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="status" name="status" value="1" {{ old('status', 1) ? 'checked' : '' }}>
              <label class="form-check-label" for="status">Published</label>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="topsale" name="topsale" value="1" {{ old('topsale') ? 'checked' : '' }}>
              <label class="form-check-label" for="topsale">Hot Deals</label>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="flashsale" name="flashsale" value="1" {{ old('flashsale') ? 'checked' : '' }}>
              <label class="form-check-label" for="flashsale">Flash Sales</label>
            </div>
          </div>
        </div>

        <div class="card product-section-card wc-compact-card wc-sidebar-card mb-3">
          <div class="card-header">
            <h6 class="mb-0">Product Details</h6>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <label for="pro_unit" class="form-label">Product Unit</label>
              <input type="text" id="pro_unit" name="pro_unit" class="form-control @error('pro_unit') is-invalid @enderror" value="{{ old('pro_unit') }}">
              @error('pro_unit')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-2">
              <label for="sold" class="form-label">Sold</label>
              <input type="number" step="1" min="0" id="sold" name="sold" class="form-control @error('sold') is-invalid @enderror" value="{{ old('sold') }}">
              @error('sold')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div>
              <label for="pro_video" class="form-label">YouTube Video Link/ID</label>
              <input type="text" id="pro_video" name="pro_video" class="form-control @error('pro_video') is-invalid @enderror" value="{{ old('pro_video') }}">
              @error('pro_video')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Variable Product Variants Section -->
    <div class="card product-section-card mb-4 js-variable-only {{ $isVariableProduct ? '' : 'd-none' }}" id="variant-section">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Variant Combinations</h5>
        <span class="text-muted small">Catalog Attributes drive every row.</span>
      </div>
      <div class="card-body">
        <div class="alert alert-info mb-3">
          New attributes created in Catalog Attributes will show here automatically. Choose which attributes apply to this product, then add one value per attribute in each variant row.
        </div>

        <div class="variant-card rounded-3 p-3 mb-3">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div class="variant-title mb-0">Attribute Set</div>
            <span class="badge bg-light text-dark" id="selectedVariantAttributeCount">{{ count($selectedCatalogAttributeIds) }} selected</span>
          </div>

          @if($catalogAttributeCollection->isEmpty())
            <div class="alert alert-warning mb-0">
              No active catalog attributes found.
              <a href="{{ route('admin.catalog-attributes.index') }}" class="alert-link">Create attributes first</a>.
            </div>
          @else
            <div class="attribute-toggle-list">
              @foreach($catalogAttributeCollection as $attribute)
                <label class="attribute-toggle-item {{ in_array((int) $attribute['id'], $selectedCatalogAttributeIds, true) ? 'is-active' : '' }}">
                  <input
                    type="checkbox"
                    class="form-check-input m-0 variant-attribute-toggle"
                    name="selected_attribute_ids[]"
                    value="{{ $attribute['id'] }}"
                    {{ in_array((int) $attribute['id'], $selectedCatalogAttributeIds, true) ? 'checked' : '' }}
                  >
                  <span class="attr-name">{{ $attribute['name'] }}</span>
                </label>
              @endforeach
            </div>
            <div class="small text-muted mt-2" id="selectedVariantAttributeNames"></div>
            @error('selected_attribute_ids')
              <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
            @enderror
          @endif
        </div>

        <div class="variant-card rounded-3 p-3 mb-3">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div class="variant-title mb-0">Variant Builder</div>
            <span class="badge bg-warning text-dark d-none" id="variantEditState">Editing row</span>
          </div>

          <div class="alert alert-info py-2 px-3 mb-3">
            <small class="mb-0"><strong>Variation stock</strong> is not set here. After saving, add stock per warehouse/variation under <a href="{{ route('admin.stock.set') }}" target="_blank" rel="noopener">Stock → Set stock</a> or <a href="{{ route('admin.inventory.index') }}" target="_blank" rel="noopener">Inventory</a>.</small>
          </div>

          <input type="hidden" id="variantEditingIndex" value="">

          <div class="variant-builder-grid">
            <div class="variant-grid-item" style="grid-column: 1 / -1;">
              <label class="form-label">Attribute Values <span class="required-star">*</span></label>
              <div id="variantBuilderAttributes" class="row g-2"></div>
              <small class="wc-form-help">Choose one value for each enabled attribute, add price/images, then click Add Selected.</small>
            </div>
            <div class="variant-grid-item" style="grid-column: 1 / -1;">
              <div id="variantDraftRows" class="variant-table-wrapper d-none">
                <table class="variant-table">
                  <thead>
                    <tr>
                      <th>Attributes</th>
                      <th>SKU</th>
                      <th>Price</th>
                      <th>Images</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
            <div class="variant-grid-item variant-field-sku">
              <label class="form-label">SKU</label>
              <input type="text" id="variantSku" class="form-control" placeholder="SKU">
            </div>
            <div class="variant-grid-item variant-field-price">
              <label class="form-label">Price <span class="required-star">*</span></label>
              <input type="number" step="0.01" min="0" id="variantPrice" class="form-control text-end" placeholder="0.00">
            </div>
            <div class="variant-grid-item variant-field-images d-flex align-items-end gap-2 flex-wrap">
              <input type="file" id="variantImages" class="form-control" accept="image/*" multiple style="flex:1;min-width:180px">
              <button type="button" class="btn btn-sm btn-outline-primary" id="open-variant-existing-gallery-btn" style="white-space:nowrap">Existing Gallery</button>
              <div class="variant-builder-preview d-flex flex-wrap gap-2"></div>
              <div class="alert alert-warning mt-0 mb-0 d-none" id="variantMessage" style="flex-basis:100%"></div>
            </div>
            <div class="variant-grid-item variant-field-add d-flex align-items-end">
              <button type="button" class="btn btn-primary w-100" id="add-variant-btn">Add Selected</button>
            </div>
          </div>
        </div>

        <div id="variantContainer" class="variant-card-grid">
          <div class="variant-table-wrapper">
            <table class="variant-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Attributes</th>
                  <th>SKU</th>
                  <th>Price</th>
                  <th>Images</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="variantTableBody">
                @foreach($oldVariants as $variantIndex => $variant)
                  @php $skuVal = old('variants.' . $variantIndex . '.sku', $variant['sku'] ?? ($variant['sku_code'] ?? '')); @endphp
                  @php $priceVal = old('variants.' . $variantIndex . '.price', $variant['price'] ?? ''); @endphp
                  @php
                    $attributeValueMap = collect($variant['attribute_value_map'] ?? [])
                      ->mapWithKeys(fn ($valueId, $attributeId) => [(int) $attributeId => (int) $valueId])
                      ->filter(fn ($valueId) => $valueId > 0)
                      ->all();
                    $attributeSummaryRows = collect($attributeValueMap)->map(function ($valueId, $attributeId) use ($catalogAttributeMap, $catalogValueMap) {
                      $attribute = $catalogAttributeMap[(int) $attributeId] ?? null;
                      $value = $catalogValueMap[(int) $valueId] ?? null;
                      if (!$attribute || !$value) { return null; }
                      return [
                        'attribute_id' => (int) $attributeId,
                        'attribute_name' => (string) $attribute['name'],
                        'attribute_slug' => (string) $attribute['slug'],
                        'value_id' => (int) $valueId,
                        'value' => (string) $value['value'],
                      ];
                    })->filter()->values();
                  @endphp
                  <tr class="variant-row" data-index="{{ $variantIndex }}">
                    <td>
                      <input type="hidden" name="variants[{{ $variantIndex }}][_active]" data-field="_active" value="1">
                      <span class="variant-row-num variant-number">{{ $variantIndex + 1 }}</span>
                    </td>
                    <td>
                      <div class="variant-value-attributes">
                        @if($attributeSummaryRows->isNotEmpty())
                          <div class="d-flex flex-wrap gap-1">
                            @foreach($attributeSummaryRows as $attributeRow)
                              <span class="badge bg-light text-dark border">{{ $attributeRow['attribute_name'] }}: {{ $attributeRow['value'] }}</span>
                            @endforeach
                          </div>
                        @else
                          -
                        @endif
                      </div>
                      <div class="variant-attribute-hidden-host">
                        @foreach($attributeSummaryRows as $attributeRow)
                          <input type="hidden" name="variants[{{ $variantIndex }}][attribute_value_map][{{ $attributeRow['attribute_id'] }}]" data-field="attribute_value_map" data-attribute-id="{{ $attributeRow['attribute_id'] }}" value="{{ $attributeRow['value_id'] }}">
                        @endforeach
                      </div>
                      @error('variants.' . $variantIndex . '.attribute_value_map')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </td>
                    <td>
                      <input type="text" name="variants[{{ $variantIndex }}][sku]" data-field="sku" class="form-control form-control-sm variant-value-sku" value="{{ $skuVal }}" placeholder="SKU">
                    </td>
                    <td>
                      <input type="number" step="0.01" min="0" name="variants[{{ $variantIndex }}][price]" data-field="price" class="form-control form-control-sm text-end variant-value-price" value="{{ $priceVal }}" placeholder="0.00">
                    </td>
                    <td class="variant-row-images-cell">
                      <div class="wc-file-pill">
                        <input type="file" class="wc-file-input-hidden" name="variants[{{ $variantIndex }}][images][]" data-field="images" accept="image/*" multiple>
                        @foreach((array) old('variants.' . $variantIndex . '.existing_images', $variant['existing_images'] ?? []) as $existingImagePath)
                          <input type="hidden" name="variants[{{ $variantIndex }}][existing_images][]" value="{{ $existingImagePath }}" data-field="existing_images">
                        @endforeach
                        <button type="button" class="btn btn-sm btn-outline-primary btn-choose-variant-images">Choose</button>
                        <span class="wc-file-meta variant-images-meta" style="font-size:0.72rem">No files</span>
                      </div>
                      <div class="variant-images-preview d-flex flex-wrap gap-1 mt-1"></div>
                    </td>
                    <td>
                      <div class="variant-row-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-variant">Edit</button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-variant">Remove</button>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="wc-form-actions mb-5">
      <div class="wc-form-actions-inner">
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" id="product-submit-btn" class="btn btn-success px-4">
          {{ $isVariableProduct ? 'Create Variable Product' : 'Create Product' }}
        </button>
      </div>
    </div>
  </form>
</div>

<div class="modal fade" id="existingGalleryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Existing Gallery Images</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        @if($existingGalleryImages->isEmpty())
          <div class="alert alert-warning mb-0">No existing gallery images found.</div>
        @else
          <div class="row g-2">
            @foreach($existingGalleryImages as $existingImagePath)
              @php $existingImagePreviewUrl = $resolveExistingGalleryPreviewUrl($existingImagePath); @endphp
              <div class="col-6 col-md-3 col-xl-2">
                <label class="w-100 border rounded p-2 h-100 existing-gallery-item">
                  <input
                    type="checkbox"
                    class="form-check-input existing-gallery-check"
                    value="{{ $existingImagePath }}"
                    {{ collect(old('gallery_existing', []))->contains($existingImagePath) ? 'checked' : '' }}
                  >
                  <img src="{{ $existingImagePreviewUrl }}" class="img-fluid rounded mt-2" style="height: 120px; width:100%; object-fit: cover;" alt="Gallery" onerror="this.onerror=null;this.src='{{ $galleryFallbackAssetUrl }}';">
                </label>
              </div>
            @endforeach
          </div>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="apply-existing-gallery-btn">Use Selected Images</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="variantExistingGalleryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Variant Images From Existing Gallery</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        @if($existingGalleryImages->isEmpty())
          <div class="alert alert-warning mb-0">No existing gallery images found.</div>
        @else
          <div class="row g-2">
            @foreach($existingGalleryImages as $existingImagePath)
              @php $existingImagePreviewUrl = $resolveExistingGalleryPreviewUrl($existingImagePath); @endphp
              <div class="col-6 col-md-3 col-xl-2">
                <label class="w-100 border rounded p-2 h-100 existing-gallery-item">
                  <input
                    type="checkbox"
                    class="form-check-input variant-existing-gallery-check"
                    value="{{ $existingImagePath }}"
                  >
                  <img src="{{ $existingImagePreviewUrl }}" class="img-fluid rounded mt-2" style="height: 120px; width:100%; object-fit: cover;" alt="Gallery" onerror="this.onerror=null;this.src='{{ $galleryFallbackAssetUrl }}';">
                </label>
              </div>
            @endforeach
          </div>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="apply-variant-existing-gallery-btn">Use Selected Images</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('script')
<script src="{{ asset('public/backEnd/assets/libs/parsleyjs/parsley.min.js') }}"></script>
<script src="{{ asset('public/backEnd/assets/js/pages/form-validation.init.js') }}"></script>
<script src="{{ asset('public/backEnd/assets/libs/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('public/backEnd/assets/libs/summernote/summernote-lite.min.js') }}"></script>

<script>
  $(document).ready(function () {
    // Initialize Select2
    $('select.select2-single').select2({
      width: '100%',
      placeholder: 'Select an option',
      allowClear: false
    });
    
    $('select.select2-multi').select2({
      width: '100%',
      placeholder: 'Select options',
      allowClear: true
    });
    
    $('select.select2').select2({
      width: '100%'
    });

     $('.summernote').summernote({
       placeholder: 'Enter product description',
       height: 220,
     });

     const variantContainer = $('#variantTableBody');
     const attributeCatalog = {!! $catalogAttributesJson !!};
     const variantAttributeCount = $('#selectedVariantAttributeCount');
     const variantAttributeNames = $('#selectedVariantAttributeNames');
     let selectedAttributeSnapshot = [];

     function escapeHtml(value) {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    const variantComposer = {
      editingIndex: null,
      editState: $('#variantEditState'),
      message: $('#variantMessage'),
      attributeHost: $('#variantBuilderAttributes'),
      draftHost: $('#variantDraftRows'),
      sku: $('#variantSku'),
      price: $('#variantPrice'),
      images: $('#variantImages'),
      addBtn: $('#add-variant-btn'),
      previewContainer: $('.variant-builder-preview')
    };

    const attributeIndex = {};
    const attributeValueIndex = {};

    attributeCatalog.forEach(function(attribute) {
      attributeIndex[String(attribute.id)] = attribute;
      (attribute.values || []).forEach(function(value) {
        attributeValueIndex[String(value.id)] = {
          id: Number(value.id),
          value: String(value.value || ''),
          attribute_id: Number(attribute.id),
          attribute_name: String(attribute.name || 'Attribute'),
          attribute_slug: String(attribute.slug || '')
        };
      });
    });

    function selectedAttributeIds() {
      const seen = {};
      return $('.variant-attribute-toggle:checked').map(function() {
        return String($(this).val() || '').trim();
      }).get().filter(function(value) {
        if (!value || seen[value]) {
          return false;
        }
        seen[value] = true;
        return true;
      });
    }

    function selectedAttributes() {
      const ids = selectedAttributeIds();
      return ids
        .map(function(id) { return attributeIndex[String(id)]; })
        .filter(function(attribute) { return !!attribute; });
    }

    function updateSelectedAttributeCount() {
      if (!variantAttributeCount.length) {
        return;
      }

      const attrs = selectedAttributes();
      const count = attrs.length;
      variantAttributeCount.text(count ? `${count} selected` : 'None selected');
      if (variantAttributeNames.length) {
        variantAttributeNames.text(count ? 'Selected fields: ' + attrs.map(function(attribute) {
          return attribute.name || 'Attribute';
        }).join(', ') : 'No variant fields selected.');
      }
    }

    function buildAttributeRowsFromValueMap(valueMap) {
      const rows = [];
      const normalizedMap = valueMap || {};

      Object.keys(normalizedMap).forEach(function(attributeIdKey) {
        const attributeId = Number(attributeIdKey || 0);
        const valueId = Number(normalizedMap[attributeIdKey] || 0);
        const attribute = attributeIndex[String(attributeId)];
        const value = attributeValueIndex[String(valueId)];

        if (!attribute || !value) {
          return;
        }

        rows.push({
          attribute_id: attributeId,
          attribute_name: String(attribute.name || 'Attribute'),
          attribute_slug: String(attribute.slug || ''),
          value_id: valueId,
          value: String(value.value || '')
        });
      });

      rows.sort(function(left, right) {
        return Number(left.attribute_id) - Number(right.attribute_id);
      });

      return rows;
    }

    function renderAttributeSummaryHtml(attributeRows) {
      if (!Array.isArray(attributeRows) || !attributeRows.length) {
        return '-';
      }

      return '<div class="d-flex flex-wrap gap-1">' + attributeRows.map(function(row) {
        return '<span class="badge bg-light text-dark border">' + escapeHtml(row.attribute_name) + ': ' + escapeHtml(row.value) + '</span>';
      }).join('') + '</div>';
    }

    function renderAttributeHiddenInputs(index, attributeRows) {
      if (!Array.isArray(attributeRows) || !attributeRows.length) {
        return '';
      }

      return attributeRows.map(function(row) {
        return '<input type="hidden" name="variants[' + index + '][attribute_value_map][' + row.attribute_id + ']" data-field="attribute_value_map" data-attribute-id="' + row.attribute_id + '" value="' + row.value_id + '">';
      }).join('');
    }

    function renderBuilderAttributeFields(selectedValueMap) {
      const attrs = selectedAttributes();
      const valueMap = selectedValueMap || {};

      updateSelectedAttributeCount();
      if (variantComposer.draftHost && variantComposer.draftHost.length) {
        variantComposer.draftHost.addClass('d-none').find('tbody').empty();
      }

      if (!variantComposer.attributeHost.length) {
        return;
      }

      if (!attrs.length) {
        variantComposer.attributeHost.html('<div class="col-12"><div class="alert alert-warning mb-0">Select at least one catalog attribute above.</div></div>');
        return;
      }

      const html = attrs.map(function(attribute) {
        const selectedValueId = Number(Array.isArray(valueMap[attribute.id]) ? valueMap[attribute.id][0] : (valueMap[attribute.id] || 0));
        const values = attribute.values || [];
        const valueOptions = values.length
          ? values.map(function(value) {
              const valueId = Number(value.id);
              const selected = selectedValueId === valueId ? ' selected' : '';

              return `<option value="${value.id}"${selected}>${escapeHtml(value.value)}</option>`;
            }).join('')
          : '';

        return `
          <div class="col-12 col-md-3">
            <label class="form-label">${escapeHtml(attribute.name)} <span class="required-star">*</span></label>
            <select class="form-control variant-builder-value-select" data-attribute-id="${attribute.id}" ${values.length ? '' : 'disabled'}>
              <option value="">Select ${escapeHtml(attribute.name)}</option>
              ${valueOptions}
            </select>
            ${values.length ? '' : '<small class="text-danger">No active values found.</small>'}
          </div>
        `;
      }).join('');

      variantComposer.attributeHost.html(html);
    }

    function syncBuilderAllValueCheckboxes() {
      variantComposer.attributeHost.find('.variant-builder-all-values').each(function() {
        const attributeId = Number($(this).data('attributeId') || 0);
        const $values = variantComposer.attributeHost.find('.variant-builder-value-check[data-attribute-id="' + attributeId + '"]');
        const checkedCount = $values.filter(':checked').length;

        this.checked = $values.length > 0 && checkedCount === $values.length;
        this.indeterminate = checkedCount > 0 && checkedCount < $values.length;
      });
    }

    function buildCombinationPayloads(valueGroups, groupIndex, valueMap, rows, combinations) {
      if (groupIndex >= valueGroups.length) {
        combinations.push({
          valueMap: Object.assign({}, valueMap),
          rows: rows.slice()
        });

        return;
      }

      const group = valueGroups[groupIndex];
      group.values.forEach(function(valueData) {
        valueMap[group.attribute.id] = Number(valueData.id);
        rows.push({
          attribute_id: Number(group.attribute.id),
          attribute_name: String(group.attribute.name || 'Attribute'),
          attribute_slug: String(group.attribute.slug || ''),
          value_id: Number(valueData.id),
          value: String(valueData.value || '')
        });

        buildCombinationPayloads(valueGroups, groupIndex + 1, valueMap, rows, combinations);

        rows.pop();
        delete valueMap[group.attribute.id];
      });
    }

    function readBuilderAttributePayload() {
      const attrs = selectedAttributes();
      const valueMap = {};
      const rows = [];
      let missingAttribute = '';

      attrs.forEach(function(attribute) {
        const valueId = Number(variantComposer.attributeHost.find('.variant-builder-value-select[data-attribute-id="' + attribute.id + '"]').val() || 0);
        const selectedValue = valueId > 0 ? attributeValueIndex[String(valueId)] : null;

        if (!selectedValue) {
          if (!missingAttribute) {
            missingAttribute = attribute.name;
          }
          return;
        }

        valueMap[attribute.id] = valueId;
        rows.push({
          attribute_id: Number(attribute.id),
          attribute_name: String(attribute.name || 'Attribute'),
          attribute_slug: String(attribute.slug || ''),
          value_id: valueId,
          value: String(selectedValue.value || '')
        });
      });

      if (missingAttribute) {
        return {
          error: 'Please select at least one ' + missingAttribute + ' value.'
        };
      }

      if (!rows.length) {
        return {
          error: 'Please select attribute values.'
        };
      }

      return {
        combinations: [{ valueMap: valueMap, rows: rows }]
      };
    }

    function readRowAttributeValueMap($row) {
      const valueMap = {};

      $row.find('input[data-field="attribute_value_map"]').each(function() {
        const attributeId = Number($(this).data('attributeId') || 0);
        const valueId = Number($(this).val() || 0);

        if (attributeId > 0 && valueId > 0) {
          valueMap[attributeId] = valueId;
        }
      });

      return valueMap;
    }

    // ==================== IMAGE PREVIEW UTILITIES ====================
    
    // Safe file reading wrapper
    function readFileAsDataURL(file, callback) {
      if (!file || !(file instanceof Blob)) {
        console.warn('Invalid file object:', file);
        return;
      }
      var reader = new FileReader();
      reader.onload = function(e) { callback(e.target.result); };
      reader.onerror = function(e) { console.error('FileReader error:', e); };
      reader.readAsDataURL(file);
    }

    // Thumbnail preview
    var thumbnailInput = $('#thumbnail');
    var thumbnailPreview = $('#thumbnail-preview');
    var thumbnailImg = $('#thumbnail-img');

    if (thumbnailInput.length) {
      thumbnailInput.on('change', function(e) {
        var file = e.target.files && e.target.files[0];
        if (file) {
          readFileAsDataURL(file, function(result) {
            thumbnailImg.attr('src', result);
            thumbnailPreview.show();
          });
        } else {
          thumbnailPreview.hide();
        }
      });
    }

    window.removeThumbnail = function() {
      if (thumbnailInput.length) {
        thumbnailInput.val('');
        thumbnailPreview.hide();
      }
    };

    // Gallery images preview
    var galleryInput = $('#image');
    var galleryPreview = $('#gallery-preview');
    var selectedExistingContainer = $('#existing-gallery-selected');
    var existingGalleryModalEl = document.getElementById('existingGalleryModal');
    var existingGalleryModal = existingGalleryModalEl ? new bootstrap.Modal(existingGalleryModalEl) : null;
    var variantExistingGalleryModalEl = document.getElementById('variantExistingGalleryModal');
    var variantExistingGalleryModal = variantExistingGalleryModalEl ? new bootstrap.Modal(variantExistingGalleryModalEl) : null;
    var galleryFiles = [];
    var variantBuilderMedia = [];
    var existingGalleryAppBase = @json($galleryAssetBaseUrl . '/');
    var existingGalleryPublicBase = @json($galleryAssetBaseUrl . '/public/');
    var existingGalleryFallbackImage = @json($galleryFallbackAssetUrl);

    if (galleryInput.length) {
      galleryInput.on('change', function(e) {
        galleryFiles = e.target.files ? Array.from(e.target.files) : [];
        renderGalleryPreview();
      });
    }

    function resolveExistingGalleryPreviewUrl(path) {
      var cleanPath = String(path || '').trim().replace(/\\/g, '/').replace(/^\/+/, '');
      if (!cleanPath) {
        return existingGalleryFallbackImage;
      }

      if (/^(https?:|data:)/i.test(cleanPath)) {
        return cleanPath;
      }

      if (/^public\//i.test(cleanPath)) {
        return existingGalleryAppBase + cleanPath;
      }

      if (/^(storage|uploads)\//i.test(cleanPath)) {
        return existingGalleryPublicBase + cleanPath;
      }

      return existingGalleryPublicBase + cleanPath;
    }

    function normalizeExistingGalleryPath(path) {
      var cleanPath = String(path || '').trim().replace(/\\/g, '/').replace(/^\/+/, '');
      if (!cleanPath) {
        return '';
      }

      if (/^public\/storage\//i.test(cleanPath)) {
        return cleanPath.replace(/^public\//i, '');
      }

      if (/^uploads\//i.test(cleanPath)) {
        return 'public/' + cleanPath;
      }

      return cleanPath;
    }

    function syncExistingGalleryHiddenInputs() {
      $('input[name="gallery_existing[]"]').remove();
      selectedExistingContainer.find('.existing-gallery-token').each(function() {
        var path = $(this).data('path');
        $('<input>', {
          type: 'hidden',
          name: 'gallery_existing[]',
          value: path
        }).appendTo('#product-create-form');
      });
    }

    function renderSelectedExistingGallery() {
      syncExistingGalleryHiddenInputs();
    }

    function addExistingGalleryToken(path) {
      var cleanPath = normalizeExistingGalleryPath(path);
      if (!cleanPath) {
        return;
      }

      var exists = selectedExistingContainer.find('.existing-gallery-token').filter(function() {
        return normalizeExistingGalleryPath($(this).data('path')) === cleanPath;
      }).length > 0;

      if (exists) {
        return;
      }

      var token = $(
        '<div class="image-preview-card existing-gallery-token" data-path="' + cleanPath + '">' +
          '<img src="' + resolveExistingGalleryPreviewUrl(cleanPath) + '" alt="Existing gallery">' +
          '<button type="button" class="remove-btn remove-existing-gallery-btn">&times;</button>' +
        '</div>'
      );
      selectedExistingContainer.append(token);
      renderSelectedExistingGallery();
    }

    $(document).on('click', '.remove-existing-gallery-btn', function() {
      $(this).closest('.existing-gallery-token').remove();
      renderSelectedExistingGallery();
    });

    $('#open-existing-gallery-btn').on('click', function() {
      $('.existing-gallery-check').prop('checked', false);
      selectedExistingContainer.find('.existing-gallery-token').each(function() {
        var tokenPath = normalizeExistingGalleryPath($(this).data('path'));
        $('.existing-gallery-check').filter(function() {
          return normalizeExistingGalleryPath($(this).val()) === tokenPath;
        }).prop('checked', true);
      });
      if (existingGalleryModal) {
        existingGalleryModal.show();
      }
    });

    $('#apply-existing-gallery-btn').on('click', function() {
      var selectedPaths = [];
      $('.existing-gallery-check:checked').each(function() {
        var cleanPath = normalizeExistingGalleryPath($(this).val());
        if (cleanPath && !selectedPaths.includes(cleanPath)) {
          selectedPaths.push(cleanPath);
        }
      });
      selectedPaths.forEach(addExistingGalleryToken);
      $('.existing-gallery-check').prop('checked', false);
      if (existingGalleryModal) {
        existingGalleryModal.hide();
      }
    });

    @foreach(collect(old('gallery_existing', [])) as $oldExistingPath)
      addExistingGalleryToken(@json($oldExistingPath));
    @endforeach

    function renderGalleryPreview() {
      if (!galleryPreview.length) return;
      galleryPreview.empty();
      for (var i = 0; i < galleryFiles.length; i++) {
        (function(index) {
          var file = galleryFiles[index];
          readFileAsDataURL(file, function(result) {
            var card = $(
              '<div class="image-preview-card" data-index="' + index + '">' +
                '<img src="' + result + '" alt="Gallery image ' + (index + 1) + '">' +
                '<button type="button" class="remove-btn" onclick="removeGalleryImage(' + index + ')">&times;</button>' +
              '</div>'
            );
            galleryPreview.append(card);
          });
        })(i);
      }
    }

    window.removeGalleryImage = function(index) {
      galleryFiles.splice(index, 1);
      var dt = new DataTransfer();
      for (var i = 0; i < galleryFiles.length; i++) {
        dt.items.add(galleryFiles[i]);
      }
      if (galleryInput.length) {
        galleryInput[0].files = dt.files;
        renderGalleryPreview();
      }
    };

    // Variant builder images preview
    function bindVariantBuilderPreviewInput() {
      if (!variantComposer || !variantComposer.images || !variantComposer.images.length) return;
      variantComposer.images.off('change.wcBuilderPreview').on('change.wcBuilderPreview', function(e) {
        var files = e.target.files ? Array.from(e.target.files) : [];
        var existingOnly = variantBuilderMedia.filter(function(item) {
          return item.type === 'existing';
        });
        variantBuilderMedia = existingOnly.concat(files.map(function(file) {
          return { type: 'upload', file: file };
        }));
        if (variantComposer.previewContainer && variantComposer.previewContainer.length) {
          variantComposer.previewContainer.empty();
        }
        for (var i = 0; i < variantBuilderMedia.length; i++) {
          (function(idx) {
            var item = variantBuilderMedia[idx];
            var renderCard = function(result) {
              var card = $(
                '<div class="image-preview-card" data-builder-index="' + idx + '">' +
                  '<img src="' + result + '" alt="Builder image ' + (idx + 1) + '">' +
                  '<button type="button" class="remove-btn" onclick="removeBuilderImage(' + idx + ')">&times;</button>' +
                '</div>'
              );
              if (variantComposer.previewContainer && variantComposer.previewContainer.length) {
                variantComposer.previewContainer.append(card);
              }
            };

            if (item.type === 'existing') {
              renderCard(resolveExistingGalleryPreviewUrl(item.path));
            } else if (item.file) {
              readFileAsDataURL(item.file, renderCard);
            }
          })(i);
        }
      });
    }
    bindVariantBuilderPreviewInput();

    function renderVariantRowPreview($row, files) {
      var previewDiv = $row.find('.variant-images-preview');
      previewDiv.empty();
      var existingPaths = [];
      $row.find('input[data-field="existing_images"]').each(function() {
        var cleanPath = normalizeExistingGalleryPath($(this).val());
        if (cleanPath && !existingPaths.includes(cleanPath)) {
          existingPaths.push(cleanPath);
        }
      });

      existingPaths.forEach(function(path, idx) {
        var card = $(
          '<div class="image-preview-card" data-existing-index="' + idx + '">' +
            '<img src="' + resolveExistingGalleryPreviewUrl(path) + '" alt="Existing variant image ' + (idx + 1) + '">' +
            '<button type="button" class="remove-btn variant-remove-existing-image-btn" data-existing-path="' + escapeHtml(path) + '">&times;</button>' +
          '</div>'
        );
        previewDiv.append(card);
      });

      for (var i = 0; i < files.length; i++) {
        (function(idx) {
          var file = files[idx];
          readFileAsDataURL(file, function(result) {
            var card = $(
              '<div class="image-preview-card" data-index="' + idx + '">' +
                '<img src="' + result + '" alt="Variant image ' + (idx + 1) + '">' +
                '<button type="button" class="remove-btn variant-remove-file-btn" data-file-index="' + idx + '">&times;</button>' +
              '</div>'
            );
            previewDiv.append(card);
          });
        })(i);
      }
    }

    variantContainer.on('click', '.variant-remove-file-btn', function(e) {
      e.preventDefault();
      var fileIndex = parseInt($(this).data('fileIndex'), 10);
      if (!Number.isFinite(fileIndex)) return;
      var $row = $(this).closest('.variant-row');
      var $input = $row.find('input[type="file"][data-field="images"]');
      if (!$input.length) return;
      var currentFiles = $input[0].files ? Array.from($input[0].files) : [];
      if (fileIndex < 0 || fileIndex >= currentFiles.length) return;
      currentFiles.splice(fileIndex, 1);
      var dt = new DataTransfer();
      for (var i = 0; i < currentFiles.length; i++) {
        dt.items.add(currentFiles[i]);
      }
      $input[0].files = dt.files;
      renderVariantRowPreview($row, currentFiles);
      $input.trigger('change.wcVariant');
    });

    variantContainer.on('click', '.variant-remove-existing-image-btn', function(e) {
      e.preventDefault();
      var path = normalizeExistingGalleryPath($(this).data('existingPath') || '');
      if (!path) return;
      var $row = $(this).closest('.variant-row');
      $row.find('input[data-field="existing_images"]').each(function() {
        if (normalizeExistingGalleryPath($(this).val()) === path) {
          $(this).remove();
        }
      });
      var $input = $row.find('input[type="file"][data-field="images"]');
      renderVariantRowPreview($row, $input[0]?.files ? Array.from($input[0].files) : []);
      updateVariantRowImageMeta($row);
    });

    window.removeBuilderImage = function(index) {
      var input = variantComposer && variantComposer.images ? variantComposer.images[0] : null;
      if (!input) return;
      if (index >= 0 && index < variantBuilderMedia.length) {
        variantBuilderMedia.splice(index, 1);
        var currentFiles = variantBuilderMedia
          .filter(function(item) { return item.type === 'upload' && item.file; })
          .map(function(item) { return item.file; });
        var dt = new DataTransfer();
        for (var i = 0; i < currentFiles.length; i++) {
          dt.items.add(currentFiles[i]);
        }
        input.files = dt.files;
        if (variantComposer && variantComposer.images) {
          variantComposer.images.trigger('change');
        }
      }
    };
    // Clear builder preview on Add (after row is added) - already handled by clearVariantComposer

    // Variant row image preview (for grid tiles)
    function initVariantRowPreview($row) {
      var $input = $row.find('input[type="file"][data-field="images"]');
      if (!$input.length) return;
      if (!$row.find('.variant-images-preview').length) {
        $('<div class="variant-images-preview d-flex flex-wrap gap-2 mt-2"></div>').insertAfter($input.closest('.wc-file-pill'));
      }
      var fileInput = $input[0];

      $input.off('change.wcVariantPreview').on('change.wcVariantPreview', function(e) {
        var files = e.target.files ? Array.from(e.target.files) : [];
        renderVariantRowPreview($row, files);
      });

      renderVariantRowPreview($row, fileInput && fileInput.files ? Array.from(fileInput.files) : []);
    }

    // ==================== END IMAGE PREVIEW ====================

    function showVariantMessage(message) {
      variantComposer.message.text(message || '');
      variantComposer.message.toggleClass('d-none', !message);
    }

    function clearVariantComposer() {
      variantComposer.sku.val('');
      variantComposer.price.val('');
      variantComposer.images.val('');
      variantComposer.editingIndex = null;
      variantBuilderMedia = [];
      variantComposer.previewContainer.empty();
      variantComposer.attributeHost.find('.variant-builder-value-select').val('');
      variantComposer.addBtn.text('Add Selected');
      variantComposer.editState.addClass('d-none');
      showVariantMessage('');
    }

    function variantRowTemplate(index, data) {
      const attributeRows = Array.isArray(data.attribute_rows)
        ? data.attribute_rows
        : buildAttributeRowsFromValueMap(data.attribute_value_map || {});
      const sku = escapeHtml(data.sku || '');
      const price = escapeHtml(data.price || '');
      const attributeSummary = renderAttributeSummaryHtml(attributeRows);
      const attributeHiddenInputs = renderAttributeHiddenInputs(index, attributeRows);
      const existingImages = Array.from(new Set((Array.isArray(data.existing_images) ? data.existing_images : [])
        .map(function(path) { return normalizeExistingGalleryPath(path); })
        .filter(function(path) { return path !== ''; })));
      const existingImageHidden = existingImages.map(function(path) {
        const clean = escapeHtml(path);
        return `<input type="hidden" name="variants[${index}][existing_images][]" value="${clean}" data-field="existing_images">`;
      }).join('');

      return `
        <tr class="variant-row" data-index="${index}">
          <td>
            <input type="hidden" name="variants[${index}][_active]" data-field="_active" value="1">
            <span class="variant-row-num variant-number">${index + 1}</span>
          </td>
          <td>
            <div class="variant-value-attributes">${attributeSummary}</div>
            <div class="variant-attribute-hidden-host">${attributeHiddenInputs}</div>
          </td>
          <td>
            <input type="text" name="variants[${index}][sku]" data-field="sku" class="form-control form-control-sm variant-value-sku" value="${sku}" placeholder="SKU">
          </td>
          <td>
            <input type="number" step="0.01" min="0" name="variants[${index}][price]" data-field="price" class="form-control form-control-sm text-end variant-value-price" value="${price}" placeholder="0.00">
          </td>
          <td class="variant-row-images-cell">
            ${existingImageHidden}
            <div class="wc-file-pill">
              <input type="file" class="wc-file-input-hidden" name="variants[${index}][images][]" data-field="images" accept="image/*" multiple>
              <button type="button" class="btn btn-sm btn-outline-primary btn-choose-variant-images">Choose</button>
              <span class="wc-file-meta variant-images-meta" style="font-size:0.72rem">No files</span>
            </div>
            <div class="variant-images-preview d-flex flex-wrap gap-1 mt-1"></div>
          </td>
          <td>
            <div class="variant-row-actions">
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit-variant">Edit</button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-remove-variant">Remove</button>
            </div>
          </td>
        </tr>
      `;
    }

    function reindexVariantCards() {
      variantContainer.find('.variant-row').each(function (index) {
        const row = $(this);
        row.attr('data-index', index);
        row.find('.variant-number').text(index + 1);

        row.find('[data-field]').each(function () {
          const field = $(this).data('field');
          if (field === 'images') {
            this.name = `variants[${index}][images][]`;
          } else if (field === 'existing_images') {
            this.name = `variants[${index}][existing_images][]`;
          } else if (field === 'attribute_value_map') {
            const attributeId = Number($(this).data('attributeId') || 0);
            this.name = `variants[${index}][attribute_value_map][${attributeId}]`;
          } else {
            this.name = `variants[${index}][${field}]`;
          }
        });
      });

      const rowCount = variantContainer.find('.variant-row').length;
      variantContainer.find('.btn-remove-variant').prop('disabled', false);
    }

    function initVariantRowFileMeta($row) {
      const $input = $row.find('input[type="file"][data-field="images"]');
      $input.off('change.wcVariant').on('change.wcVariant', function() {
        updateVariantRowImageMeta($row);
      });
      updateVariantRowImageMeta($row);
      // Also initialize preview for this row
      initVariantRowPreview($row);
    }

    function updateVariantRowImageMeta($row) {
      const $input = $row.find('input[type="file"][data-field="images"]');
      const $meta = $row.find('.variant-images-meta');
      const count = ($input[0]?.files || []).length;
      const existingCount = $row.find('input[data-field="existing_images"]').length;
      const totalCount = count + existingCount;
      $meta.text(totalCount ? `${totalCount} image(s) selected` : 'No files chosen');
    }

    $(document).on('change', '.variant-builder-value-select', function () {
      showVariantMessage('');
    });

    $(document).on('change', '.variant-attribute-toggle', function () {
      // Toggle is-active class on the parent label
      $(this).closest('.attribute-toggle-item').toggleClass('is-active', this.checked);

      const nextSelection = selectedAttributeIds();
      const hasRows = variantContainer.find('.variant-row').length > 0;
      const selectionChanged = JSON.stringify(nextSelection) !== JSON.stringify(selectedAttributeSnapshot);

      if (hasRows && selectionChanged) {
        const shouldReset = window.confirm('Changing selected attributes will clear the current variant rows. Continue?');
        if (!shouldReset) {
          this.checked = !this.checked;
          $(this).closest('.attribute-toggle-item').toggleClass('is-active', this.checked);
          return;
        }

        variantContainer.empty();
        reindexVariantCards();
      }

      clearVariantComposer();
      selectedAttributeSnapshot = selectedAttributeIds();
      updateSelectedAttributeCount();
      renderBuilderAttributeFields({});
    });

    $(document).on('click', '.attribute-toggle-item', function () {
      window.setTimeout(function () {
        updateSelectedAttributeCount();
        renderBuilderAttributeFields({});
      }, 0);
    });

    function variantCombinationKeyFromRows(attributeRows) {
      return (attributeRows || [])
        .map(function(row) {
          return Number(row.attribute_id || 0) + ':' + Number(row.value_id || 0);
        })
        .filter(function(part) {
          return part !== '0:0';
        })
        .sort()
        .join('|');
    }

    function variantCombinationExists(attributeRows, ignoreIndex) {
      const candidateKey = variantCombinationKeyFromRows(attributeRows);
      let exists = false;

      variantContainer.find('.variant-row').each(function(index) {
        if (ignoreIndex !== null && ignoreIndex !== undefined && Number(ignoreIndex) === index) {
          return;
        }

        const rowKey = variantCombinationKeyFromRows(
          buildAttributeRowsFromValueMap(readRowAttributeValueMap($(this)))
        );

        if (rowKey && rowKey === candidateKey) {
          exists = true;
          return false;
        }
      });

      return exists;
    }

    function copyBuilderFilesToVariantRow($row) {
      const $rowFileInput = $row.find('input[type="file"][data-field="images"]');
      const builderInput = variantComposer.images[0];

      if (!builderInput || !$rowFileInput.length) {
        return;
      }

      const dt = new DataTransfer();
      const selectedFiles = builderInput.files ? Array.from(builderInput.files) : [];
      for (let i = 0; i < selectedFiles.length; i++) {
        dt.items.add(selectedFiles[i]);
      }
      $rowFileInput[0].files = dt.files;
    }

    function renderVariantDraftRows() {
      if (!variantComposer.draftHost.length) return;
      const $body = variantComposer.draftHost.find('tbody');
      $body.empty();
      const attributePayload = readBuilderAttributePayload();
      if (attributePayload.error || !Array.isArray(attributePayload.combinations)) {
        variantComposer.draftHost.addClass('d-none');
        return;
      }

      attributePayload.combinations.forEach(function(combination, index) {
        const attrSummary = renderAttributeSummaryHtml(combination.rows);
        $body.append(
          '<tr data-draft-index="' + index + '">' +
            '<td>' + attrSummary + '</td>' +
            '<td><input type="text" class="form-control form-control-sm draft-sku" placeholder="SKU"></td>' +
            '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end draft-price" placeholder="0.00"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-primary draft-choose-images">Choose</button><input type="file" class="d-none draft-images" accept="image/*" multiple><span class="small text-muted ms-2 draft-image-meta">No files</span><div class="variant-images-preview d-flex flex-wrap gap-1 mt-1"></div></td>' +
          '</tr>'
        );
      });
      variantComposer.draftHost.toggleClass('d-none', attributePayload.combinations.length === 0);
    }

    function syncSelectedCombinationsToRows() {
      return;
    }

    function addVariantRowsFromBuilder() {
      const selectedAttrs = selectedAttributes();
      if (!selectedAttrs.length) {
        showVariantMessage('Please select at least one catalog attribute.');
        return false;
      }

      const attributePayload = readBuilderAttributePayload();
      if (attributePayload.error) {
        showVariantMessage(attributePayload.error);
        return false;
      }

      const commonPayload = {
        sku: String(variantComposer.sku.val() || '').trim(),
        price: String(variantComposer.price.val() || '').trim(),
        existing_images: variantBuilderMedia
          .filter(function(item) { return item.type === 'existing'; })
          .map(function(item) { return item.path; })
      };

      const files = variantComposer.images[0]?.files || [];

      const editIndex = variantComposer.editingIndex === null ? null : Number(variantComposer.editingIndex);
      const combinations = attributePayload.combinations || [];

      if (editIndex === null) {
        let addedCount = 0;
        let skippedCount = 0;

        combinations.forEach(function(combination) {
          if (variantCombinationExists(combination.rows, null)) {
            skippedCount++;
            return;
          }

          const index = variantContainer.find('.variant-row').length;
          const $draftRow = variantComposer.draftHost.find('tbody tr').eq(combinations.indexOf(combination));
          const payload = Object.assign({}, commonPayload, {
            sku: $draftRow.length ? String($draftRow.find('.draft-sku').val() || '').trim() : commonPayload.sku,
            price: $draftRow.length ? String($draftRow.find('.draft-price').val() || '').trim() : commonPayload.price,
            attribute_value_map: combination.valueMap,
            attribute_rows: combination.rows
          });
          const $row = $(variantRowTemplate(index, payload));
          variantContainer.append($row);
          if ($draftRow.length && $draftRow.find('.draft-images')[0]?.files?.length) {
            const dt = new DataTransfer();
            Array.from($draftRow.find('.draft-images')[0].files || []).forEach(function(file) { dt.items.add(file); });
            $row.find('input[type="file"][data-field="images"]')[0].files = dt.files;
          } else {
            copyBuilderFilesToVariantRow($row);
          }
          initVariantRowFileMeta($row);
          addedCount++;
        });

        if (addedCount === 0) {
          showVariantMessage(skippedCount ? 'All selected variant combinations already exist.' : 'No variant combinations were generated.');
          return false;
        }

        reindexVariantCards();
        clearVariantComposer();
        if (skippedCount > 0) {
          showVariantMessage('Skipped ' + skippedCount + ' duplicate combination(s).');
        }
        return true;
      }

      if (combinations.length !== 1) {
        showVariantMessage('Editing one row supports one checked value per selected attribute.');
        return false;
      }

      const payload = Object.assign({}, commonPayload, {
        attribute_value_map: combinations[0].valueMap,
        attribute_rows: combinations[0].rows
      });

      if (variantCombinationExists(payload.attribute_rows, editIndex)) {
        showVariantMessage('This variant combination already exists.');
        return false;
      }

      const $target = variantContainer.find('.variant-row').eq(editIndex);
      if (!$target.length) {
        clearVariantComposer();
        return false;
      }
      $target.find('.variant-attribute-hidden-host').html(renderAttributeHiddenInputs(editIndex, payload.attribute_rows));
      $target.find('.variant-value-attributes').html(renderAttributeSummaryHtml(payload.attribute_rows));
      $target.find('input[data-field="sku"]').val(payload.sku);
      $target.find('input[data-field="price"]').val(payload.price);
      clearVariantComposer();
      return true;
    }

    $('#add-variant-btn').on('click', function () {
      addVariantRowsFromBuilder();
    });

    $(document).on('click', '.draft-choose-images', function () {
      $(this).siblings('.draft-images').trigger('click');
    });

    $(document).on('change', '.draft-images', function () {
      const $row = $(this).closest('tr');
      const files = Array.from(this.files || []);
      $row.find('.draft-image-meta').text(files.length ? files.length + ' image(s) selected' : 'No files');
      const preview = $row.find('.variant-images-preview');
      preview.empty();
      files.forEach(function(file, index) {
        readFileAsDataURL(file, function(result) {
          preview.append('<div class="image-preview-card"><img src="' + result + '" alt="Draft image ' + (index + 1) + '"></div>');
        });
      });
    });

    $(document).on('click', '.btn-remove-variant', function () {
      $(this).closest('tr.variant-row').remove();
      reindexVariantCards();
    });

    $(document).on('click', '.btn-edit-variant', function () {
      const $row = $(this).closest('tr.variant-row');
      const index = Number($row.attr('data-index'));
      variantComposer.editingIndex = index;
      variantComposer.editState.text(`Editing row ${index + 1}`).removeClass('d-none');
      variantComposer.addBtn.text('Update');

      renderBuilderAttributeFields(readRowAttributeValueMap($row));
      variantComposer.sku.val($row.find('input[data-field="sku"]').val() || '');
      variantComposer.price.val($row.find('input[data-field="price"]').val() || '');
      showVariantMessage('To change images, use "Choose files" on the row.');
      $('html, body').animate({ scrollTop: $('#variant-section').offset().top - 90 }, 250);
    });

    $(document).on('click', '.btn-choose-variant-images', function () {
      const $row = $(this).closest('.variant-row');
      const $input = $row.find('input[type="file"][data-field="images"]');
      $input.trigger('click');
    });

    function addVariantExistingToBuilder(path) {
      var cleanPath = normalizeExistingGalleryPath(path);
      if (!cleanPath) return;
      var exists = variantBuilderMedia.some(function(item) {
        return item.type === 'existing' && normalizeExistingGalleryPath(item.path) === cleanPath;
      });
      if (!exists) {
        variantBuilderMedia.push({ type: 'existing', path: cleanPath });
      }
    }

    $('#open-variant-existing-gallery-btn').on('click', function() {
      $('.variant-existing-gallery-check').prop('checked', false);
      variantBuilderMedia
        .filter(function(item) { return item.type === 'existing'; })
        .forEach(function(item) {
          var itemPath = normalizeExistingGalleryPath(item.path);
          $('.variant-existing-gallery-check').filter(function() {
            return normalizeExistingGalleryPath($(this).val()) === itemPath;
          }).prop('checked', true);
        });
      if (variantExistingGalleryModal) {
        variantExistingGalleryModal.show();
      }
    });

    $('#apply-variant-existing-gallery-btn').on('click', function() {
      var selectedPaths = [];
      $('.variant-existing-gallery-check:checked').each(function() {
        var cleanPath = normalizeExistingGalleryPath($(this).val());
        if (cleanPath && !selectedPaths.includes(cleanPath)) {
          selectedPaths.push(cleanPath);
        }
      });
      selectedPaths.forEach(addVariantExistingToBuilder);
      $('.variant-existing-gallery-check').prop('checked', false);
      variantComposer.images.trigger('change');
      if (variantExistingGalleryModal) {
        variantExistingGalleryModal.hide();
      }
    });

    function toggleProductTypeSections() {
      const selectedType = $('input.js-product-type:checked').val() || 'simple';
      const isVariable = selectedType === 'variable';

      $('.js-variable-only').toggleClass('d-none', !isVariable);
      $('.js-simple-only').toggleClass('d-none', isVariable);

      $('.js-variable-only').find(':input').prop('disabled', !isVariable);
      $('.js-simple-only').find(':input').prop('disabled', isVariable);

      $('#product-submit-btn').text(isVariable ? 'Create Variable Product' : 'Create Product');
    }

    $('input.js-product-type').on('change', toggleProductTypeSections);

    $('#product-create-form').on('submit', function (event) {
      const isVariable = ($('input.js-product-type:checked').val() || 'simple') === 'variable';
      if (!isVariable) {
        return true;
      }

      const selectedAttrs = selectedAttributeIds();
      let rowCount = variantContainer.find('.variant-row').length;

      if (!selectedAttrs.length) {
        event.preventDefault();
        showVariantMessage('Please select at least one catalog attribute.');
        $('html, body').animate({ scrollTop: $('#variant-section').offset().top - 90 }, 250);
        return false;
      }

      if (!rowCount) {
        event.preventDefault();
        if (!$.trim(variantComposer.message.text())) {
          showVariantMessage('Add at least one variant row before saving.');
        }
        $('html, body').animate({ scrollTop: $('#variant-section').offset().top - 90 }, 250);
        return false;
      }

      let firstInvalidMessage = '';
      variantContainer.find('.variant-row').each(function (index) {
        const $row = $(this);
        const attributeCount = $row.find('input[data-field="attribute_value_map"]').length;
        const price = Number($row.find('input[data-field="price"]').val() || 0);
        const uploadCount = ($row.find('input[type="file"][data-field="images"]')[0]?.files || []).length;
        const existingCount = $row.find('input[data-field="existing_images"]').length;

        if (attributeCount !== selectedAttrs.length) {
          firstInvalidMessage = 'Row ' + (index + 1) + ' needs one value for every selected attribute.';
          return false;
        }

        if (!Number.isFinite(price) || price <= 0) {
          firstInvalidMessage = 'Row ' + (index + 1) + ' needs a valid price.';
          return false;
        }

        if (uploadCount === 0 && existingCount === 0) {
          firstInvalidMessage = 'Row ' + (index + 1) + ' needs at least one uploaded or existing image.';
          return false;
        }
      });

      if (firstInvalidMessage) {
        event.preventDefault();
        showVariantMessage(firstInvalidMessage);
        $('html, body').animate({ scrollTop: $('#variant-section').offset().top - 90 }, 250);
        return false;
      }

      return true;
    });

    // Helper to reinitialize Select2 on a select element after dynamic option changes
    function reinitSelect2($el) {
      if ($el.data('select2')) {
        $el.select2('destroy');
      }
      $el.select2({
        width: '100%',
        placeholder: 'Select an option',
        allowClear: false
      });
    }

    $('#category_id').on('change', function () {
      const categoryId = $(this).val();
      const subcategorySelect = $('#subcategory_id');
      const childcategorySelect = $('#childcategory_id');

      // Reset both dependent dropdowns
      subcategorySelect.empty().append('<option value="">Select sub category</option>');
      childcategorySelect.empty().append('<option value="">Select child category</option>');
      reinitSelect2(subcategorySelect);
      reinitSelect2(childcategorySelect);

      if (!categoryId) {
        return;
      }

      $.ajax({
        type: 'GET',
        url: "{{ url('ajax-product-subcategory') }}",
        data: { category_id: categoryId },
        success: function (response) {
          subcategorySelect.empty().append('<option value="">Select sub category</option>');
          $.each(response, function (id, name) {
            subcategorySelect.append($('<option>', { value: id, text: name }));
          });
          reinitSelect2(subcategorySelect);
        },
        error: function (xhr, status, error) {
          console.error('Failed to load subcategories:', status, error);
        }
      });
    });

    $('#subcategory_id').on('change', function () {
      const subcategoryId = $(this).val();
      const childcategorySelect = $('#childcategory_id');

      // Reset child category dropdown
      childcategorySelect.empty().append('<option value="">Select child category</option>');
      reinitSelect2(childcategorySelect);

      if (!subcategoryId) {
        return;
      }

      $.ajax({
        type: 'GET',
        url: "{{ url('ajax-product-childcategory') }}",
        data: { subcategory_id: subcategoryId },
        success: function (response) {
          childcategorySelect.empty().append('<option value="">Select child category</option>');
          $.each(response, function (id, name) {
            childcategorySelect.append($('<option>', { value: id, text: name }));
          });
          reinitSelect2(childcategorySelect);
        },
        error: function (xhr, status, error) {
          console.error('Failed to load child categories:', status, error);
        }
      });
    });

     variantContainer.find('.variant-row').each(function() {
       initVariantRowFileMeta($(this));
     });

     reindexVariantCards();
     clearVariantComposer();
     selectedAttributeSnapshot = selectedAttributeIds();
     updateSelectedAttributeCount();
     toggleProductTypeSections();

   });
 </script>
@endsection
