<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MeasurementRepository")
 */
class Measurement
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $sniffer;

    /**
     * @ORM\Column(type="integer")
     */
    private $time;

    /**
     * @ORM\Column(type="integer")
     */
    private $point;

    /**
     * @ORM\Column(type="integer")
     */
    private $trip;

    /**
     * @ORM\Column(type="float")
     */
    private $latitude;

    /**
     * @ORM\Column(type="float")
     */
    private $longitude;

    /**
     * @ORM\Column(type="integer")
     */
    private $pm2_5;

    /**
     * @ORM\Column(type="integer")
     */
    private $pm10;

    /**
     * @ORM\Column(type="integer")
     */
    private $n;

    /**
     * @ORM\Column(type="integer")
     */
    private $p;

    /**
     * @ORM\Column(type="float")
     */
    private $t;

    /**
     * @ORM\Column(type="integer")
     */
    private $rh;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSniffer(): ?string
    {
        return $this->sniffer;
    }

    public function setSniffer(string $sniffer): self
    {
        $this->sniffer = $sniffer;

        return $this;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getPoint(): ?int
    {
        return $this->point;
    }

    public function setPoint(int $point): self
    {
        $this->point = $point;

        return $this;
    }

    public function getTrip(): ?int
    {
        return $this->trip;
    }

    public function setTrip(int $trip): self
    {
        $this->trip = $trip;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getPm25(): ?int
    {
        return $this->pm2_5;
    }

    public function setPm25(int $pm2_5): self
    {
        $this->pm2_5 = $pm2_5;

        return $this;
    }

    public function getPm10(): ?int
    {
        return $this->pm10;
    }

    public function setPm10(int $pm10): self
    {
        $this->pm10 = $pm10;

        return $this;
    }

    public function getN(): ?int
    {
        return $this->n;
    }

    public function setN(int $n): self
    {
        $this->n = $n;

        return $this;
    }

    public function getP(): ?int
    {
        return $this->p;
    }

    public function setP(int $p): self
    {
        $this->p = $p;

        return $this;
    }

    public function getT(): ?float
    {
        return $this->t;
    }

    public function setT(float $t): self
    {
        $this->t = $t;

        return $this;
    }

    public function getRh(): ?int
    {
        return $this->rh;
    }

    public function setRh(int $rh): self
    {
        $this->rh = $rh;

        return $this;
    }
}
