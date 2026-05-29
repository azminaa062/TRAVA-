<?php
if(session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    // Use relative path to avoid issues with different server configurations
    $depth = substr_count(str_replace('\\','/',__FILE__), '/') - substr_count(str_replace('\\','/',realpath($_SERVER['DOCUMENT_ROOT'])), '/');
    header("Location: ../../login.php");
    exit;
}
