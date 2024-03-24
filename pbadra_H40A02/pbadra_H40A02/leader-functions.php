<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//LOGIN VALIDATION
function validate_email(string $e): array
{
    $errors = [];

    if (strlen(trim($e)) == 0)
        array_push($errors, "Email Must Be Entered");
    else {
        if (!preg_match("/^[^@]*@[^@]*$/", $e))
            array_push($errors, "Email Must Contain Only One @ Symbol");
        if (!preg_match("/^[^@]+@[^@]*$/", $e))
            array_push($errors, "Email Must Contain Text Before The @ Symbol");
        if (!preg_match("/^[^@]+@([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/", $e))
            array_push($errors, "Email Must Contain Domain Name After The @ Symbol");
        if (!preg_match("/^[^@]+@([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/", $e))
            array_push($errors, "Email Must Have A Top-Level Domain Of Minimum Two Characters");
        if (!preg_match("/^[a-zA-Z].*[a-zA-Z]$/", $e))
            array_push($errors, "Email Must Start And End With Letters");
    }
    return $errors;
}

function validate_password(string $p): array
{
    $errors = [];

    if (strlen(trim($p)) == 0)
        array_push($errors, "Password Must Be Entered");
    else {
        if (!preg_match("/\d/", $p))
            array_push($errors, "Password Must Contain A Number");
        if (!preg_match("/[a-z]/", $p))
            array_push($errors, "Password Must Contain A Lowercase Letter");
        if (!preg_match("/[A-Z]/", $p))
            array_push($errors, "Password Must contain An Uppercase Letter");
        if (!preg_match("/^\S*$/", $p))
            array_push($errors, "Password Must Not Contain Spaces");
        if (!preg_match("/[^a-zA-Z\d]/", $p))
            array_push($errors, "Password Must Contain A Special Character");
        if (!preg_match("/^.{8,16}$/", $p))
            array_push($errors, "Password Must Be 8-16 Characters Long");
    }
    return $errors;
}


//ADD PLAYER/UPDATE PLAYER VALIDATION
function validate_name(string $n): array
{
    $errors = [];
    if (strlen(trim($n)) == 0)
        array_push($errors, "Must Be Entered");
    else {
        if (!preg_match("/^[a-zA-Z-' ]*$/", $n))
            array_push($errors, "Must Only Contain Letters, Dashes, Apostrophes And Spaces");
        if (!preg_match("/^[a-zA-Z](?:.*[a-zA-Z])?$/", $n))
            array_push($errors, "Must Start And End With Letters");
    }
    return $errors;
}

function validate_nickname(string $n, array $p): array
{
    $errors = [];
    if (strlen(trim($n)) == 0)
        array_push($errors, "Nickname Must Be Entered");
    else {
        if (!preg_match("/^[a-zA-Z-' ]*$/", $n))
            array_push($errors, "Nickname Must Only Contain Letters, Dashes, Apostrophes And Spaces");
        if (!preg_match("/^[a-zA-Z](?:.*[a-zA-Z])?$/", $n))
            array_push($errors, "Nickname Must Start And End With Letters");
        foreach ($p as $pl) {
            if (strtolower(trim($n)) === strtolower($pl->get_nickname())) {
                array_push($errors, "Nickname Aleardy Exists");
                break;
            }
        }
    }

    return $errors;
}


function validate_city(string $c): array
{
    $errors = [];
    if (strlen(trim($c)) == 0) {
        array_push($errors, "City Must Be Entered");
    } else {
        if (!preg_match("/^[a-zA-Z- ]*$/", $c))
            array_push($errors, "City Must Only Contain Letters, Dashes and Spaces");
        if (!preg_match("/^[a-zA-Z](?:.*[a-zA-Z])?$/", $c))
            array_push($errors, "City Must Start And End With Letters");
    }
    return $errors;
}

function validate_country(string $c): array
{
    $errors = [];
    if ($c === "Select a Country")
        array_push($errors, "A Country Must be Selected");
    else {
        if ($c !== "Canada" && $c !== "United States" && $c !== "Argentina" && $c !== "Brazil" && $c !== "Ecuador" && $c !== "Peru" && $c !== "Poland")
            array_push($errors, "Invalid Country Selected");
    }
    return $errors;
}

function validate_file(array $f): array
{
    $errors = [];

    if ($f['error'] !== 4) {
        $allowed_extensions = ['png', 'gif', 'jpeg', 'webm', 'jpg'];

        if ($f['error'] === 3)
            array_push($errors, "File Upload Was Unsuccessful");
        if ($f['error'] === 1 || $f['error'] === 2)
            array_push($errors, 'File Size Must Not Exceed 2MB');
        if (!in_array(strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)), $allowed_extensions))
            array_push($errors, "File Must Be An Image Of Type png, gif, jpeg, webm, or jpg");

    }
    return $errors;
}


function compare_Players($a, $b)
{
    $lastNameComparison = strcmp(strtolower($a['lastname']), strtolower($b['lastname']));
    return ($lastNameComparison !== 0) ? $lastNameComparison : strcmp(strtolower($a['firstname']), strtolower($b['firstname']));
}
