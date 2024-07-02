<?php

namespace App\Entity;

use App\Repository\PaiementsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PaiementsRepository::class)
 */
class Paiements
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Transactions::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $transaction;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $amountPaid;

    /**
     * @ORM\Column(type="date")
     */
    private $paymentDate;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $paymentMethod;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransaction(): ?Transactions
    {
        return $this->transaction;
    }

    public function setTransaction(Transactions $transaction): self
    {
        $this->transaction = $transaction;

        return $this;
    }

    public function getAmountPaid(): ?string
    {
        return $this->amountPaid;
    }

    public function setAmountPaid(string $amountPaid): self
    {
        $this->amountPaid = $amountPaid;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeInterface $paymentDate): self
    {
        $this->paymentDate = $paymentDate;

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

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }
}
