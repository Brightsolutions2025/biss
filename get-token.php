<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_DRIVE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_DRIVE_CLIENT_SECRET']);
$client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
$client->addScope(Google_Service_Drive::DRIVE);
$client->setAccessType('offline');
$client->setPrompt('consent');

$authUrl = $client->createAuthUrl();
echo "Open the following link in your browser:\n$authUrl\n\n";
echo 'Enter verification code: ';
$authCode = trim(fgets(STDIN));

$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

if (isset($accessToken['error'])) {
    echo "Error retrieving access token:\n";
    print_r($accessToken);
    exit(1);
}

if (isset($accessToken['refresh_token'])) {
    echo 'Refresh Token: ' . $accessToken['refresh_token'] . "\n";
} else {
    echo "No refresh token received.\n";
}
