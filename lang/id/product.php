<?php

return [
    'title' => 'Produk',
    'create_title' => 'Buat Produk Baru',
    'edit_title' => 'Ubah Produk',
    'delete_title' => 'Hapus Produk',
    'search_placeholder' => 'Cari produk...',
    'select_supplier' => 'Pilih pemasok',
    'no_products' => 'Tidak ada produk ditemukan',
    'variant_count' => 'varian',
    'all_products' => 'Semua Produk',
    'discount' => 'DISKON',

    'sections' => [
        'basic_info' => 'Informasi Dasar',
        'basic_info_description' => 'Masukkan informasi dasar dan harga produk.',
        'attributes' => 'Varian Produk',
        'attributes_description' => 'Pilih varian untuk menghasilkan varian produk.',
        'variants' => 'Varian Produk',
        'add_attribute' => 'Tambah Varian Baru',
        'price_group' => 'Kelompok Harga',
    ],

    'fields' => [
        'name' => 'Nama Produk',
        'supplier' => 'Pemasok',
        'factory_price' => 'Harga Pabrik',
        'distributor_price' => 'Harga Distributor',
        'reseller_price' => 'Harga Reseller',
        'retail_price' => 'Harga Eceran',
        'price' => 'Harga',
        'discount_price' => 'Harga Diskon',
        'variants' => 'Varian',
        'sku' => 'SKU',
        'stock' => 'Stok',
        'created_at' => 'Dibuat Pada',
        'attribute_name' => 'Nama Varian',
        'attribute_type' => 'Tipe Varian',
        'attribute_values' => 'Nilai Varian',
        'bulk_price' => 'Update Harga Massal',
        'bulk_stock' => 'Update Stok Massal',
        'bulk_sku_prefix' => 'Awalan SKU Massal',
        'image' => 'Gambar',
    ],

    'attribute_types' => [
        'text' => 'Teks',
        'color' => 'Warna',
    ],

    'placeholders' => [
        'attribute_name' => 'contoh: Ukuran, Warna',
        'attribute_values' => 'contoh: S, M, L, XL atau 36, 37, 38, 39, 40',
        'bulk_price' => 'Masukkan harga dalam Rp',
        'bulk_sku' => 'Masukkan awalan SKU',
    ],

    'tooltips' => [
        'stock' => 'Masukkan jumlah stok yang tersedia untuk varian ini',
    ],

    'messages' => [
        'created' => 'Produk berhasil dibuat.',
        'updated' => 'Produk berhasil diperbarui.',
        'deleted' => 'Produk berhasil dihapus.',
        'delete_confirm' => 'Apakah Anda yakin ingin menghapus',
        'max_attributes' => 'Anda dapat memilih maksimal 2 varian.',
        'max_attributes_reached' => 'Jumlah maksimal varian (2) telah tercapai.',
        'no_attributes' => 'Tidak ada varian tersedia. Tambahkan beberapa varian terlebih dahulu.',
        'attribute_values_help' => 'Masukkan nilai dipisahkan dengan koma',
    ],

    'actions' => [
        'create' => 'Buat Produk Baru',
        'edit' => 'Ubah',
        'delete' => 'Hapus',
        'add_attribute' => 'Tambah Varian',
    ],

    'delete_warning' => 'Tindakan ini tidak dapat dibatalkan. Ini akan menghapus produk dan semua variannya secara permanen.',
];
