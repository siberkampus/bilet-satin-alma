<?php
function control_input($data)
{
    $data = trim($data);
    $data = stripcslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


function formatDateTime($dateTimeString)
{
    $timestamp = strtotime($dateTimeString);


    $months = [
        1 => 'Ocak',
        2 => 'Şubat',
        3 => 'Mart',
        4 => 'Nisan',
        5 => 'Mayıs',
        6 => 'Haziran',
        7 => 'Temmuz',
        8 => 'Ağustos',
        9 => 'Eylül',
        10 => 'Ekim',
        11 => 'Kasım',
        12 => 'Aralık'
    ];

    $days = [
        'Sunday' => 'Pazar',
        'Monday' => 'Pazartesi',
        'Tuesday' => 'Salı',
        'Wednesday' => 'Çarşamba',
        'Thursday' => 'Perşembe',
        'Friday' => 'Cuma',
        'Saturday' => 'Cumartesi'
    ];

    $dayOfWeek = date('l', $timestamp);
    $day = date('j', $timestamp);
    $month = date('n', $timestamp);
    $year = date('Y', $timestamp);
    $time = date('H:i', $timestamp);

    return [
        'full' => "$day {$months[$month]} $year, {$days[$dayOfWeek]} - $time",
        'short' => "$day {$months[$month]} $year - $time",
        'date' => "$day {$months[$month]} $year",
        'time' => $time,
        'day_month' => "$day {$months[$month]}",
        'day_of_week' => $days[$dayOfWeek]
    ];
}

