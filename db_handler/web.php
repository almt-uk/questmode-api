<?php

class DbHandler {

    private $conn;

    function __construct() {
        $path = $_SERVER['DOCUMENT_ROOT'];
        require_once $path . '/include/db_connect.php';
        require_once $path . '/libs/Utils/utils.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    public function addLoginDetails($user_id, $device_id) {
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
        $loc = explode(",", $details->loc, 2);
        $lat = $loc[0];
        $lon = $loc[1];
        $stmt = $this->conn->prepare("INSERT INTO user_logins 
                                (user_id, device_id, ip, ip_latitude, ip_longitude, country_code, city, region) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY 
                                UPDATE ip = ?, ip_latitude = ?, ip_longitude = ?, country_code = ?, city = ?, region = ?, timestamp = now()");
        $stmt->bind_param("isssssssssssss",
                $user_id, $device_id, $ip, $lat, $lon, $details->country, $details->city, $details->region,
                $ip, $lat, $lon, $details->country, $details->city, $details->region);
        $stmt->execute();
        $stmt->close();
        
    }

    public function emailLogin($log_key, $password) {
        
        $response = array();
        $stmt = $this->conn->prepare("SELECT user_id, password, account_closed, verified_email, authentication_type, gradient_colour_1, gradient_colour_2 FROM users WHERE email = ?");
        $stmt->bind_param("s", $log_key);
        if (!$stmt->execute()) {
            $stmt->close();
            $response["type"] = 101;
            return $response;
        }
        $dataRows = fetchData($stmt);
        $stmt->close();
        if (count($dataRows) == 1) {
            $userData = json_decode(json_encode($dataRows[0]));
            $user_id = $userData->user_id;
            $passwordN = $userData->password;
            $account_closed = $userData->account_closed;
            $verified_email = $userData->verified_email;
            $authentication_type = $userData->authentication_type;
            if ($user_id) {
                if (password_verify($password, $passwordN)) {
                    if($account_closed == 1) {
                        $response["type"] = 102;
                        return $response;
                    } else if($verified_email == 0) {
                        $response["type"] = 103;
                        return $response;
                    } else if($authentication_type == 1) {
                        $response["type"] = 104;
                        return $response;
                    } else if($authentication_type == 0) {
                        unset($userData->{"password"});
                        $response["userData"] = $userData;
                        $response["type"] = 200;
                        return $response;
                    }
                } else {
                    $response["type"] = 105;
                    return $response;
                }
            } else {
                $response["type"] = 106;
                return $response;
            }
        } else {
            $response["type"] = 106;
            return $response;
        }

    }

    public function phoneLogin($log_key, $password) {
        
        $response = array();
        $stmt = $this->conn->prepare("SELECT user_id, password, account_closed, verified_email, verified_phone, authentication_type, gradient_colour_1, gradient_colour_2 FROM users WHERE phone = ?");
        $stmt->bind_param("s", $log_key);
        if (!$stmt->execute()) {
            $stmt->close();
            $response["type"] = 101;
            return $response;
        }
        $dataRows = fetchData($stmt);
        $stmt->close();
        if (count($dataRows) == 1) {
            $userData = json_decode(json_encode($dataRows[0]));
            $user_id = $userData->user_id;
            $passwordN = $userData->password;
            $account_closed = $userData->account_closed;
            $verified_email = $userData->verified_email;
            $verified_phone = $userData->verified_phone;
            $authentication_type = $userData->authentication_type;
            if ($user_id) {
                if (password_verify($password, $passwordN)) {
                    if($account_closed == 1) {
                        $response["type"] = 102;
                        return $response;
                    } else if($verified_email == 0 || $verified_phone == 0) {
                        $response["type"] = 103;
                        return $response;
                    } else if($authentication_type == 1) {
                        $response["type"] = 104;
                        return $response;
                    } else if($authentication_type == 0) {
                        unset($userData->{"password"});
                        $response["userData"] = $userData;
                        $response["type"] = 200;
                        return $response;
                    }
                } else {
                    $response["type"] = 105;
                    return $response;
                }
            } else {
                $response["type"] = 106;
                return $response;
            }
        } else {
            $response["type"] = 106;
            return $response;
        }

    }

    public function usernameLogin($log_key, $password) {
        
        $response = array();
        $stmt = $this->conn->prepare("SELECT user_id, password, account_closed, verified_email, authentication_type, name, image, username, account_verified, gradient_colour_1, gradient_colour_2 FROM users WHERE username = ?");
        $stmt->bind_param("s", $log_key);
        if (!$stmt->execute()) {
            $stmt->close();
            $response["type"] = 101;
            return $response;
        }
        $dataRows = fetchData($stmt);
        $stmt->close();
        if (count($dataRows) == 1) {
            $userData = json_decode(json_encode($dataRows[0]));
            $user_id = $userData->user_id;
            $passwordN = $userData->password;
            $account_closed = $userData->account_closed;
            $verified_email = $userData->verified_email;
            $authentication_type = $userData->authentication_type;
            if ($user_id) {
                if (password_verify($password, $passwordN)) {
                    if($account_closed == 1) {
                        $response["type"] = 102;
                        return $response;
                    } else if($verified_email == 0) {
                        $response["type"] = 103;
                        return $response;
                    } else if($authentication_type == 1) {
                        $response["type"] = 104;
                        return $response;
                    } else if($authentication_type == 0) {
                        unset($userData->{"password"});
                        $response["userData"] = $userData;
                        $response["type"] = 200;
                        return $response;
                    }
                } else {
                    $response["type"] = 105;
                    return $response;
                }
            } else {
                $response["type"] = 106;
                return $response;
            }
        } else {
            $response["type"] = 106;
            return $response;
        }

    }

    public function login($logkey, $password) {
        
        $logType = typeLogKey($logkey);
        $response = array();
        if($logType == 0) {
            //email login
            $responseLogin = $this->emailLogin($logkey, $password);
        } else if($logType == 1) {
            //phone login
            $responseLogin = $this->phoneLogin($logkey, $password);
        } else if($logType == 2) {
            //username login
            $responseLogin = $this->usernameLogin($logkey, $password);
        }
        if($responseLogin["type"] == 200) {
            $user_id = json_decode(json_encode($responseLogin["userData"]))->user_id;
            $device_id = "website" . json_decode(json_encode($responseLogin["userData"]))->username . json_decode(json_encode($responseLogin["userData"]))->user_id;
            $this->addLoginDetails($user_id, $device_id);
            return json_encode($responseLogin);
        } else {
            return json_encode($responseLogin);
        }

    }

    public function userFullDetails($my_uid, $username) {
        
        $stmt = $this->conn->prepare("SELECT U.user_id, U.name, U.username, U.image, U.account_online,
            U.account_private, U.account_closed, U.account_verified, U.status, U.gradient_colour_1,
            U.gradient_colour_2,
            (SELECT count(*) FROM user_followers UF
            WHERE UF.follower = U.user_id
            AND UF.type = 1) following, 
            (SELECT count(*) FROM user_followers UF
            WHERE UF.following = U.user_id
            AND UF.type = 1) followers, 
            (SELECT count(*) FROM posts P
            WHERE P.owner_id = U.user_id
            AND P.post_deleted = 0
            AND P.post_archived = 0) no_posts
            FROM users U
            WHERE U.username = ?
            LIMIT 1");
        $stmt->bind_param("s", $username);
        if ($stmt->execute()) {
            $dataRows = fetchData($stmt);
            if($dataRows != null) {
                $userData = json_decode(json_encode($dataRows[0]));
                $stmt->close();
                return $userData;
            } else {
                $stmt->close();
                return null;
            }

        } else {
            $response["type"] = 101;
            return $response;
        }

    }

    public function hasAccess($my_uid, $user_id) {
        $sql = "SELECT
                CASE
                    WHEN U.user_id=? THEN 1
                    WHEN U.account_private=0 THEN 1
                    WHEN U.account_private=1 THEN (SELECT 1 FROM user_followers uf WHERE uf.following = U.user_id AND uf.follower = ? AND uf.type = 1)
                END AS has_access
                FROM users U
                WHERE U.user_id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $my_uid, $my_uid, $user_id);
        if ($stmt->execute()) { 
            $hasAccess = fetchData($stmt)[0];
            if($hasAccess["has_access"] == NULL) {
                return 0;
            } else {
                return 1;
            }
        } else {
            return 0;
        }
    }

    public function userPosts($my_uid, $user_id, $type) {
        
        $response = array();
        $hasAccess = $this->hasAccess($my_uid, $user_id);
        if($hasAccess != 1) {
            $response["hasAccess"] = false;
            return;
        } else {
            $response["hasAccess"] = true;
        }
        if ($type == 0) {
            //recent
            $stmt = $this->conn->prepare("SELECT U.username, U.name, U.image, U.account_verified, P.description, P.date_posted, P.post_type, P.images_url, P.color_1, P.color_2, P.post_id, P.owner_id,
                    (SELECT 1 FROM post_interactions PI WHERE PI.post_id = P.post_id AND PI.owner_id = ? AND PI.interaction_type = 0) liked,
                    (SELECT 1 FROM post_interactions PI WHERE PI.post_id = P.post_id AND PI.owner_id = ? AND PI.interaction_type = 1) unliked,
                    (SELECT 1 FROM post_saved PS WHERE PS.post_id = P.post_id AND PS.owner_id = ?) saved,
                    (SELECT COUNT(post_id) FROM post_interactions PI WHERE PI.post_id = P.post_id AND PI.interaction_type = 0) number_likes,
                    (SELECT COUNT(post_id) FROM post_interactions PI WHERE PI.post_id = P.post_id AND PI.interaction_type = 1) number_unlikes,
                    (SELECT COUNT(post_id) FROM post_views PV WHERE PV.post_id = P.post_id) number_views
                FROM posts P 
                INNER JOIN users U 
                ON P.owner_id = U.user_id
                AND P.post_archived = 0
                AND U.account_closed = 0
                AND P.post_deleted = 0
                WHERE P.owner_id=?
                ORDER BY P.date_posted desc LIMIT 30");
            $stmt->bind_param("iiii", $my_uid, $my_uid, $my_uid, $user_id);
        } else if ($type == 1) {
            //mentions
            $stmt = $this->conn->prepare("SELECT U.username, U.name, U.image, U.account_verified, P.description, P.date_posted, P.post_type, P.images_url, P.color_1, P.color_2, P.post_id, P.owner_id,
                    (SELECT 1 FROM post_interactions PI WHERE PI.post_id = P.post_id AND PI.owner_id = ? AND PI.interaction_type = 0) liked,
                    (SELECT 1 FROM post_interactions PI WHERE PI.post_id = P.post_id AND PI.owner_id = ? AND PI.interaction_type = 1) unliked,
                    (SELECT 1 FROM post_saved PS WHERE PS.post_id = P.post_id AND PS.owner_id = ?) saved,
                    (SELECT COUNT(post_id) FROM post_interactions PI WHERE PI.post_id = P.post_id AND PI.interaction_type = 0) number_likes,
                    (SELECT COUNT(post_id) FROM post_interactions PI WHERE PI.post_id = P.post_id AND PI.interaction_type = 1) number_unlikes,
                    (SELECT COUNT(post_id) FROM post_views PV WHERE PV.post_id = P.post_id) number_views
                FROM post_mentions PM
                INNER JOIN posts P ON P.post_id=PM.post_id
                INNER JOIN users U ON U.user_id=P.owner_id
                WHERE P.post_archived = 0
                AND U.account_closed = 0
                AND P.post_deleted = 0
                AND P.owner_id <> PM.mention_id
                AND PM.mention_id = ?
                ORDER BY P.date_posted desc LIMIT 30");
            $stmt->bind_param("iiii", $my_uid, $my_uid, $my_uid, $user_id);
        }
        if ($stmt->execute()) {
            $dataRows = fetchData($stmt);
            $this->updatePostsViews($my_uid, $dataRows);

            $userPosts = array();
            foreach ($dataRows as $post) {
                $post = json_decode(json_encode($post));
                $number_views_post = $post->number_views;
                if ($number_views_post == 0) {
                    $post->number_views += 1;
                }
                $stmt = $this->conn->prepare("SELECT COUNT(U.user_id) AS mutual_followers FROM users U
                    INNER JOIN post_interactions PI ON PI.post_id = ?
                    INNER JOIN user_followers UF ON UF.follower = ?
                    WHERE U.user_id = PI.owner_id AND PI.owner_id = UF.following");
                $stmt->bind_param("ii", $post->post_id, $my_uid);
                $stmt->execute();
                $mutual_followers = json_decode(json_encode(fetchData($stmt)[0]))->mutual_followers;
                $post->mutual_followers = $mutual_followers;
                    
                $stmt = $this->conn->prepare("SELECT U.username, U.image, U.user_id FROM users U
                    INNER JOIN post_interactions PI ON PI.post_id = ?
                    INNER JOIN user_followers UF ON UF.follower = ?
                    WHERE U.user_id = PI.owner_id AND PI.owner_id = UF.following
                    LIMIT 3");
                $stmt->bind_param("ii", $post->post_id, $my_uid);
                $stmt->execute();
                $mutual_followers_data = fetchData($stmt);
                $post->mutual_followers_data = $mutual_followers_data;

                $userPosts[] = $post;

            }

            $stmt->close();
            $response["posts"] = $userPosts;
            $response["type"] = 200;
            return $response;
        } else {
            $response["type"] = 101;
            return $response;
        }

    }

    public function updatePostsViews($user_id, $postsArray) {
        foreach($postsArray as $value){
            $post_id = json_decode(json_encode($value))->post_id;
            $this->updatePostViews($user_id, $post_id);
        }
    }

    public function updatePostViews($user_id, $post_id) {
        $stmt = $this->conn->prepare("IF EXISTS (SELECT * FROM post_views WHERE post_id=? AND user_id=?) THEN
                UPDATE post_views SET timestamp=now() WHERE post_id=? AND user_id=?;
            ELSE 
                INSERT INTO post_views (post_id, user_id, timestamp)VALUES (?, ?, now());
            END IF");
        $stmt->bind_param("iiiiii", $post_id, $user_id, $post_id, $user_id, $post_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    public function userDetails($my_uid, $user_id) {
        
        $response = array();
        $hasAccess = $this->hasAccess($my_uid, $user_id);
        if($hasAccess != 1) {
            $response["hasAccess"] = false;
            return;
        } else {
            $response["hasAccess"] = true;
        }

        $stmt = $this->conn->prepare("SELECT U.user_id, U.email, U.username, U.phone, U.gender, U.name, U.date_of_birthday, U.gender, U.country_code, U.image, U.last_time_online, U.account_online, U.account_private, U.account_closed, U.account_verified, U.bio, U.status, U.website, U.joining_date, U.gradient_colour_1, U.gradient_colour_2, U.message_privacy, U.show_birthday, U.show_business_address, U.show_business_email, U.show_business_phone, U.show_business_hq, U.show_country, U.show_email, U.show_founded_date, U.show_gender, U.show_join_date, U.show_phone, U.show_user_online, U.show_website, U.business_address, U.business_categories, U.business_email, U.business_location, U.business_phone, U.business_postal_code, U.notify_live_video, U.notify_message, U.notify_message_request, U.notify_new_login, U.notify_post_comment, U.notify_post_comment_interaction, U.notify_post_interaction, U.notify_post_mention, U.notify_post_of_you, U.notify_request_accepted, U.notify_new_follower, U.notify_bio_mention, U.notify_product_announcements, U.notify_support_request, U.featured_profiles, U.trusted_devices, U.business_founded_date, U.authentication_type, U.business_account,
            (SELECT 1 FROM user_followers UF
            WHERE UF.follower = ?
            AND UF.following = ?
            AND UF.type = 1) is_following, 
            (SELECT 1 FROM user_followers UF
            WHERE UF.follower = ?
            AND UF.following = ?
            AND UF.type = 0) is_requested, 
            (SELECT count(*) FROM user_followers UF
            WHERE UF.follower = ?
            AND UF.type = 1) following, 
            (SELECT count(*) FROM user_followers UF
            WHERE UF.following = ?
            AND UF.type = 1) followers, 
            (SELECT count(*) FROM posts P
            WHERE P.owner_id = ?
            AND P.post_deleted = 0
            AND P.post_archived = 0) no_posts, 
            (SELECT 1 FROM blocked_users BU
            WHERE (BU.owner_id = ? AND BU.user_id = ?)
            OR (BU.owner_id = ? AND BU.user_id = ?)) is_blocked
            FROM users U
            WHERE U.user_id = ?
            LIMIT 1");
            $stmt->bind_param("iiiiiiiiiiii", $my_uid, $user_id, $my_uid, $user_id, $user_id, $user_id, $user_id, $user_id, $my_uid, $my_uid, $user_id, $user_id);
        
        if ($stmt->execute()) {
            $dataRows = fetchData($stmt);
            $userData = json_decode(json_encode($dataRows[0]));

            $featuredProfiles = explode(",", $userData->featured_profiles);
            $user_ids = array();
            foreach ($featuredProfiles as $value) {
                $stmt = $this->conn->prepare("SELECT user_id, username, name, image, account_online, account_private, account_verified,
                    (SELECT 1 FROM user_followers UF WHERE UF.follower = ? AND UF.following = ? AND UF.type = 1) is_following, 
                    (SELECT 1 FROM user_followers UF WHERE UF.follower = ? AND UF.following = ? AND UF.type = 0) is_requested
                    FROM users WHERE user_id = ?");
                $stmt->bind_param("iiiii", $my_uid, $value, $my_uid, $value, $value);
                if ($stmt->execute()) {
                    $dataFeatured = fetchData($stmt);
                    if (count($dataFeatured) > 0) {
                        $userObj = json_decode(json_encode($dataFeatured[0]));
                        $user_ids[] = $userObj;
                    }
                }
            }
            $userData->featured_profiles = $user_ids;

            if ($my_uid != $user_id) {
                $stmt = $this->conn->prepare("SELECT COUNT(U.user_id) AS mutual_followers FROM users U
                    INNER JOIN user_followers UFF ON UFF.follower = ?
                    INNER JOIN user_followers UF ON UF.follower = UFF.following
                    WHERE U.user_id = UF.follower AND UF.following=?");
                $stmt->bind_param("ii", $my_uid, $userData->user_id);
                $stmt->execute();
                $mutual_followers = json_decode(json_encode(fetchData($stmt)[0]))->mutual_followers;
                $userData->mutual_followers = $mutual_followers;
    
                $stmt = $this->conn->prepare("SELECT U.username, U.image, U.user_id FROM users U
                    INNER JOIN user_followers UFF ON UFF.follower = ?
                    INNER JOIN user_followers UF ON UF.follower = UFF.following
                    WHERE U.user_id = UF.follower AND UF.following=?
                    LIMIT 3");
                $stmt->bind_param("ii", $my_uid, $userData->user_id);
                $stmt->execute();
                $mutual_followers_data = fetchData($stmt);
                $userData->mutual_followers_data = $mutual_followers_data;
            }
            
            if($my_uid == $user_id) {
                $stmt = $this->conn->prepare("SELECT S.type, S.id, S.timestamp,
                    CASE S.type WHEN 1 THEN (SELECT U.username FROM users U WHERE U.user_id=S.id) END AS username,
                    CASE S.type WHEN 1 THEN (SELECT U.name FROM users U WHERE U.user_id=S.id) END AS name,
                    CASE S.type WHEN 1 THEN (SELECT U.image FROM users U WHERE U.user_id=S.id) END AS image,
                    CASE S.type WHEN 1 THEN (SELECT U.account_verified FROM users U WHERE U.user_id=S.id) END AS account_verified,
                    CASE S.type WHEN 1 THEN (SELECT U.account_private FROM users U WHERE U.user_id=S.id) END AS account_private,
                    CASE S.type WHEN 1 THEN (SELECT 1 FROM user_followers uf WHERE uf.following = S.id AND uf.follower = ? AND uf.type = 1) END AS is_following,
                    CASE S.type WHEN 1 THEN (SELECT 1 FROM user_followers uf WHERE uf.following = S.id AND uf.follower = ? AND uf.type = 0) END AS is_requested,
                    CASE S.type WHEN 2 THEN (SELECT H.hashtag FROM hashtags H WHERE H.hashtag_id=S.id) END AS hashtag,
                    CASE S.type WHEN 2 THEN (
                        SELECT COUNT(PH.post_id) FROM hashtags H
                        INNER JOIN post_hashtags PH ON PH.hashtag_id = H.hashtag_id
                        INNER JOIN posts P ON P.post_id = PH.post_id
                        INNER JOIN users U ON P.owner_id = U.user_id
                        WHERE H.hashtag_id=S.id AND P.post_deleted=0 AND P.post_archived=0 AND U.account_closed = 0
                        AND ((SELECT 1 FROM user_followers UF WHERE UF.following = P.owner_id AND UF.follower = ? AND UF.type = 1) = 1 OR U.account_private = 0)
                    ) END AS hashtag_size
                    FROM searches S
                    WHERE S.owner_id = ?
                    GROUP BY S.id
                    ORDER BY S.timestamp DESC
                    LIMIT 10");
                $stmt->bind_param("iiii", $my_uid, $my_uid, $my_uid, $my_uid);
                $stmt->execute();
                $search_history = fetchData($stmt);
                $userData->search_history = $search_history;
            }

            $stmt->close();
            unset($userData->{"password"});
            unset($userData->{"email_emergency"});
            unset($userData->{"security_codes"});
            if($my_uid == $userData->user_id && $my_uid != 0) {
                $response["userData"] = $userData;
                $response["type"] = 200;
                return $response;
            } else {
                unset($userData->{"authentication_type"});
                unset($userData->{"trusted_devices"});
                unset($userData->{"notify_live_video"});
                unset($userData->{"notify_message"});
                unset($userData->{"notify_message_request"});
                unset($userData->{"notify_new_login"});
                unset($userData->{"notify_post_comment"});
                unset($userData->{"notify_post_comment_interaction"});
                unset($userData->{"notify_post_interaction"});
                unset($userData->{"notify_post_mention"});
                unset($userData->{"notify_post_of_you"});
                unset($userData->{"notify_request_accepted"});
                unset($userData->{"notify_new_follower"});
                unset($userData->{"notify_bio_mention"});
                unset($userData->{"notify_product_announcements"});
                unset($userData->{"notify_support_request"});
                if($userData->business_account == 0) {
                    unset($userData->{"show_business_address"});
                    unset($userData->{"business_address"});
                    unset($userData->{"business_postal_code"});
                    unset($userData->{"show_business_email"});
                    unset($userData->{"business_email"});
                    unset($userData->{"show_business_phone"});
                    unset($userData->{"business_phone"});
                    unset($userData->{"show_business_hq"});
                    unset($userData->{"business_location"});
                    unset($userData->{"show_founded_date"});
                    unset($userData->{"business_founded_date"});
                    unset($userData->{"business_categories"});
                } else {
                    if($userData->business_categories == NULL) {
                        unset($userData->{"business_categories"});
                    }
                    if($userData->show_business_address != 1) {
                        unset($userData->{"business_address"});
                        unset($userData->{"business_postal_code"});
                    }
                    if($userData->show_business_email != 1) {
                        unset($userData->{"business_email"});
                    }
                    if($userData->show_business_phone != 1) {
                        unset($userData->{"business_phone"});
                    }
                    if($userData->show_business_hq != 1) {
                        unset($userData->{"business_location"});
                    }
                    if($userData->show_founded_date != 1) {
                        unset($userData->{"business_founded_date"});
                    }
                }
                if($userData->show_birthday != 1) {
                    unset($userData->{"date_of_birthday"});
                }
                if($userData->show_country != 1) {
                    unset($userData->{"country_code"});
                }
                if($userData->show_email != 1) {
                    unset($userData->{"email"});
                }
                if($userData->show_gender != 1) {
                    unset($userData->{"gender"});
                }
                if($userData->show_join_date != 1) {
                    unset($userData->{"joining_date"});
                }
                if($userData->show_phone != 1) {
                    unset($userData->{"phone"});
                }
                if($userData->show_user_online != 1) {
                    unset($userData->{"account_online"});
                    unset($userData->{"last_time_online"});
                }
                if($userData->show_website != 1) {
                    unset($userData->{"website"});
                }
                $response["userData"] = $userData;
                $response["type"] = 200;
                return $response;
            }
        } else {
            $response["type"] = 101;
            return $response;
        }
        return $response;

    }

}

?>
