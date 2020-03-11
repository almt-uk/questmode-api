<?php

$path = $_SERVER['DOCUMENT_ROOT'];
require_once $path . '/db_handler/web.php';

$db = new DbHandlerWeb();
$db->initializeAPI("xtoAkWqVGp4nDtW6tZL1AaJUCl9I3tYcqjfTBhSu", "PHZ7dh4vHtbJoF7kD2RtZQUxi3opTFeXvpa0Jp7R");

$quizzSessionID = $_SESSION["quizzSessionID"];

  $currentQuestion = $_SESSION["currentQuestion"];

  $quizzData = $_SESSION["quizzData"];
  $quizz_id = $_SESSION["quizzData"]["quizz_id"];
  $title = $_SESSION["quizzData"]["title"];

  $questionRowsData = $_SESSION["questionRowsData"];

  $questionData = $questionRowsData[$currentQuestion];
  $question_id = $questionData["question_id"];
  $experience = $questionData["experience"];
  $content = $questionData["content"];
  $image = $questionData["image"];
  $quizz_id = $questionData["quizz_id"];
  $time_question = $questionData["time_question"];
  $time_answer = $questionData["time_answer"];
  $time_results = $questionData["time_results"];
  $questionAnswersData = $questionData["questionAnswersData"];
  
  $answerDetails1 = $questionAnswersData[0];
  $answer_id1 = $questionOneDetails["answer_id"];
  $question_id1 = $questionOneDetails["question_id"];
  $content1 = $questionOneDetails["content"];
  $is_right1 = $questionOneDetails["is_right"];
  $order_id1 = $questionOneDetails["order_id"];
  
  $answerDetails2 = $questionAnswersData[1];
  $answer_id2 = $questionOneDetails["answer_id"];
  $question_id2 = $questionOneDetails["question_id"];
  $content2 = $questionOneDetails["content"];
  $is_right2 = $questionOneDetails["is_right"];
  $order_id2 = $questionOneDetails["order_id"];
  
  $answerDetails3 = $questionAnswersData[2];
  $answer_id3 = $questionOneDetails["answer_id"];
  $question_id3 = $questionOneDetails["question_id"];
  $content3 = $questionOneDetails["content"];
  $is_right3 = $questionOneDetails["is_right"];
  $order_id3 = $questionOneDetails["order_id"];
  
  $answerDetails4 = $questionAnswersData[3];
  $answer_id4 = $questionOneDetails["answer_id"];
  $question_id4 = $questionOneDetails["question_id"];
  $content4 = $questionOneDetails["content"];
  $is_right4 = $questionOneDetails["is_right"];
  $order_id4 = $questionOneDetails["order_id"];

?>