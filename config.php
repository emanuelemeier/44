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
    'distance'         => '44km &bull; 3300m D+',
    'route'            => '11&times; Selma &mdash; Landarenca',

    // --- Luoghi ---
    'location_bottom'  => 'Selma (800m)',
    'location_top'     => 'Landarenca (1260m)',

    // --- Orari ---
    'time_start'       => '4:00',          // partenza epica
    'time_join'        => '8:00',          // dalle X si puo unirsi
    'time_bbq'         => '12:00',         // grigliata in vetta

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
