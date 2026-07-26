<?php

function format_currency(float $value, string $symbol = '$', int $decimals = 2): string {
    return $symbol . number_format($value, $decimals);
}

function format_number(float $value, int $decimals = 2): string {
    return number_format($value, $decimals);
}

?>


