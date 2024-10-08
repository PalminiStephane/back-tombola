<?php

namespace App\Command;

use App\Entity\Draws;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DrawLotteryCommand extends Command
{
    protected static $defaultName = 'app:draw-lottery';
    
    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Exécute le tirage au sort pour les tombolas ouvertes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $drawsToExecute = $this->entityManager->getRepository(Draws::class)->findDrawsToExecute();
        
        $totalDraws = count($drawsToExecute);
        $io->text("Nombre de tirages à exécuter : $totalDraws");

        foreach ($drawsToExecute as $draw) {
            try {
                $io->text('Exécution du tirage au sort pour : ' . $draw->getTitle());

                if ($draw->getStatus() !== 'open') {
                    $io->warning('La tombola ' . $draw->getTitle() . ' n\'est pas ouverte pour le tirage.');
                    continue;
                }

                $winner = $this->selectWinner($draw);
                $prize = $this->selectPrize($draw);

                if ($winner) {
                    $draw->setWinners($winner->getName());
                    $draw->setPrize($prize);
                    $draw->setStatus('closed');
                    $this->entityManager->persist($draw);
                    $io->success('Gagnant pour ' . $draw->getTitle() . ' : ' . $winner->getName());
                } else {
                    $io->warning('Aucun gagnant sélectionné pour la tombola ' . $draw->getTitle());
                }
            } catch (\Exception $e) {
                $io->error('Erreur lors du traitement de la tombola ' . $draw->getTitle() . ': ' . $e->getMessage());
                $this->logger->error('Erreur lors du tirage au sort de la tombola ' . $draw->getId(), ['exception' => $e]);
            }
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
        return $draw->getPrize();
    }
}

