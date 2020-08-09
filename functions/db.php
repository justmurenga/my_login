<?php

$servername = 'localhost';
$db_user = 'root';
$db_pass = 'mysql';
$db_name = 'login_db';

$conn = mysqli_connect($servername,$db_user,$db_pass,$db_name);

function row_count($result){

    return mysqli_num_rows($result);
}

// cleaning the db data
function escape($string){
    global $conn;
    return mysqli_real_escape_string($conn, $string);


}
function query($query){
    global $conn;
    return mysqli_query($conn,$query);
}

function confirm($result){
    global $conn;

    if (!$result){
        die("QUERY FAILED " . mysqli_error($conn));
    }

}

function fetch_array($result){


    return mysqli_fetch_array($result);


}
