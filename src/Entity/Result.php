<?php

namespace App\Entity;

use App\Repository\ResultRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ResultRepository::class)
 */
class Result
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     */
    private $announcementDate;

    /**
     * @ORM\OneToOne(targetEntity=Draw::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $draw;

    /**
     * @ORM\OneToOne(targetEntity=Ticket::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $winnerTicket;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnnouncementDate(): ?\DateTimeInterface
    {
        return $this->announcementDate;
    }

    public function setAnnouncementDate(\DateTimeInterface $announcementDate): self
    {
        $this->announcementDate = $announcementDate;

        return $this;
    }

    public function getDraw(): ?Draw
    {
        return $this->draw;
    }

    public function setDraw(Draw $draw): self
    {
        $this->draw = $draw;

        return $this;
    }

    public function getWinnerTicket(): ?Ticket
    {
        return $this->winnerTicket;
    }

    public function setWinnerTicket(Ticket $winnerTicket): self
    {
        $this->winnerTicket = $winnerTicket;

        return $this;
    }
}
