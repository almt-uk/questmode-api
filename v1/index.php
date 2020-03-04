<?php

error_reporting(-1);
ini_set('display_errors', 'On');

require_once '../include/db_handler.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

/**
 * ERRORS
 * 100 - BAD API KEY
 * 101 - NULL ERROR
 * 102 - ACCOUNT CLOSED 
 * 103 - EMAIL NOT VERIFIED
 * 104 - 2STEPS AUTH
 * 105 - WRONG PASSWORD
 * 106 - WRONG CREDENTIALS
 * 107 - USER NOT EXISTS
 * 108 - EMPTY; NO USER INTERACTIONS
 * 109 - NOT UNIQUE CREDENTIAL
 * 110 - NO SUCH HASHTAG
 * 111 - NO POSTS WITH THIS HASHTAG
 * 112 - NO POSTS IN TIMELINE
 * 113 - NO USER POSTS
 * 114 - NO COMMENTS FOR THE POST
 * 115 - ERROR ON INSERT CREDENTIALS
 * 116 - ERROR ON UPDATING CREDENTIALS
 * 117 - ERROR ON CREDENTIALS SIGN UP
 * 118 - EMPTY ACCOUNT DETAILS
 * 119 - FAILED TO CREATE NEW ACCOUNT
 * 120 - FAILED TO INTERACT
 * 121 - FAILED TO UPLOAD POST
 * 122 - FAILED TO UPDATE POST
 * 123 - FAILED TO CREATE COMMENT
 * 124 - WRONG PASSWORD
 * 125 - FAILED TO INTERACT IWHT USER
 * 
 * SUCCEED
 * 200 - SUCCESSFULLY LOGGED IN
 */

// User login
$app->post('/user/login', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'device_id', 'log_key', 'password'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $device_id = $app->request->post('device_id');
    $log_key = strtolower($app->request->post('log_key'));
    $password = $app->request->post('password');

    $db = new DbHandler();
    if($db->checkApi($package_name, $api_key)) {
        $response = array();
        $logType = typeLogKey($log_key);
        if($logType == 0) {
            //email login
            $loginResponse = $db->emailLogin($log_key, $password);
        } else if($logType == 1) {
            //phone login
            $loginResponse = $db->phoneLogin($log_key, $password);
        } else if($logType == 2) {
            //username login
            $loginResponse = $db->usernameLogin($log_key, $password);
        } else {
            echoError(101);
        }
        if($loginResponse["type"] == 200) {
            $user_id = json_decode(json_encode($loginResponse["userData"]))->user_id;
            $log_key = $db->getLogKey($user_id);
            $db->addLoginDetails($user_id, $device_id);
            $loginResponse = json_decode(json_encode($loginResponse["userData"]));
            $loginResponse->log_key = $log_key;
            $response["error"] = false;
            $response["data"] = $loginResponse;
            echoRespnse(200, $response);
        } else {
            echoError($loginResponse["type"]);
        }
    } else {
        echoError(100);
    }

});

// User full details
$app->post('/user/authenticate/logkey', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'log_key'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $log_key = $app->request->post('log_key');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        
        $dataUser = $db->checkLogKey($my_uid, $log_key);
        if ($dataUser["type"] == 200) {
            $response["error"] = false;
            echoRespnse(200, $response);
        } else {
            $response["error"] = true;
            echoRespnse(200, $response);
        }
    } else {
        echoError(100);
    }

});

// User full details
$app->post('/user/verified/send', function() use ($app) {
    // check for required params

    
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'fullName', 'category', 'documentID',
        'knownFor'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $fullName = $app->request->post('fullName');
    $category = $app->request->post('category');
    $documentID = $app->request->post('documentID');
    $knownFor = $app->request->post('knownFor');

    $db = new DbHandler();
    if($my_uid == 0 || $fullName == NULL || $category == NULL || $documentID == NULL || $knownFor == NULL) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $response = $db->requestVerification($my_uid, $fullName, $category, $documentID, $knownFor);
        echoRespnse(200, $response);
    } else {
        echoError(100);
    }

});

// User full details
$app->post('/user/full_details', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'user_id', 'username'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $user_id = $app->request->post('user_id');
    $username = $app->request->post('username');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        
        $dataUser = $db->userFullDetails($my_uid, $user_id, $username);
        if ($dataUser["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["data"] = $dataUser;
            echoRespnse(200, $response);
        } else {
            echoError($dataUser["type"]);
        }
    } else {
        echoError(100);
    }

});

// User quick details
$app->post('/user/quick_details', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'post_id', 'user_id', 'action_type'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $post_id = $app->request->post('post_id');
    $user_id = $app->request->post('user_id');
    $action_type = $app->request->post('action_type');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $dataUsers = $db->userQuickDetails($my_uid, $post_id, $user_id, $action_type);
        if ($dataUsers["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["data"] = $dataUsers;
            echoRespnse(200, $response);
        } else {
            echoError($dataUsers["type"]);
        }
    } else {
        echoError(100);
    }

});

// User block
$app->post('/user/block', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'user_id', 'action'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $user_id = $app->request->post('user_id');
    $action = $app->request->post('action');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $dataUsers = $db->block($my_uid, $user_id, $action);
        if ($dataUsers["type"] == 200) {
            $response = array();
            $response["error"] = false;
            echoRespnse(200, $response);
        } else {
            echoError($dataUsers["type"]);
        }
    } else {
        echoError(100);
    }

});

// User update cover
$app->post('/users/update/cover', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'color1', 'color2'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $color1 = $app->request->post('color1');
    $color2 = $app->request->post('color2');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $dataUsers = $db->updateCover($my_uid, $color1, $color2);
        if ($dataUsers["type"] == 200) {
            $response = array();
            $response["error"] = false;
            echoRespnse(200, $response);
        } else {
            echoError($dataUsers["type"]);
        }
    } else {
        echoError(100);
    }

});

// User update credentials
$app->post('/users/update/credentials', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'username', 'email', 'phone'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $username = $app->request->post('username');
    $email = $app->request->post('email');
    $phone = $app->request->post('phone');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $updateCredentials = $db->updateCredentials($my_uid, $username, $email, $phone);
        if ($updateCredentials["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["data"] = $updateCredentials;
            echoRespnse(200, $response);
        } else {
            echoError($updateCredentials["type"]);
        }
    } else {
        echoError(100);
    }

});

// User update credentials
$app->post('/user/configure/businessAccount', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'category'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $category = $app->request->post('category');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $configureBusinessAccount = $db->configureBusinessAccount($my_uid, $category);
        if ($configureBusinessAccount["type"] == 200) {
            $response = array();
            $response["error"] = false;
            echoRespnse(200, $response);
        } else {
            echoError($configureBusinessAccount["type"]);
        }
    } else {
        echoError(100);
    }

});

// User update credentials
$app->post('/search/history/get', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $searchHistory = $db->searchHistoryGet($my_uid);
        if ($searchHistory["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["searchHistory"] = $searchHistory;
            echoRespnse(200, $response);
        } else {
            echoError($searchHistory["type"]);
        }
    } else {
        echoError(100);
    }

});

// User update credentials
$app->post('/search/history/delete', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $searchHistory = $db->searchHistoryDelete($my_uid);
        if ($searchHistory["type"] == 200) {
            $response = array();
            $response["error"] = false;
            echoRespnse(200, $response);
        } else {
            echoError($searchHistory["type"]);
        }
    } else {
        echoError(100);
    }

});

// User update credentials
$app->post('/users/update/details', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'name', 'country', 'gender',
        'birthday', 'website', 'status', 'bio', 'businessAddress', 'businessEmail', 'businessFounded',
        'businessLocation', 'businessPhone', 'businessPostalCode'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $name = $app->request->post('name');
    $country = $app->request->post('country');
    $gender = $app->request->post('gender');
    $birthday = $app->request->post('birthday');
    $website = $app->request->post('website');
    $status = $app->request->post('status');
    $bio = $app->request->post('bio');
    $businessAddress = $app->request->post('businessAddress');
    $businessEmail = $app->request->post('businessEmail');
    $businessFounded = $app->request->post('businessFounded');
    $businessLocation = $app->request->post('businessLocation');
    $businessPhone = $app->request->post('businessPhone');
    $businessPostalCode = $app->request->post('businessPostalCode');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $updateDetails = $db->updateDetails($my_uid, $name, $country, $gender, $birthday,
        $website, $status, $bio, $businessAddress, $businessEmail, $businessFounded, $businessLocation,
        $businessPhone, $businessPostalCode);
        if ($updateDetails["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["data"] = $updateDetails;
            echoRespnse(200, $response);
        } else {
            echoError($updateDetails["type"]);
        }
    } else {
        echoError(100);
    }

});

// User update credentials
$app->post('/users/update/visibility', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'showBirthday', 'showBusinessAddress',
        'showBusinessEmail', 'showBusinessPhone', 'showBusinessHQ', 'showCountry', 'showEmail', 'showFoundedDate',
        'showGender', 'showJoinedDate', 'showPhone', 'showUserOnline', 'showWebsite'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $showBirthday = $app->request->post('showBirthday');
    $showBusinessAddress = $app->request->post('showBusinessAddress');
    $showBusinessEmail = $app->request->post('showBusinessEmail');
    $showBusinessPhone = $app->request->post('showBusinessPhone');
    $showBusinessHQ = $app->request->post('showBusinessHQ');
    $showCountry = $app->request->post('showCountry');
    $showEmail = $app->request->post('showEmail');
    $showFoundedDate = $app->request->post('showFoundedDate');
    $showGender = $app->request->post('showGender');
    $showJoinedDate = $app->request->post('showJoinedDate');
    $showPhone = $app->request->post('showPhone');
    $showUserOnline = $app->request->post('showUserOnline');
    $showWebsite = $app->request->post('showWebsite');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $updateVisibility = $db->updateVisibility($my_uid, $showBirthday, $showBusinessAddress, $showBusinessEmail,
            $showBusinessPhone, $showBusinessHQ, $showCountry, $showEmail, $showFoundedDate, $showGender, $showJoinedDate,
            $showPhone, $showUserOnline, $showWebsite);
        if ($updateVisibility["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["data"] = $updateVisibility;
            echoRespnse(200, $response);
        } else {
            echoError($updateVisibility["type"]);
        }
    } else {
        echoError(100);
    }

});

// User update credentials
$app->post('/users/update/privateAccount', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'privateAccount'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $privateAccount = $app->request->post('privateAccount');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $updatePrivateAccount = $db->updatePrivateAccount($my_uid, $privateAccount);
        if ($updatePrivateAccount["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["data"] = $updatePrivateAccount;
            echoRespnse(200, $response);
        } else {
            echoError($updatePrivateAccount["type"]);
        }
    } else {
        echoError(100);
    }

});

// User check
$app->post('/user/check', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'username', 'email', 'phone', 'type'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $username = $app->request->post('username');
    $email = $app->request->post('email');
    $phone = $app->request->post('phone');
    $type = $app->request->post('type');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $checkedData = $db->checkCredentialsRT($my_uid, $username, $email, $phone, $type);
        if ($checkedData["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["checkedData"] = $checkedData;
            echoRespnse(200, $response);
        } else {
            echoError($checkedData["type"]);
        }
    } else {
        echoError(100);
    }

});

// User check
$app->post('/users/update/featuredProfiles', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'featuredProfiles'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $featuredProfiles = $app->request->post('featuredProfiles');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $updateFeaturedProfiles = $db->updateFeaturedProfiles($my_uid, $featuredProfiles);
        if ($updateFeaturedProfiles["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["updateFeaturedProfiles"] = $updateFeaturedProfiles;
            echoRespnse(200, $response);
        } else {
            echoError($updateFeaturedProfiles["type"]);
        }
    } else {
        echoError(100);
    }

});

// Hashtag size
$app->post('/hashtag/size', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'hashtag'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $hashtag = $app->request->post('hashtag');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $hashtagData = $db->hashtagSize($my_uid, $hashtag);
        if ($hashtagData["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["hashtagData"] = $hashtagData;
            echoRespnse(200, $response);
        } else {
            echoError($hashtagData["type"]);
        }
    } else {
        echoError(100);
    }

});

$app->post('/users/profile/pictures/delete', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'image_id'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $image_id = $app->request->post('image_id');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $deleteProfilePicture = $db->deleteProfilePicture($my_uid, $image_id);
        if ($deleteProfilePicture["type"] == 200) {
            $response = array();
            $response["error"] = false;
            echoRespnse(200, $response);
        } else {
            echoError($deleteProfilePicture["type"]);
        }
    } else {
        echoError(100);
    }

});

$app->post('/users/profile/pictures/update', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'image_id'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $image_id = $app->request->post('image_id');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $updateProfilePicture = $db->updateProfilePicture($my_uid, $image_id);
        if ($updateProfilePicture["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["response"] = $updateProfilePicture;
            echoRespnse(200, $response);
        } else {
            echoError($updateProfilePicture["type"]);
        }
    } else {
        echoError(100);
    }

});

$app->post('/users/profile/pictures/upload', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'image_id'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $image_id = $app->request->post('image_id');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $uploadProfilePicture = $db->uploadProfilePicture($my_uid, $image_id);
        if ($uploadProfilePicture["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["response"] = $uploadProfilePicture;
            echoRespnse(200, $response);
        } else {
            echoError($uploadProfilePicture["type"]);
        }
    } else {
        echoError(100);
    }

});

// User profile pictures
$app->post('/users/profile/pictures', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'user_id'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $user_id = $app->request->post('user_id');

    $db = new DbHandler();
    if($my_uid == 0 || $user_id != $my_uid) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $userProfilePictures = $db->userProfilePictures($user_id);
        if ($userProfilePictures["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["profilePictures"] = $userProfilePictures;
            echoRespnse(200, $response);
        } else {
            echoError($userProfilePictures["type"]);
        }
    } else {
        echoError(100);
    }

});

// Hashtag posts
$app->post('/hashtag/posts', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'hashtag', 'type'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $hashtag = $app->request->post('hashtag');
    $type = $app->request->post('type');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $hashtagData = $db->hashtagPosts($my_uid, $hashtag, $type);
        if ($hashtagData["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["hashtagData"] = $hashtagData;
            echoRespnse(200, $response);
        } else {
            echoError($hashtagData["type"]);
        }
    } else {
        echoError(100);
    }

});

// Posts timeline
$app->post('/posts/timeline', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $timelineData = $db->timelinePosts($my_uid);
        $response = array();
        if ($timelineData["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["timelineData"] = $timelineData;
            echoRespnse(200, $response);
        } else {
            echoError($timelineData["type"]);
        }
    } else {
        echoError(100);
    }

});

// Notifications
$app->post('/notifications', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $userNotification = $db->userNotification($my_uid);
        $response = array();
        if ($userNotification["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["userNotification"] = $userNotification;
            echoRespnse(200, $response);
        } else {
            echoError($userNotification["type"]);
        }
    } else {
        echoError(100);
    }

});

// Posts discover
$app->post('/posts/discover', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $discoverPosts = $db->discoverPosts($my_uid);
        $response = array();
        if ($discoverPosts["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["discoverPosts"] = $discoverPosts;
            echoRespnse(200, $response);
        } else {
            echoError($discoverPosts["type"]);
        }
    } else {
        echoError(100);
    }

});

// Discover search
$app->post('/discover/search', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'word'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $word = $app->request->post('word');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $discoverSearch = $db->discoverSearch($my_uid, $word);
        $response = array();
        if ($discoverSearch["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["discoverData"] = $discoverSearch;
            echoRespnse(200, $response);
        } else {
            echoError($discoverSearch["type"]);
        }
    } else {
        echoError(100);
    }

});

// Discover search
$app->post('/discover/search/clicked', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'word', 'type', 'id'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $word = $app->request->post('word');
    $type = $app->request->post('type');
    $id = $app->request->post('id');

    $db = new DbHandler();
    if($my_uid == 0 || $type == -1) {
        echoError(101);
    } else if($type == 1 && $my_uid==$id) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $discoverSearchClicked = $db->discoverSearchClicked($my_uid, $word, $type, $id);
        $response = array();
        if ($discoverSearchClicked["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["discoverSearchClicked"] = $discoverSearchClicked;
            echoRespnse(200, $response);
        } else {
            echoError($discoverSearchClicked["type"]);
        }
    } else {
        echoError(100);
    }

});

// User posts
$app->post('/posts/user', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'user_id', 'type'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $user_id = $app->request->post('user_id');
    $type = $app->request->post('type');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $userPosts = $db->userPosts($my_uid, $user_id, $type);
        if ($userPosts["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["userPosts"] = $userPosts;
            echoRespnse(200, $response);
        } else {
            echoError($userPosts["type"]);
        }
    } else {
        echoError(100);
    }

});

// User posts
$app->post('/posts/user/interacted', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $interactedPosts = $db->interactedPosts($my_uid);
        if ($interactedPosts["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["interactedPosts"] = $interactedPosts;
            echoRespnse(200, $response);
        } else {
            echoError($interactedPosts["type"]);
        }
    } else {
        echoError(100);
    }

});

// User posts
$app->post('/posts/user/saved', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $savedPosts = $db->savedPosts($my_uid);
        if ($savedPosts["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["savedPosts"] = $savedPosts;
            echoRespnse(200, $response);
        } else {
            echoError($savedPosts["type"]);
        }
    } else {
        echoError(100);
    }

});

// User comments
$app->post('/post/comments', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'post_id'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $post_id = $app->request->post('post_id');

    $db = new DbHandler();
    if($my_uid == 0) {
        echoError(101);
    } else if($db->checkApi($package_name, $api_key)) {
        $postComments = $db->postComments($my_uid, $post_id);
        if ($postComments["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["postComments"] = $postComments;
            echoRespnse(200, $response);
        } else {
            echoError($postComments["type"]);
        }
    } else {
        echoError(100);
    }

});

// SignUp Credentials Only
$app->post('/signup/credentials', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'username', 'email', 'user_id'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $username = $app->request->post('username');
    $email = $app->request->post('email');
    $user_id = $app->request->post('user_id');

    $db = new DbHandler();
    if($db->checkApi($package_name, $api_key)) {
        $signUpCredentials = $db->signUpCredentials($username, $email, $user_id);
        if ($signUpCredentials["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["signUpCredentials"] = $signUpCredentials;
            echoRespnse(200, $response);
        } else {
            echoError($signUpCredentials["type"]);
        }
    } else {
        echoError(100);
    }

});

// SignUp Account
$app->post('/signup/account', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'user_id',
        'name', 'password', 'image', 'gender', 'country', 'dateOfBirthday'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $user_id = $app->request->post('user_id');
    $name = $app->request->post('name');
    $password = $app->request->post('password');
    $image = $app->request->post('image');
    $gender = $app->request->post('gender');
    $country = $app->request->post('country');
    $dateOfBirthday = $app->request->post('dateOfBirthday');

    $db = new DbHandler();
    if($db->checkApi($package_name, $api_key)) {
        $signUpAccount = $db->signUpAccount($user_id, $name, $password, $image, $gender,
            $country, $dateOfBirthday);
        if ($signUpAccount["type"] == 200) {
            $response = array();
            $response["error"] = false;
            $response["signUpAccount"] = $signUpAccount;
            echoRespnse(200, $response);
        } else {
            echoError($signUpAccount["type"]);
        }
    } else {
        echoError(100);
    }

});

// SignUp Session
$app->post('/signup/session', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'user_id'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $user_id = $app->request->post('user_id');

    $db = new DbHandler();
    if($db->checkApi($package_name, $api_key)) {
        $db->signupSession($user_id);
        $response = array();
        $response["error"] = false;
        echoRespnse(200, $response);
    } else {
        echoError(100);
    }

});

// Post Interactions
$app->post('/posts/update/interaction', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'post_id', 'type'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $post_id = $app->request->post('post_id');
    $type = $app->request->post('type');

    if($my_uid == 0) {
        echoError(120);
        return;
    }
    $db = new DbHandler();
    if($db->checkApi($package_name, $api_key)) {
        $postInteraction = $db->updatePostInteraction($my_uid, $post_id, $type);
        $response = array();
        if ($postInteraction["type"] == 200) {
            $response["error"] = false;
            $response["postInteraction"] = $postInteraction;
            echoRespnse(200, $response);
        } else {
            echoError($postInteraction["type"]);
        }
    } else {
        echoError(100);
    }

});

// Post Create
$app->post('/posts/create', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'description', 'color_1',
        'color_2', 'images_url', 'type'));
    
    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $type = $app->request->post('type');
    $description = $app->request->post('description');
    $color_1 = $app->request->post('color_1');
    $color_2 = $app->request->post('color_2');
    $images_url = $app->request->post('images_url');

    if($my_uid == 0) {
        echoError(100);
        return;
    }
    $db = new DbHandler();
    if($db->checkApi($package_name, $api_key)) {
        $createPost = $db->createPost($my_uid, $type, $description, $color_1, $color_2, $images_url);
        $response = array();
        if ($createPost["type"] == 200) {
            $response["error"] = false;
            $response["createPost"] = $createPost;
            echoRespnse(200, $response);
        } else {
            echoError($createPost["type"]);
        }
    } else {
        echoError(100);
    }

});

// Post Update
$app->post('/posts/update', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'post_id', 'description', 'color_1',
        'color_2', 'images_url', 'type'));
    
    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $post_id = $app->request->post('post_id');
    $type = $app->request->post('type');
    $description = $app->request->post('description');
    $color_1 = $app->request->post('color_1');
    $color_2 = $app->request->post('color_2');
    $images_url = $app->request->post('images_url');

    if($my_uid == 0) {
        echoError(100);
        return;
    }
    $db = new DbHandler();
    if($db->checkApi($package_name, $api_key)) {
        $updatePost = $db->updatePost($my_uid, $post_id, $type, $description, $color_1, $color_2, $images_url);
        $response = array();
        if ($updatePost["type"] == 200) {
            $response["error"] = false;
            $response["updatePost"] = $updatePost;
            echoRespnse(200, $response);
        } else {
            echoError($updatePost["type"]);
        }
    } else {
        echoError(100);
    }

});

// Post Create
$app->post('/comments/create', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'post_id', 'comment'));
    
    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $post_id = $app->request->post('post_id');
    $comment = $app->request->post('comment');

    if($my_uid == 0 || $post_id == 0) {
        echoError(100);
        return;
    }
    $db = new DbHandler();
    if($db->checkApi($package_name, $api_key)) {
        $createComment = $db->createComment($my_uid, $post_id, $comment);
        $response = array();
        if ($createComment["type"] == 200) {
            $response["error"] = false;
            $response["createComment"] = $createComment;
            echoRespnse(200, $response);
        } else {
            echoError($createComment["type"]);
        }
    } else {
        echoError(100);
    }

});

// User Interactions
$app->post('/users/update/interaction', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'user_id', 'interaction_type', 'action'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $user_id = $app->request->post('user_id');
    $interaction_type = $app->request->post('interaction_type');
    $action = $app->request->post('action');

    if($my_uid == 0) {
        echoError(100);
        return;
    }
    $db = new DbHandler();
    if($db->checkApi($package_name, $api_key)) {
        $updateUserInteraction = $db->updateUserInteraction($my_uid, $interaction_type, $user_id, $action);
        $response = array();
        if ($updateUserInteraction["type"] == 200) {
            $response["error"] = false;
            $response["updateUserInteraction"] = $updateUserInteraction;
            echoRespnse(200, $response);
        } else {
            echoError($updateUserInteraction["type"]);
        }
    } else {
        echoError(100);
    }

});

// User Change Password
$app->post('/user/password', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'log_key', 'password', 'newPassword'));
    
    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $my_uid = $app->request->post('my_uid');
    $password = $app->request->post('password');
    $newPassword = $app->request->post('newPassword');
    $log_key = $app->request->post('log_key');

    if($my_uid == 0) {
        echoError(100);
        return;
    }
    $db = new DbHandler();
    if($db->checkApi($package_name, $api_key)) {
        $updatePassword = $db->updatePassword($my_uid, $log_key, $password, $newPassword);
        $response = array();
        if ($updatePassword["type"] == 200) {
            $response["error"] = false;
            $response["updatePassword"] = $updatePassword;
            echoRespnse(200, $response);
        } else {
            echoError($updatePassword["type"]);
        }
    } else {
        echoError(100);
    }

});

// SignUp Session
$app->post('/user/session', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('package_name', 'api_key', 'user_id'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $user_id = $app->request->post('user_id');

    if($user_id == 0) {
        echoError(100);
        return;
    }
    $db = new DbHandler();
    if($db->checkApi($package_name, $api_key)) {
        $db->userSession($user_id);
        $response = array();
        $response["error"] = false;
        echoRespnse(200, $response);
    } else {
        echoError(100);
    }

});

// Autocomplete
$app->post('/autocomplete', function() use ($app) {
    // check for required params

    verifyRequiredParams(array('package_name', 'api_key', 'my_uid', 'word', 'type'));

    $package_name = $app->request->post('package_name');
    $api_key = $app->request->post('api_key');
    $user_id = $app->request->post('my_uid');
    $word = $app->request->post('word');
    $type = $app->request->post('type');

    $db = new DbHandler();
    if($db->checkApi($package_name, $api_key)) {
        $autocomplete = $db->autocomplete($user_id, $word, $type);
        $response = array();
        if ($autocomplete["type"] == 200) {
            $response["error"] = false;
            $response["autocomplete"] = $autocomplete;
            echoRespnse(200, $response);
        } else {
            echoError($autocomplete["type"]);
        }
    } else {
        echoError(100);
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

function IsNullOrEmptyString($str) {
    return (!isset($str) || trim($str) === '');
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoError($status_code) {
    if ($status_code == 100) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Bad Api Key";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 101) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Null Error";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 102) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Account Closed";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 103) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Email Not Verified";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 104) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "2Steps Auth - Not Supported";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 105) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Wrong Password";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 106) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Wrong Credentials";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 107) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "No such user";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 108) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "No interactions";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 109) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Credential not unique";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 110) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "No such hashtag";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 111) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "No posts with this hashtag";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 112) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "No posts for timeline";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 113) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "No posts for user";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 114) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "No comments for this post";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 115) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Error on creating credentials";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 116) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Error on updating credentials";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 117) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Error credentials";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 118) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Empty data for the new account";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 119) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Failed to create new account";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 120) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Failed to interact with post";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 121) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Failed to upload post";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 122) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Failed to update post";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 123) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Failed to create comment";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 124) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Wrong password";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else if ($status_code == 125) {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = $status_code;
        $responseError["errorMessage"] = "Failed to interact with user";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    } else {
        $response = array();
        $responseError = array();
        $responseError["errorType"] = 0;
        $responseError["errorMessage"] = "N/A";
        $response["error"] = true;
        $response["errorData"] = $responseError;
        echoRespnse(200, $response);
    }
}

function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>