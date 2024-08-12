<?php

namespace App\Controller;

use App\Entity\Draws;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LotteryController extends AbstractController
{
    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * @Route("/run-lottery", name="run_lottery")
     */
    public function runLottery(): Response
    {
        // Ici, nous imaginons que vous avez déjà une logique pour obtenir les tombolas à exécuter
        $drawsToExecute = $this->entityManager->getRepository(Draws::class)->findDrawsToExecute();

        foreach ($drawsToExecute as $draw) {
            // Sélection du gagnant
            $winner = $this->selectWinner($draw);
            $prize = $this->selectPrize($draw);

            if ($winner) {
                $draw->setWinners($winner->getName());
                $draw->setPrize($prize);
            } else {
                $this->logger->warning('Aucun gagnant sélectionné pour la tombola ' . $draw->getId());
            }

            $draw->setStatus('closed');
            $this->entityManager->persist($draw);
        }

        $this->entityManager->flush();

        return new Response('Tirage au sort exécuté');
    }

    private function selectWinner(Draws $draw)
    {
        $tickets = $draw->getTickets()->toArray(); // Récupère tous les tickets associés à la tombola

        if (count($tickets) === 0) {
            return null; // Aucun gagnant si personne n'a acheté de ticket
        }

        $winnerTicket = $tickets[array_rand($tickets)]; // Sélectionne un ticket aléatoire
        return $winnerTicket->getUser(); // Supposez que chaque ticket est associé à un utilisateur
    }

    private function selectPrize(Draws $draw)
    {
        return 'Le lot associé à la tombola'; // Remplacez par la logique réelle pour sélectionner un lot
    }
}
