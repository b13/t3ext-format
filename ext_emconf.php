<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'External Format Services for PDF, Excel and CSV creation',
    'description' => 'Includes simple service classes to create CSV, Excel and PDF files.',
    'category' => 'misc',
    'author' => 'Benjamin Mack, Daniel Goerz',
    'author_email' => 'benjamin.mack@b13.com, daniel.goerz@b13.com',
    'state' => 'stable',
    'author_company' => 'b13, Stuttgart',
    'version' => '2.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-11.5.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
