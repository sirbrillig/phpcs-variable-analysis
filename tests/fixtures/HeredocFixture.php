<?php
function function_with_heredoc() {
    $var = 10;
    echo <<<END_OF_TEXT
$var
{$var}
${var}
$var2
{$var2}
${var2}
\$var2
\\$var2
END_OF_TEXT;
}
