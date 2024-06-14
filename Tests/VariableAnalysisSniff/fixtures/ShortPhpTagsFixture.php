<?php
$foo = 'hello';
$usedLast = 'hello';
$bar = 'bye'; // unused variable
?>
<html>
<?= $baz ?> undefined variable
<?= $foo ?>
<?= $usedLast ?> used as last php statement without semicolon
</html>
