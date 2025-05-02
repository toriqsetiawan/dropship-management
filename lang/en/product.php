<?php

return [
    'title' => 'Products',
    'create_title' => 'Create New Product',
    'edit_title' => 'Edit Product',
    'delete_title' => 'Delete Product',
    'search_placeholder' => 'Search products...',
    'select_supplier' => 'Select a supplier',
    'no_products' => 'No products found',
    'variant_count' => 'variants',
    'all_products' => 'All Products',
    'discount' => 'DISCOUNT',

    'sections' => [
        'basic_info' => 'Basic Information',
        'basic_info_description' => 'Enter the basic product information and pricing.',
        'attributes' => 'Product Attributes',
        'attributes_description' => 'Select attributes to generate product variants.',
        'variants' => 'Product Variants',
        'add_attribute' => 'Add New Attribute',
        'price_group' => 'Price Group',
    ],

    'fields' => [
        'name' => 'Product Name',
        'supplier' => 'Supplier',
        'factory_price' => 'Factory Price',
        'distributor_price' => 'Distributor Price',
        'reseller_price' => 'Reseller Price',
        'retail_price' => 'Retail Price',
        'price' => 'Price',
        'discount_price' => 'Discount Price',
        'variants' => 'Variants',
        'sku' => 'SKU',
        'stock' => 'Stock',
        'created_at' => 'Created At',
        'attribute_name' => 'Attribute Name',
        'attribute_type' => 'Attribute Type',
        'attribute_values' => 'Attribute Values',
        'bulk_price' => 'Bulk Price Update',
        'bulk_stock' => 'Bulk Stock Update',
        'bulk_sku_prefix' => 'Bulk SKU Prefix',
    ],

    'attribute_types' => [
        'text' => 'Text',
        'color' => 'Color',
    ],

    'placeholders' => [
        'attribute_name' => 'e.g. Size, Color',
        'attribute_values' => 'e.g. S, M, L, XL or 36, 37, 38, 39, 40',
        'bulk_price' => 'Enter price in Rp',
        'bulk_sku' => 'Enter SKU prefix',
    ],

    'tooltips' => [
        'stock' => 'Enter the available stock quantity for this variant',
    ],

    'messages' => [
        'created' => 'Product has been created successfully.',
        'updated' => 'Product has been updated successfully.',
        'deleted' => 'Product has been deleted successfully.',
        'delete_confirm' => 'Are you sure you want to delete',
        'max_attributes' => 'You can select maximum 2 attributes.',
        'max_attributes_reached' => 'Maximum number of attributes (2) has been reached.',
        'no_attributes' => 'No attributes available. Add some attributes first.',
        'attribute_values_help' => 'Enter values separated by commas',
    ],

    'actions' => [
        'create' => 'Create New Product',
        'edit' => 'Edit',
        'delete' => 'Delete Product',
        'add_attribute' => 'Add Attribute',
    ],

    'delete_warning' => 'This action cannot be undone. This will permanently delete the product and all its variants.',
];
