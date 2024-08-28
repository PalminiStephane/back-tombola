<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PurchaseRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass=PurchaseRepository::class)
 */
class Purchase
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     *  @ORM\ManyToOne(targetEntity=User::class, inversedBy="purchases")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Draws::class, inversedBy="purchases")
     * @ORM\JoinColumn(nullable=false)
     */
    private $draw;

    /**
     * @ORM\Column(type="integer")
     */
    private $quantity;

    /**
     * @ORM\Column(type="date")
     */
    private $purchaseDate;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $status;

     /**
     * @ORM\OneToMany(targetEntity=Tickets::class, mappedBy="purchase", cascade={"persist", "remove"})
     */
    private $tickets;

     /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripeSessionId;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
    }

    /**
     * @return Collection|Tickets[]
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Tickets $ticket): self
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets[] = $ticket;
            $ticket->setPurchase($this);
        }

        return $this;
    }

    public function removeTicket(Tickets $ticket): self
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getPurchase() === $this) {
                $ticket->setPurchase(null);
            }
        }

        return $this;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDraw(): ?Draws
    {
        return $this->draw;
    }

    public function setDraw(?Draws $draw): self
    {
        $this->draw = $draw;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeInterface
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(\DateTimeInterface $purchaseDate): self
    {
        $this->purchaseDate = $purchaseDate;

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

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): self
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    public function __toString()
    {
        return sprintf('Purchase #%d - %s', $this->getId(), $this->getStatus());
    }
}
