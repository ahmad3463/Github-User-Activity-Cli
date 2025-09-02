<?php
if ($argc < 2) {
    echo "Usage: php github-activity.php <username>\n";
    exit(1);
}

$username = $argv[1];
$url = "https://api.github.com/users/$username/events";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: php-cli",
    "Accept: application/vnd.github.v3+json"
]);

$response = curl_exec($ch);

if ($response === false) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    exit(1);
}

$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($statusCode == 404) {
    echo "Error: User '$username' not found.\n";
    exit(1);
} elseif ($statusCode != 200) {
    echo "Error: GitHub API returned status $statusCode\n";
    exit(1);
}


$data = json_decode($response, true);

if (empty($data)) {
    echo "No recent activity found for $username\n";
    exit(0);
}

foreach (array_slice($data, 0, 10) as $event) {
    $type = $event["type"];
    $repo = $event["repo"]["name"];

    switch ($type) {
        case "PushEvent":
            $commitCount = count($event["payload"]["commits"] ?? []);
            echo "- Pushed $commitCount commits to $repo\n";
            break;
        case "IssuesEvent":
            $action = ucfirst($event["payload"]["action"]);
            echo "- $action an issue in $repo\n";
            break;
        case "WatchEvent":
            echo "- Starred $repo\n";
            break;
        case "ForkEvent":
            echo "- Forked $repo\n";
            break;
        default:
            echo "- $type on $repo\n";
    }
}
