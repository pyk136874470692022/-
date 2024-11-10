<?php
function getShares() {
    $sharesFile = 'shares.json';
    if (file_exists($sharesFile)) {
        return json_decode(file_get_contents($sharesFile), true) ?? [];
    }
    return [];
}

function saveShares($shares) {
    file_put_contents('shares.json', json_encode($shares));
}

function getShare($shareId) {
    $shares = getShares();
    if (isset($shares[$shareId])) {
        $share = $shares[$shareId];
        if ($share['expires'] > time()) {
            return $share;
        }
    }
    return null;
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?> 