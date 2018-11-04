<?php

namespace App\Field;

trait CountryField
{
    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string")
     */
    private $country;

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $name): void
    {
        $this->country = $name;
    }
}