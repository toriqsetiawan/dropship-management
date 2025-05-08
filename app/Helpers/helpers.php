<?php

if (!function_exists('is_distributor_or_admin')) {
    function is_distributor_or_admin($user)
    {
        return $user && $user->hasRole(['distributor', 'admin', 'administrator']);
    }
}

if (!function_exists('is_distributor')) {
    function is_distributor($user)
    {
        return $user && $user->hasRole(['distributor']);
    }
}

if (!function_exists('is_admin')) {
    function is_admin($user)
    {
        return $user && $user->hasRole(['admin', 'administrator']);
    }
}

if (!function_exists('is_reseller')) {
    function is_reseller($user)
    {
        return $user && $user->hasRole(['reseller']);
    }
}
