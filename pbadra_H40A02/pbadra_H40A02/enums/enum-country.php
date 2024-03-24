<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

enum Country: string
{
    case select_country = "Select a Country";
    case canada = "Canada";
    case united_states = "United States";
    case argentina = "Argentina";
    case brazil = "Brazil";
    case ecuador = "Ecuador";
    case peru = "Peru";
    case poland = "Poland";
}