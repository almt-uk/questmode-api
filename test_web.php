<?php

$path = $_SERVER['DOCUMENT_ROOT'];
require_once $path . '/db_handler/web.php';

$db = new DbHandlerWeb();

$checkAPI = $db->checkApi("xtoAkWqVGp4nDtW6tZL1AaJUCl9I3tYcqjfTBhSu", "PHZ7dh4vHtbJoF7kD2RtZQUxi3opTFeXvpa0Jp7R");

echo $checkAPI;

?>