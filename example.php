<?php

require 'vendor/autoload.php';

use function Laravel\Prompts\{text, select, confirm, info, multiselect};

info("The Spin logo!");

info("🚀 Let's get Laravel launched!");

$phpVersion = select(
    label: '👉 What PHP version would you like to use?',
    options: [
        '8.4' => 'PHP 8.4 (Latest)',
        '8.3' => 'PHP 8.3',
        '8.2' => 'PHP 8.2'
    ]
);

$email = text(
    label: '🤖 Server Contact',
    placeholder: 'test@example.com',
    required: true,
    validate: fn (string $value) => match (true) {
        !filter_var($value, FILTER_VALIDATE_EMAIL) => 'Please enter a valid email address.',
        strlen($value) > 255 => 'The email must not exceed 255 characters.',
        default => null
    },
    hint: "Set an email contact who should be notified for Let's Encrypt SSL renewals and other system alerts."
);

info("What PHP extensions would you like to include?

Default extensions:
ctype, curl, dom, fileinfo, filter, hash, mbstring, mysqli,
opcache, openssl, pcntl, pcre, pdo_mysql, pdo_pgsql, redis,
session, tokenizer, xml, zip

See available extensions:
https://serversideup.net/docker-php/available-extensions

Enter additional extensions as a comma-separated list (no spaces).
Example: gd,imagick,intl");

$extensions = text(
    label: 'Enter comma separated extensions below or press ENTER to use default extensions.'
);

$services = multiselect(
    label: "Select which Laravel features you'd like to use:",
    options: [
        'scheduler' => 'Task Scheduling',
        'horizon' => 'Horizon',
        'queues' => 'Queues',
        'reverb' => 'Reverb',
    ],
    default: ['read', 'create']
);

$package = select(
  label: "Choose your JavaScript package manager:",
  options: [
      'yarn' => 'Yarn',
      'npm' => 'NPM',
    ]
);

$database = multiselect(
    label: "What database engine(s) would you like to use?",
    options: [
        'sqlite' => 'SQLite',
        'mysql' => 'MySQL',
        'mariadb' => 'MariaDB',
        'postgres' => 'PostgreSQL',
        'redis' => 'Redis'
    ],
    required: true,
);

$githubAction = confirm("Would you like to use GitHub Actions?");

echo json_encode([
    'phpVersion' => $phpVersion,
    'email' => $email,
    'extensions' => explode(',', $extensions),
    'services' => $services,
    'package' => $package,
    'database' => $database,
    'githubAction' => $githubAction,
    'timestamp' => date('Y-m-d H:i:s')
]);
