<?php

// *** Source Formatting
// $ php-cs-fixer fix --rules @PSR2,@Symfony version_info.php
//
// *** Cron
// $ crontab -l
// @hourly	/usr/bin/php /home/fkooman/version_info.php > /var/www/html/fkooman/version_info.html.tmp && mv /var/www/html/fkooman/version_info.html.tmp /var/www/html/fkooman/version_info.html

// set this to the latest version of vpn-user-portal
// @see https://github.com/eduvpn/vpn-user-portal/releases
$latestVersion = '2.1.4';

// discovery files
$discoFiles = [
    'secure_internet' => 'https://static.eduvpn.nl/disco/secure_internet.json',
    'institute_access' => 'https://static.eduvpn.nl/disco/institute_access.json',
];

// other servers not part of any discovery file
$otherServerList = [
    'https://vpn.tuxed.net/',
    'https://vpn-dev.tuxed.net/',
    'https://meko.eduvpn.nl/',
    'https://vpn.spoor.nu/',
];

$streamContext = stream_context_create(
    [
        'http' => [
            'timeout' => 5,
        ],
    ]
);

$serverList = [];
// extract the "base_uri" from all discovery files
foreach ($discoFiles as $serverType => $discoFile) {
    if (!array_key_exists($serverType, $serverList)) {
        $serverList[$serverType] = [];
    }
    if (false === $discoJson = @file_get_contents($discoFile, false, $streamContext)) {
        continue;
    }
    $discoData = json_decode($discoJson, true);
    foreach ($discoData['instances'] as $serverInstance) {
        $serverList[$serverType][] = $serverInstance['base_uri'];
    }
}
// add the other servers to the list as well
$serverList['other'] = $otherServerList;

// now retrieve the info.json file from all servers
$serverVersionList = [];
foreach ($serverList as $serverType => $serverList) {
    foreach ($serverList as $baseUri) {
        //echo '*** '.$baseUri.PHP_EOL;
        if (false === $infoJson = @file_get_contents($baseUri.'info.json', false, $streamContext)) {
            $serverVersionList[$baseUri] = null;
            continue;
        }
        $infoData = json_decode($infoJson, true);
        $serverVersion = array_key_exists('v', $infoData) ? $infoData['v'] : 'N/A';
        $serverVersionList[$baseUri] = $serverVersion;
    }
}

$dateTime = new DateTime();
?>
<!DOCTYPE html>

<html lang="en-US" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>eduVPN Server Info</title>
    <style>
body {
    font-family: sans-serif;
    max-width: 50em;
    margin: 1em auto;
    color: #444;
}

h1 {
    text-align: center;
}

table {
    border: 1px solid #ccc;
    width: 100%;
    border-collapse: collapse;
}

table th, table td {
    padding: 0.8em 0.5em;
}

table thead th {
    text-align: left;
}

table tbody tr:nth-child(odd) {
    background-color: #f8f8f8;
}

a {
    color: #444;
}

span.error {
    color: darkred;
}

span.success {
    color: darkgreen;
}

span.warning {
    color: darkorange;
}

footer {
    margin-top: 1em;
    font-size: 85%;
    color: #888;
    text-align: center;
}
    </style>
</head>
<body>
<h1>eduVPN Server Info</h1>
<table>
<thead>
    <tr>
        <th>Server URL</th>
        <th>Version</th>
    </tr>
</thead>
<tbody>
<?php foreach ($serverVersionList as $baseUri => $serverVersion): ?>
    <tr>
        <td><a href="<?=$baseUri; ?>"><?=parse_url($baseUri, PHP_URL_HOST); ?></a></td>
        <td>
<?php if (null === $serverVersion): ?>
            <span class="error">Error</span>
<?php else: ?>
<?php if ($serverVersion === $latestVersion): ?>
            <span class="success"><?=$serverVersion; ?></span>
<?php else: ?>
            <span class="warning"><?=$serverVersion; ?></span>
<?php endif; ?>
<?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
</tbody>
</table>
<footer>
Generated on <?=$dateTime->format(DateTime::ATOM); ?>
</footer>
</body>
</html>
