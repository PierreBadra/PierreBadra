<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Leader
{
    function __construct(
        private string $email,
        private string $name,
        private string $password
    ) {
    }

    function set_email(string $e): void
    {
        $this->email = $e;
    }

    function get_email(): string
    {
        return $this->email;
    }

    function set_name(string $n): void
    {
        $this->name = $n;
    }

    function get_name(): string
    {
        return $this->name;
    }

    function set_password(string $p): void
    {
        $this->password = $p;
    }

    function get_password(): string
    {
        return $this->password;
    }
}