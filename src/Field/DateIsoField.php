<?php

namespace App\Field;

trait DateIsoField
{
    /**
     * @var string
     *
     * @ORM\Column(name="date", type="string")
     */
    private $date;

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
    }
}
