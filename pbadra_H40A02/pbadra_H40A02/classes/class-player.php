<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
class Player
{
    function __construct(
        private int $id,
        private string $first_name,
        private string $last_name,
        private string $nickname,
        private string $city,
        private Country $country,
        private bool $profressional = false
    ) {
    }

    function set_id(int $identifier): void
    {
        $this->id = $identifier;
    }

    function get_id(): int
    {
        return $this->id;
    }

    function set_first_name(string $f): void
    {
        $this->first_name = $f;
    }

    function get_first_name(): string
    {
        return $this->first_name;
    }

    function set_last_name(string $l): void
    {
        $this->last_name = $l;
    }

    function get_last_name(): string
    {
        return $this->last_name;
    }

    function set_nickname(string $n): void
    {
        $this->nickname = $n;
    }

    function get_nickname(): string
    {
        return $this->nickname;
    }

    function set_city(string $c): void
    {
        $this->city = $c;
    }

    function get_city(): string
    {
        return $this->city;
    }

    function set_country(Country $c): void
    {
        $this->country = $c;
    }

    function get_country(): string
    {
        return $this->country->value;
    }
    function set_professional(bool $p): void
    {
        $this->profressional = $p;
    }

    function get_professional(): bool
    {
        return $this->profressional;
    }
}