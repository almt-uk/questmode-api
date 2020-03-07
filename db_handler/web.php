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

    public function publishQuizz($quizzData, $quizzQuestionsData)
    {
        // parse quizz data
        $quizzData = json_decode($quizzData);
        $quizzData->title;
        $quizzData->visibility;

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
        }

    }

}

?>
