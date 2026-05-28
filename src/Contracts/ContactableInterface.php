<?php

declare(strict_types=1);


interface EmailContactableInterface
{
    public function getEmail(): string;
}

interface PhoneContactableInterface
{
    public function getPhone(): string;
}

interface ContactableInterface extends EmailContactableInterface, PhoneContactableInterface
{
}
