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

    public function checkApi($api_key, $api_password)
    {
        
        $sqlQuery = "SELECT 1 FROM api_clients WHERE api_key = ? AND api_password = ?";
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

    public function create()
    {
        $quizzDataI = '{"title":"Quizz Test 1","visibility":1}';
        $questionDataI = '[{"content":"Northampton location","experience":"7400","image_url":"imagehssgfsfsgsgssg","answer1":"United Kingdom","answer2":"England","answer3":"Romania","answer4":"Belgium","answer1_correct":1,"answer2_correct":1,"answer3_correct":0,"answer4_correct":0,"time_question":5,"time_answer":5,"time_results":5},{"content":"UoN","experience":"1400","image_url":"unkbssghsshsgg","answer1":"Uni","answer2":"University of Northampton","answer3":"Uni of Birmingham","answer4":"Uni of London","answer1_correct":1,"answer2_correct":1,"answer3_correct":0,"answer4_correct":0,"time_question":5,"time_answer":5,"time_results":5}]';
        $UID = 100002;
        $this->publishQuizz($quizzDataI, $questionDataI, $UID);
    }    

    public function publishQuizz($quizzData, $quizzQuestionsData, $uid)
    {
        
        // prepare the response array
        $response = array();
        $response["error"] = false;

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

}

$db = new DbHandler();
$db->create();

?>
