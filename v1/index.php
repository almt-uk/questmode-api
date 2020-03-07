<?php

error_reporting(-1);
ini_set('display_errors', 'On');

require_once '../db_handler/web.php';
require_once '../db_handler/unity.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
$app->post('/unity/user/login', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('api_key', 'api_password'));

    $api_key = $app->request->post('api_key');
    $api_password = $app->request->post('api_password');

    return 0;
    $db = new DbHandlerUnity();
    if($db->checkApi($api_key, $api_password)) {
        $response = array();
        echoResponse(200, "good api");
    } else {
        echoResponse(101, "error. wrong api");
    }

});

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>