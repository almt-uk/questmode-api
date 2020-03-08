<?php

$path = $_SERVER['DOCUMENT_ROOT'];
require_once $path . '/db_handler/web.php';

$db = new DbHandlerWeb();

$checkAPI = $db->checkApi("0RMs4Z0pFV7TNk9hsctm7w0ZdV2QWm9yuOawQJZm", "0RMs4Z0pFV7TNk9hsctm7w0ZdV2QWm9yuOawQJZm");

echo $checkAPI;

?>