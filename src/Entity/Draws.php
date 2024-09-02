<?php

namespace App\Entity;

use App\Repository\DrawsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DrawsRepository::class)
 */
class Draws
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="date")
     */
    private $drawDate;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $ticketPrice;

    /**
     * @ORM\Column(type="integer")
     */
    private $ticketsAvailable;

    /**
     * @ORM\Column(type="integer")
     */
    private $totalTickets;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $status;

    /**
     * @ORM\OneToMany(targetEntity=Tickets::class, mappedBy="draw")
     */
    private $tickets;

    /**
     * @ORM\Column(type="dateinterval", nullable=true)
     */
    private $ticketValidityDuration;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $winners;

     /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $winnerName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $prize;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $picture;

    /**
    * @ORM\OneToMany(targetEntity=Purchase::class, mappedBy="draw", cascade={"persist", "remove"})
    */
    private $purchases;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
        $this->purchases = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDrawDate(): ?\DateTimeInterface
    {
        return $this->drawDate;
    }

    public function setDrawDate(\DateTimeInterface $drawDate): self
    {
        $this->drawDate = $drawDate;

        return $this;
    }

    public function getTicketPrice(): ?string
    {
        return $this->ticketPrice;
    }

    public function setTicketPrice(string $ticketPrice): self
    {
        $this->ticketPrice = $ticketPrice;

        return $this;
    }

    public function getTicketsAvailable(): ?int
    {
        return $this->ticketsAvailable;
    }

    public function setTicketsAvailable(int $ticketsAvailable): self
    {
        $this->ticketsAvailable = $ticketsAvailable;

        return $this;
    }

    public function getTotalTickets(): ?int
    {
        return $this->totalTickets;
    }

    public function setTotalTickets(int $totalTickets): self
    {
        $this->totalTickets = $totalTickets;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, Tickets>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Tickets $ticket): self
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets[] = $ticket;
            $ticket->setDraw($this);
        }

        return $this;
    }

    public function removeTicket(Tickets $ticket): self
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getDraw() === $this) {
                $ticket->setDraw(null);
            }
        }

        return $this;
    }

    public function getTicketValidityDuration(): ?\DateInterval
    {
        return $this->ticketValidityDuration;
    }

    public function setTicketValidityDuration(?\DateInterval $ticketValidityDuration): self
    {
        $this->ticketValidityDuration = $ticketValidityDuration;

        return $this;
    }

    public function getWinners(): ?string
    {
        return $this->winners;
    }

    public function setWinners(?string $winners): self
    {
        $this->winners = $winners;

        return $this;
    }

    public function getWinnerName(): ?string
    {
        return $this->winnerName;
    }

    public function setWinnerName(?string $winnerName): self
    {
        $this->winnerName = $winnerName;

        return $this;
    }

    public function getPrize(): ?string
    {
        return $this->prize;
    }

    public function setPrize(?string $prize): self
    {
        $this->prize = $prize;

        return $this;
    }


    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * @return Collection<int, Purchase>
     */
    public function getPurchases(): Collection
    {
        return $this->purchases;
    }

    public function addPurchase(Purchase $purchase): self
    {
        if (!$this->purchases->contains($purchase)) {
            $this->purchases[] = $purchase;
            $purchase->setDraw($this);
        }

        return $this;
    }

    public function removePurchase(Purchase $purchase): self
    {
        if ($this->purchases->removeElement($purchase)) {
            // set the owning side to null (unless already changed)
            if ($purchase->getDraw() === $this) {
                $purchase->setDraw(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        // Retourne une représentation textuelle de l'entité. 
        // Par exemple, ici, vous pourriez retourner le titre de la tombola.
        return $this->getTitle(); // ou une autre propriété que vous souhaitez afficher
    }
}
