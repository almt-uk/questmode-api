<?php

class DbHandlerWeb {

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

    public function initializeAPI($api_key, $api_password)
    {
        
        $sqlQuery = "SELECT clearance_level FROM api_clients WHERE api_key = ? AND api_password = ?";
        $stmt = $this->conn->prepare($sqlQuery);
        $stmt->bind_param("ss", $api_key, $api_password);
        if ($stmt->execute()) {
            $dataRows = fetchData($stmt);
            if (count($dataRows) == 1) {
                $this->validSession = true;
                $this->clearance_level = $dataRows[0]["clearance_level"];
                return $this->clearance_level;
            } else {
                return 0;
            }
        } else {
            return 0;
        }

    }

    public function publishQuizz($quizzData, $quizzQuestionsData, $uid)
    {
        
        // prepare the response array
        $response = array();
        $response["error"] = false;

        if(!$this->validSession)
        {
            $response["error"] = true;
            return $response;
        }

        // parse quizz data
        $quizzData = json_decode($quizzData);
        $quizzData->title;
        $quizzData->visibility;
        if (!empty($quizzData->title) && ($quizzData->visibility == 0 || $quizzData->visibility == 1))
        {
            // no error
        }
        else
        {
            $response["error"] = true;
            $response["errorQuizzData"] = "Please check the quizz title and/or visibility";
        }

        // parse quizz questions data
        $quizzQuestionsData = json_decode($quizzQuestionsData);
        // going through each question
        foreach ($quizzQuestionsData as $questionData) {
            $questionData->content;
            $questionData->experience;
            $questionData->image_url;
            $questionData->answer1;
            $questionData->answer2;
            $questionData->answer3;
            $questionData->answer4;
            $questionData->answer1_correct;
            $questionData->answer2_correct;
            $questionData->answer3_correct;
            $questionData->answer4_correct;
            $questionData->time_question;
            $questionData->time_answer;
            $questionData->time_results;
            if (empty($questionData->content) || empty($questionData->answer1) || empty($questionData->answer2)
                || empty($questionData->answer3) || empty($questionData->answer4))
            {
                $response["error"] = true;
                $response["errorQuestions"] = "Please check the questions data";
            }
            if ($questionData->time_question >= 20 || $questionData->time_answer >= 20 ||
               $questionData->time_results >= 20)
            {
                $response["error"] = true;
                $response["errorQuestions"] = "Please check the questions data";
            }
        }

        if (!$response["error"])
        {
            //upload quizz data
            $insert_id = $this->quizzCreate(json_encode($quizzData), $uid);
            if($insert_id == NULL)
            {
                $response["error"] = true;
                $response["errorCreate"] = "Please try again";
            }
            else
            {
                $succeedQuestion = $this->quizzCreateQuestions(json_encode($quizzQuestionsData), $insert_id);
                if(!$succeedQuestion)
                {
                    $response["error"] = true;
                    $response["errorCreate"] = "Please try again";
                }
            }

        }

        return $response;

    }

    private function quizzCreate($quizzData, $uid)
    {
        $quizzData = json_decode($quizzData);
        $sqlQuery = "INSERT INTO quizzes SET title = ?, visibility = ?, creator_id = ?";
        $stmt = $this->conn->prepare($sqlQuery);
        $stmt->bind_param("sii", $quizzData->title, $quizzData->visibility, $uid);
        if ($stmt->execute()) {
            return $stmt->insert_id;
        } else {
            $stmt->close();
            return false;
        }
    }

    private function quizzCreateQuestions($quizzQuestionsData, $insert_id)
    {
        $quizzQuestionsData = json_decode($quizzQuestionsData);
        // going through each question
        foreach ($quizzQuestionsData as $questionData) {
            $questionData->content;
            $questionData->experience;
            $questionData->image_url;
            $questionData->answer1;
            $questionData->answer2;
            $questionData->answer3;
            $questionData->answer4;
            $questionData->answer1_correct;
            $questionData->answer2_correct;
            $questionData->answer3_correct;
            $questionData->answer4_correct;
            $questionData->time_question;
            $questionData->time_answer;
            $questionData->time_results;
            $sqlQuery = "INSERT INTO questions
                SET content = ?, experience = ?, image = ?, quizz_id = ?,
                time_question = ?, time_answer = ?, time_results = ?";
            $stmt = $this->conn->prepare($sqlQuery);
            $stmt->bind_param("sisiiii", $questionData->content, $questionData->experience,  $questionData->image_url,
                $insert_id, $questionData->time_question, $questionData->time_answer, $questionData->time_results);
            if ($stmt->execute()) {
                $question_id = $stmt->insert_id;
                $sqlQuery = "INSERT INTO answers
                    SET question_id = ?, content = ?, is_right = ?, order_id = ?";
                $stmt = $this->conn->prepare($sqlQuery);
                $answerCount = 1;
                $stmt->bind_param("isii", $question_id, $questionData->answer1,
                    $questionData->answer1_correct, $answerCount);
                if (!$stmt->execute()) {
                    $stmt->close();
                    return false;
                }
                $answerCount++;
                $sqlQuery = "INSERT INTO answers
                    SET question_id = ?, content = ?, is_right = ?, order_id = ?";
                $stmt = $this->conn->prepare($sqlQuery);
                $stmt->bind_param("isii", $question_id, $questionData->answer2,
                    $questionData->answer2_correct, $answerCount);
                if (!$stmt->execute()) {
                    $stmt->close();
                    return false;
                }
                $answerCount++;
                $sqlQuery = "INSERT INTO answers
                    SET question_id = ?, content = ?, is_right = ?, order_id = ?";
                $stmt = $this->conn->prepare($sqlQuery);
                $stmt->bind_param("isii", $question_id, $questionData->answer3,
                    $questionData->answer3_correct, $answerCount);
                if (!$stmt->execute()) {
                    $stmt->close();
                    return false;
                }
                $answerCount++;
                $sqlQuery = "INSERT INTO answers
                    SET question_id = ?, content = ?, is_right = ?, order_id = ?";
                $stmt = $this->conn->prepare($sqlQuery);
                $stmt->bind_param("isii", $question_id, $questionData->answer4,
                    $questionData->answer4_correct, $answerCount);
                if (!$stmt->execute()) {
                    $stmt->close();
                    return false;
                }
            } else {
                $stmt->close();
                return false;
            }
        }
        
        $stmt->close();
        return true;
    }

    public function registerUser($email, $password, $username, $institutionName, $countryCode, $isTeacher)
    {
        
        // prepare the response array
        $response = array();
        $response["error"] = false;

        if(!$this->validSession)
        {
            $response["error"] = true;
            return $response;
        }

        $sqlQuery = "SELECT institution_id FROM educational_institutions WHERE name=?";
        $stmt = $this->conn->prepare($sqlQuery);
        $stmt->bind_param("s", $institutionName);
        if ($stmt->execute())
        {
            $institution_id = fetchData($stmt)[0];
            if($institution_id != NULL)
            {
                // insert
                $sqlQuery = "INSERT INTO educational_institutions SET name=?, country_code=?";
                $stmt = $this->conn->prepare($sqlQuery);
                $stmt->bind_param("ss", $institutionName);
                if ($stmt->execute())
                {
                }

            }
            else if($isTeacher)
            {
                // create the instituion
            }
            else
            {
                // institution don't exist
                // not teacher -> you can not create the instituion
                // return error 
                $response["error"] = true;
                return $response;  
            }                
        }
        else
        {
            $response["error"] = true;
            return $response;              
        }

    }

    private function getIsTeacher($email)
    {
        $domain_name = substr(strrchr($email, "@"), 1);
        if (substr($domain_name, 0, 3) != "my.")
        {
            return true;
        }
        else
        {
            return false;
        }
    }

}

?>
