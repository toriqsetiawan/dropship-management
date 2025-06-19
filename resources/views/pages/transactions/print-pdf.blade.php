<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('common.transaction.print_pdf_title') }} - {{ $transaction->transaction_code }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .print-container {
                width: 100% !important;
                height: 100vh !important;
            }
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Header -->
        <div class="no-print bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">
                            {{ __('common.transaction.print_pdf_title') }} - {{ $transaction->transaction_code }}
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        {{-- <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fa-solid fa-print mr-2"></i>
                            {{ __('common.transaction.print_pdf_title') }}
                        </button> --}}
                        <button onclick="window.close()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fa-solid fa-times mr-2"></i>
                            {{ __('common.actions.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PDF Container -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden print-container">
                <!-- Transaction Info -->
                <div class="no-print bg-gray-50 px-6 py-4 border-b">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-700">{{ __('common.transaction.code') }}:</span>
                            <span class="text-gray-900">{{ $transaction->transaction_code }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">{{ __('common.transaction.shipping_number') }}:</span>
                            <span class="text-gray-900">{{ $transaction->shipping_number }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">{{ __('common.transaction.status') }}:</span>
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                {{ $transaction->status === 'pending' ? 'bg-yellow-200 text-yellow-800' : '' }}
                                {{ $transaction->status === 'processed' ? 'bg-blue-200 text-blue-800' : '' }}
                                {{ $transaction->status === 'packed' ? 'bg-purple-200 text-purple-800' : '' }}
                                {{ $transaction->status === 'shipped' ? 'bg-green-200 text-green-800' : '' }}">
                                {{ __('common.transaction.status_' . $transaction->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- PDF Viewer -->
                <div class="w-full" style="height: 80vh;">
                    <iframe
                        src="{{ $pdfPath }}"
                        width="100%"
                        height="100%"
                        frameborder="0"
                        class="border-0"
                    ></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>
</html>
