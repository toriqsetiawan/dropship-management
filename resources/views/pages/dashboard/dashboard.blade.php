<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Dashboard actions -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ __('dashboard.title') }}</h1>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">

                <!-- Filter button -->
                <x-dropdown-filter align="right" />

                <!-- Datepicker built with flatpickr -->
                <x-datepicker />

                <!-- Add view button -->
                <button class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                    <svg class="fill-current shrink-0 xs:hidden" width="16" height="16" viewBox="0 0 16 16">
                        <path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                  </svg>
                  <span class="max-xs:sr-only">{{ __('dashboard.add_view') }}</span>
                </button>

            </div>

        </div>

        <!-- Cards -->
        <div class="grid grid-cols-12 gap-6">

            <!-- Line chart (Acme Plus) -->
            <x-dashboard.dashboard-card-01 :dataFeed="$dataFeed" :title="__('dashboard.cards.acme_plus')" />

            <!-- Line chart (Acme Advanced) -->
            <x-dashboard.dashboard-card-02 :dataFeed="$dataFeed" :title="__('dashboard.cards.acme_advanced')" />

            <!-- Line chart (Acme Professional) -->
            <x-dashboard.dashboard-card-03 :dataFeed="$dataFeed" :title="__('dashboard.cards.acme_professional')" />

            <!-- Bar chart (Direct vs Indirect) -->
            <x-dashboard.dashboard-card-04 :title="__('dashboard.cards.direct_vs_indirect')" />

            <!-- Line chart (Real Time Value) -->
            <x-dashboard.dashboard-card-05 :title="__('dashboard.cards.real_time_value')" />

            <!-- Doughnut chart (Top Countries) -->
            <x-dashboard.dashboard-card-06 :title="__('dashboard.cards.top_countries')" />

            <!-- Table (Top Channels) -->
            <x-dashboard.dashboard-card-07 :title="__('dashboard.cards.top_channels')" />

            <!-- Line chart (Sales Over Time) -->
            <x-dashboard.dashboard-card-08 :title="__('dashboard.cards.sales_over_time')" />

            <!-- Stacked bar chart (Sales VS Refunds) -->
            <x-dashboard.dashboard-card-09 :title="__('dashboard.cards.sales_vs_refunds')" />

            <!-- Card (Customers) -->
            <x-dashboard.dashboard-card-10 :title="__('dashboard.cards.customers')" />

            <!-- Card (Reasons for Refunds) -->
            <x-dashboard.dashboard-card-11 :title="__('dashboard.cards.refund_reasons')" />

            <!-- Card (Recent Activity) -->
            <x-dashboard.dashboard-card-12 :title="__('dashboard.cards.recent_activity')" />

            <!-- Card (Income/Expenses) -->
            <x-dashboard.dashboard-card-13 :title="__('dashboard.cards.income_expenses')" />

        </div>

    </div>
</x-app-layout>
