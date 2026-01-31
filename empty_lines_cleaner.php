<?php
$filePath = 'c:/case-php/app/Services/LeagueService.php';
$content = file_get_contents($filePath);
$lines = explode("\n", $content);
$finalLines = [];
foreach ($lines as $line) {
    if (trim($line) !== '') {
        $finalLines[] = rtrim($line);
    } else {
        if (!empty($finalLines) && trim(end($finalLines)) !== '') {
            $finalLines[] = '';
        }
    }
}
file_put_contents($filePath, implode("\n", $finalLines));
echo "Empty lines cleaned up in $filePath\n";
