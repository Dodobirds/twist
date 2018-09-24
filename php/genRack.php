<?php

session_start();
$currentRack;
$rackCombinations = array();
$submittedWords = array();
$countArray = [0,0,0,0];

function reset_global(){
    global $currentRack;
    global $rackCombinations;
    global $countArray;
    global $submittedWords;
    $currentRack = '';
    $rackCombinations = array();
    $countArray = [0,0,0,0];
    $submittedWords = array();
    #session_destroy();
    $_SESSION['rack'] = '';
    $_SESSION['prev'] = array();
    $_SESSION['count'] = array();
}

function generate_rack($n){
  $tileBag = "AAAAAAAAABBCCDDDDEEEEEEEEEEEEFFGGGHHIIIIIIIIIJKLLLLMMNNNNNNOOOOOOOOPPQRRRRRRSSSSTTTTTTUUUUVVWWXYYZ";
  $rack_letters = substr(str_shuffle($tileBag), 0, $n);
  $temp = str_split($rack_letters);
  sort($temp);
  return implode($temp);
};
function query_rack($rack) {
    $dbhandle  = new PDO("sqlite:scrabble.sqlite") or die("Failed to open DB");
    if (!$dbhandle) die ($error);
    $query = 'SELECT words FROM racks WHERE rack="'. $rack . '"';
    $statement = $dbhandle->prepare($query);
    $statement->execute();
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
    #print_r($results);
    return $results;
}
//Ensures that atleast 1 6-length word exists for this rack
function validate_rack() {
    global $currentRack;
    do {
        $s = generate_rack(6);
    } while(!query_rack($s));
    $currentRack = $s;
    $_SESSION['rack'] = $s;
    return $s;
}

function genComb($myrack) {
    global $rackCombinations;
    $racks = [];
    for($i = 0; $i < pow(2, strlen($myrack)); $i++){
        $ans = "";
        for($j = 0; $j < strlen($myrack); $j++){
            //if the jth digit of i is 1 then include letter
            if (($i >> $j) % 2) {
                $ans .= $myrack[$j];
            }
        }
        if (strlen($ans) > 1){
            $racks[] = $ans;
        }
    }
    $racks = array_unique($racks);
    return $racks;
}

function listAllWords($arr){
    global $rackCombinations;
    foreach ($arr as $r) {
        array_push($rackCombinations, query_rack($r));
    }
    clean_racks($rackCombinations);
}

function clean_racks($arr) { #this might be the worst function I have ever written in my life
    global $rackCombinations;
    $temp = array();
    for($a = 0; $a < count($arr); $a++) {
        for($b = 0; $b < count($arr[$a]); $b++) {
            array_push($temp, $arr[$a][$b]);
        }
    }
    $matchArray = array();
    foreach($temp as $words) {
        array_push($matchArray,preg_split("/@@/", implode($words)));
    }
    $temp = array();
    for($a = 0; $a < count($matchArray); $a++) {
        for($b = 0; $b < count($matchArray[$a]); $b++) {
            array_push($temp, $matchArray[$a][$b]);
        }
    }
    $func = function ($value) {
        return (strlen($value) > 2);
    };
    $rackCombinations = array_unique($temp);
    $rackCombinations = array_filter($rackCombinations, $func);
}

function countWords(){
    global $countArray;
    global $rackCombinations;
    foreach($rackCombinations as $r) {
        if (strlen($r) == 3){
            $countArray[0]+= 1;
        }
        else if (strlen($r) == 4){
            $countArray[1]+= 1;
        }
        else if (strlen($r) == 5){
            $countArray[2]+= 1;
        }
        else if (strlen($r) == 6){
            $countArray[3]+= 1;
        }
    }
}

function checkCombination($word) {
    $copy = $word;
    $copy = str_split($copy);
    sort($copy);
    $copy = implode('', $copy);

    $lis = genComb($_SESSION['rack']);
    foreach ($lis as $l) {
        if ($copy === $l) {
            return checkWord($word, $copy);
        }
    }
    return false;
}

function checkWord($word, $newRack) {
    $lis = query_rack($newRack);
    $arr = array();
    foreach($lis as $l) {
        $arr = preg_split("/@@/", implode('',$l));
    }
    foreach ($arr as $a) {
        if ($word == $a) {
            return true;
        }
    }
    return false;

}

function contains($arr, $val) {
    foreach ($arr as $l) {
        if ($val === $l) {
            return true;
        }
    }
    return false;
}

$verb = $_SERVER["REQUEST_METHOD"];
$response = new StdClass();

switch ($verb) {
case 'GET':
    reset_global();
    $currentRack = validate_rack();
    listAllWords(genComb($currentRack));
    countWords();
    $_SESSION['count'] = $countArray;
    echo json_encode(Array("word"=> $currentRack, "count"=> $countArray));
    break;
case 'POST':
    $response_body = file_get_contents("php://input");
    $response_body = preg_replace("/[^A-Z]/", "", $response_body);

    if (!contains($_SESSION['prev'],$response_body) && checkCombination($response_body)){
        array_push($_SESSION['prev'], $response_body);
        $_SESSION['count'][(strlen($response_body) - 3)]--;

    }
    echo json_encode($_SESSION['count']);
    break;
default:
    #parse_str(file_get_contents("php://input"), $response->payload);
    #echo json_encode($response);
    #break;
};

?>
