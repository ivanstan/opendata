<?php

namespace App\Field;

trait DateField
{
    /**
     * @var \DateTime $date
     * @ORM\Column(name="date", type="datetime", nullable=false)
     */
    private $date;

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }
}