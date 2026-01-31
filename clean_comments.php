<?php
$filePath = 'c:/case-php/app/Services/LeagueService.php';
$content = file_get_contents($filePath);
$tokens = token_get_all($content);
$newContent = '';

foreach ($tokens as $token) {
    if (is_array($token)) {
        // T_COMMENT is for // and # and /* */
        // T_DOC_COMMENT is for /** */
        if ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT) {
            $newContent .= $token[1];
        }
    } else {
        $newContent .= $token;
    }
}

// Optional: clean up excessive empty lines (more than 2 consecutive newlines)
$newContent = preg_replace("/(\r?\n){3,}/", "\n\n", $newContent);

file_put_contents($filePath, $newContent);
echo "Comments removed successfully from $filePath\n";
