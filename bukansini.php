<?php
// Redirect bersih tanpa HTML sama sekali
ob_start();
header("Location: https://sariroti.com/id/", true, 302);
header("Connection: close");
header("Content-Length: 0");
ob_end_flush();
@flush();
exit();
?>