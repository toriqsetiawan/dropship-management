@props(['name', 'class' => ''])

@switch($name)
    @case('dashboard')
        <i class="fas fa-tachometer-alt {{ $class }}"></i>
        @break

    @case('supplier')
        <i class="fas fa-boxes {{ $class }}"></i>
        @break

    @case('ecommerce')
        <i class="fas fa-shopping-cart {{ $class }}"></i>
        @break

    @case('community')
        <i class="fas fa-users {{ $class }}"></i>
        @break

    @case('chevron-down')
        <i class="fas fa-chevron-down {{ $class }}"></i>
        @break

    @case('close')
        <i class="fas fa-arrow-left {{ $class }}"></i>
        @break

    @case('finance')
        <i class="fas fa-wallet {{ $class }}"></i>
        @break

    @case('job')
        <i class="fas fa-briefcase {{ $class }}"></i>
        @break

    @case('tasks')
        <i class="fas fa-tasks {{ $class }}"></i>
        @break

    @case('messages')
        <i class="fas fa-envelope {{ $class }}"></i>
        @break

    @case('inbox')
        <i class="fas fa-inbox {{ $class }}"></i>
        @break

    @case('calendar')
        <i class="fas fa-calendar {{ $class }}"></i>
        @break

    @case('campaigns')
        <i class="fas fa-bullhorn {{ $class }}"></i>
        @break

    @case('settings')
        <i class="fas fa-cog {{ $class }}"></i>
        @break

    @case('utility')
        <i class="fas fa-tools {{ $class }}"></i>
        @break

    @case('authentication')
        <i class="fas fa-lock {{ $class }}"></i>
        @break

    @case('onboarding')
        <i class="fas fa-user-plus {{ $class }}"></i>
        @break

    @case('components')
        <i class="fas fa-cube {{ $class }}"></i>
        @break

    @default
        <i class="fas fa-circle {{ $class }}"></i>
@endswitch
