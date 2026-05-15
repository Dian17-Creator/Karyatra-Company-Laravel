<?php

if (!function_exists('rp')) {
    function rp($n)
    {
        return 'Rp ' . number_format((float) $n, 0, ',', '.');
    }
}
