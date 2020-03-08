<?php

class DbHandlerUnity {

    private $conn;
    private $validSession = false;
    private $clearance_level = 0;

    function __construct() {
        $path = $_SERVER['DOCUMENT_ROOT'];
        require_once $path . '/include/db_connect.php';
        require_once $path . '/libs/Utils/utils.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    public function checkApi($api_key, $api_password)
    {
        
        $sqlQuery = "SELECT clearance_level FROM api_clients WHERE api_key = ? AND api_password = ?";
        $stmt = $this->conn->prepare($sqlQuery);
        $stmt->bind_param("ss", $api_key, $api_password);
        if ($stmt->execute()) {
            $dataRows = fetchData($stmt);
            if (count($dataRows) == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }  

}

?>
