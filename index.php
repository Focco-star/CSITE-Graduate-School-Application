<?php /* Starts PHP so a redirect can be sent before any HTML reaches the browser. */ ?>
<?php
// Sends the visitor to the public home page inside this same project folder.
header('Location: public/index.php');
// Stops the script immediately after the redirect so nothing else runs.
exit;

