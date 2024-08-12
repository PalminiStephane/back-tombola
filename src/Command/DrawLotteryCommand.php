<?php

namespace App\Command;

use App\Entity\Draws;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DrawLotteryCommand extends Command
{
    protected static $defaultName = 'app:draw-lottery';
    
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Exécute le tirage au sort pour les tombolas ouvertes.')
            // Ajoutez d'autres options ou arguments ici si nécessaire
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Logique du tirage au sort ici
        $drawsToExecute = $this->entityManager->getRepository(Draws::class)->findDrawsToExecute();

        foreach ($drawsToExecute as $draw) {
            $winner = $this->selectWinner($draw);
            $prize = $this->selectPrize($draw);

            if ($winner) {
                $draw->setWinners($winner->getName());
                $draw->setPrize($prize);
            }

            $draw->setStatus('closed');
            $this->entityManager->persist($draw);
        }

        $this->entityManager->flush();

        $io->success('Les tirages au sort ont été exécutés avec succès.');

        return Command::SUCCESS;
    }

    private function selectWinner(Draws $draw)
    {
        $tickets = $draw->getTickets()->toArray();

        if (count($tickets) === 0) {
            return null;
        }

        $winnerTicket = $tickets[array_rand($tickets)];
        return $winnerTicket->getUser();
    }

    private function selectPrize(Draws $draw)
    {
        return 'Le lot associé à la tombola';
    }
}
