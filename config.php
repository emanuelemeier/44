<?php
// ============================================================
//  BIRTHDAY VERTICAL CHALLENGE - Config
//  Modifica qui tutti i dettagli dell'evento
// ============================================================

return [

    // --- Host ---
    'host_name'        => 'Emanuele',

    // --- Evento ---
    'event_name'       => 'Birthday Vertical Challenge',
    'event_date'       => 'Sabato 2 Maggio 2026',
    'distance'         => '~24km &bull; ~1800m D+',
    'route'            => '6&times; Selma &mdash; Landarenca',

    // --- Luoghi ---
    'location_bottom'  => 'Selma (800m)',
    'location_top'     => 'Landarenca (1260m)',

    // --- Orari ---
    'time_start'       => '7:45',          // partenza da Landarenca (6 salite)
    'time_join'        => '8:00',          // dalle X si puo unirsi
    'time_bbq'         => '12:00',         // grigliata in vetta

    // --- Slot salite (partenza da Selma, 25 min salita + 15 min discesa) ---
    'climb_slots' => [
        1 => ['label' => 'Salita 1', 'depart' => '8:00',  'arrive' => '8:25'],
        2 => ['label' => 'Salita 2', 'depart' => '8:40',  'arrive' => '9:05'],
        3 => ['label' => 'Salita 3', 'depart' => '9:20',  'arrive' => '9:45'],
        4 => ['label' => 'Salita 4', 'depart' => '10:00', 'arrive' => '10:25'],
        5 => ['label' => 'Salita 5', 'depart' => '10:40', 'arrive' => '11:05'],
        6 => ['label' => 'Salita 6', 'depart' => '11:20', 'arrive' => '11:45'],
    ],

    // --- Regole birre ---
    'beer_rules'       => [
        '1 salita = 1 birra',
        '2 salite = 2 birre',
        '3+ salite = gloria eterna',
    ],

    // --- Admin ---
    'admin_password'   => 'birra44',       // cambia questa password!

    // --- File dati (non toccare) ---
    'data_file'        => __DIR__ . '/data/responses.json',
];
