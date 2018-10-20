<?php

namespace App\Entity;

use App\Field\IdField;
use App\Field\NameField;
use App\Field\TleField;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TleRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Tle
{
    use IdField;
    use NameField;
    use TleField;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="satellite_id", type="integer")
     */
    private $satelliteId;

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function update(): void
    {
        $this->setUpdatedAt(new \DateTime());
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getSatelliteId(): int
    {
        return $this->satelliteId;
    }

    public function setSatelliteId(int $satelliteId): void
    {
        $this->satelliteId = $satelliteId;
    }
}
