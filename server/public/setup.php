<?php

error_reporting(E_ALL);

ini_set('display_errors', true);

$alerts = [
    'danger' => [],
    'warning' => [],
    'info' => [],
    'success' => [],
];

$installed = false;

function alert($type, $message)
{
    global $alerts;

    if (!array_key_exists($type, $alerts)) {
        trigger_error('Invalid alert type provided: ' . $type);
    }

    $alerts[$type][] = $message;
}

function requirements()
{
    if (file_exists(__DIR__ . '/../.env')) {
        alert('warning', 'The application appears to be already configured (the environment file exists).');
    }

    if (!is_writable(__DIR__ . '/../')) {
        alert('warning', 'The root directory of the application is not writable, please make sure that it has the right permissions: ' . realpath(__DIR__ . '/../'));
    }

    if (!isset($_SERVER['SCRIPT_FILENAME'])) {
        alert('warning', 'The script filename server variable $_SERVER["SCRIPT_FILENAME"] is not set, please make sure the server is configured correctly.');
    }

    if (!isset($_SERVER['PHP_SELF'])) {
        alert('warning', 'The script filename server variable $_SERVER["PHP_SELF"] is not set, please make sure the server is configured correctly.');
    }

    if (!extension_loaded('curl')) {
        alert('warning', 'The cURL PHP extension is not present on the server, please install it and try again.');
    }

    if (!extension_loaded('zip')) {
        alert('warning', 'The Zip PHP extension is not present on the server, please install it and try again.');
    }
}

function location()
{
    return $_SERVER['HTTP_ORIGIN'] . dirname($_SERVER['PHP_SELF']);
}

function environment()
{
    $path = __DIR__ . '/../.env';

    if (file_exists($path)) {
        return true;
    }

    $env = file_get_contents(__DIR__ . '/../.env.example');

    file_put_contents($path, strtr($env, [
        '{KEY}' => strtoupper(md5(time())),
        '{URL}' => location(),
        '{DB_HOST}' => $_POST['db_host'],
        '{DB_NAME}' => $_POST['db_name'],
        '{DB_USERNAME}' => $_POST['db_username'],
        '{DB_PASSWORD}' => $_POST['db_password'],
    ]));

    if (!file_exists($path)) {
        alert('danger', 'The environment file could not be created.');
        return false;
    }

    return true;
}

function migrations()
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, location() . '/api.php/v1');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_exec($ch);
    if (curl_errno($ch)) {
        alert('danger', 'The application migrations failed to execute: ' . curl_error($ch));
        return false;
    }
    curl_close($ch);
    return true;
}

function install()
{
    if (!environment()) {
        return false;
    }

    if (!migrations()) {
        return false;
    }

    alert('success', 'The application was installed successfully, visit <a href="' . location() . '">'
        . str_replace(['http://', 'https://'], '', location()) . '</a> and login with admin@example.org / 12345');

    alert('info', 'You can now remove the setup.php file from the server.');

    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $installed = install();
}

if (!$installed) {
    requirements();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="Alex Tselegidis">
    <title>Installation · Plainpad</title>

    <link rel="shortcut icon" href="favicon.ico">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
          integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

    <link href="https://fonts.googleapis.com/css?family=Roboto+Mono&display=swap" rel="stylesheet">

    <style>
        .bd-placeholder-img {
            font-size: 1.125rem;
            text-anchor: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        @media (min-width: 768px) {
            .bd-placeholder-img-lg {
                font-size: 3.5rem;
            }
        }

        html,
        body {
            height: 100%;
        }

        body {
            display: -ms-flexbox;
            display: flex;
            -ms-flex-align: center;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
            font-family: 'Roboto Mono', 'Roboto', 'Helvetica', sans-serif;
        }

        .alert small {
            word-break: break-all;
        }

        .form-install {
            width: 100%;
            max-width: 420px;
            padding: 15px;
            margin: auto;
        }

        .form-label-group {
            position: relative;
            margin-bottom: 1rem;
        }

        .form-label-group > input,
        .form-label-group > label {
            height: 3.125rem;
            padding: .75rem;
        }

        .form-label-group > input {
            background: white;
            border-radius: 0;
        }

        .form-label-group > input:hover {
            box-shadow: none;
        }

        .form-label-group > label {
            position: absolute;
            top: 0;
            left: 0;
            display: block;
            width: 100%;
            margin-bottom: 0; /* Override default `<label>` margin */
            line-height: 1.5;
            color: #495057;
            pointer-events: none;
            cursor: text; /* Match the input under the label */
            border: 1px solid transparent;
            border-radius: .25rem;
            transition: all .1s ease-in-out;
        }

        .form-label-group input::-webkit-input-placeholder {
            color: transparent;
        }

        .form-label-group input:-ms-input-placeholder {
            color: transparent;
        }

        .form-label-group input::-ms-input-placeholder {
            color: transparent;
        }

        .form-label-group input::-moz-placeholder {
            color: transparent;
        }

        .form-label-group input::placeholder {
            color: transparent;
        }

        .form-label-group input:not(:placeholder-shown) {
            padding-top: 1.25rem;
            padding-bottom: .25rem;
        }

        .form-label-group input:not(:placeholder-shown) ~ label {
            padding-top: .25rem;
            padding-bottom: .25rem;
            font-size: 12px;
            color: #777;
        }

        .checkbox input {
            position: relative;
            top: 2px;
        }

        .btn-primary {
            background-color: #564b65;
            border-color: #564b65;
            border-radius: 0;
        }

        .btn-primary:hover,
        .btn-primary:active,
        .btn-primary:focus {
            background-color: #322b3b !important;
            border-color: #322b3b !important;
            box-shadow: none;
        }

        /* Fallback for Edge
        -------------------------------------------------- */
        @supports (-ms-ime-align: auto) {
            .form-label-group > label {
                display: none;
            }

            .form-label-group input::-ms-input-placeholder {
                color: #777;
            }
        }

        /* Fallback for IE
        -------------------------------------------------- */
        @media all and (-ms-high-contrast: none), (-ms-high-contrast: active) {
            .form-label-group > label {
                display: none;
            }

            .form-label-group input:-ms-input-placeholder {
                color: #777;
            }
        }
    </style>
</head>
<body>
<form class="form-install" method="post">
    <div class="text-center mb-4">
        <img class="mb-4" src="logo.png" alt="Plainpad Logo" width="72" height="72">

        <h1 class="h3 mb-3 font-weight-normal">
            Plainpad
        </h1>

        <p class="text-muted">
            Welcome To The Installation Page
        </p>
    </div>

    <?php foreach ($alerts as $type => $messages): ?>
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-<?= $type ?>">
                <small>
                    <?= $message ?>
                </small>
            </div>
        <?php endforeach ?>
    <?php endforeach ?>

    <div <?= $installed ? 'hidden' : '' ?>>
        <h5 class="mb-4">Database</h5>

        <div class="form-label-group">
            <input name="db_host" id="db-host" class="form-control" placeholder="localhost" required autofocus
                   value="<?= $_POST['db_host'] ?? '' ?>">
            <label for="db-host">Host</label>
        </div>

        <div class="form-label-group">
            <input name="db_name" id="db-name" class="form-control" placeholder="plainpad" required
                   value="<?= $_POST['db_name'] ?? '' ?>">
            <label for="db-name">Name</label>
        </div>

        <div class="form-label-group">
            <input name="db_username" id="db-username" class="form-control" placeholder="root" required
                   autocomplete="new-username" value="<?= $_POST['db_username'] ?? '' ?>">
            <label for="db-username">Username</label>
        </div>

        <div class="form-label-group">
            <input name="db_password" id="db-password" class="form-control" type="password" placeholder="root" required
                   autocomplete="new-password">
            <label for="db-password">Password</label>
        </div>

        <div class="checkbox mb-4">
            <small>
                <label>
                    <input type="checkbox" value="agree-to-terms" required> Plainpad is licensed under the
                    <a href="https://www.gnu.org/licenses/gpl-3.0.en.html" target="_blank">GPL-3.0</a>
                    license, by installing and using Plainpad I agree to the terms and conditions of this license.
                </label>
            </small>
        </div>

        <button class="btn btn-lg btn-primary btn-block" type="submit">Install</button>
    </div>

    <p class="mt-4 mb-4 text-muted text-center">
        <small>
            Plainpad &copy; <?= date('Y') ?>
        </small>
    </p>
</form>
</body>
</html>

