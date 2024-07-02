<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Draws;
use App\Entity\Tickets;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordhasher;

    // ? on utilise le constructeur pour utiliser l'injection de dépendance
    public function __construct(UserPasswordHasherInterface $userPasswordHasherInterface)
    {
        $this->passwordhasher = $userPasswordHasherInterface;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        // Création de 4 tombolas
        $drawTitles = ['Tombola 1', 'Tombola 2', 'Tombola 3', 'Tombola 4'];

        foreach ($drawTitles as $index => $title) {
            $draw = new Draws();
            $draw->setTitle($title);
            $draw->setDescription('Description de la tombola ' . ($index + 1));
            $draw->setDrawDate(new \DateTime('+1 month')); // Date du tirage au sort, à ajuster
            $draw->setTicketValidityDuration(new \DateInterval('P7D')); // Durée de validité des tickets, à ajuster
            $draw->setStatus('open'); // Statut du tirage au sort
            $draw->setTicketPrice(10.00); // Prix du ticket, à ajuster
            $draw->setTicketsAvailable(100); // Nombre de tickets disponibles, à ajuster
            $draw->setTotalTickets(200); // Nombre total de tickets, à ajuster
            $manager->persist($draw);

            // Référencement des tirages pour être utilisés dans les fixtures des tickets
            $this->addReference('draw_' . ($index + 1), $draw);
        }
        $manager->flush();

        // Création de 100 utilisateurs
        for ($i = 0; $i < 100; $i++) {
            $user = new User();
            $user->setName($faker->userName);
            $user->setEmail($faker->email);
            $pw = $this->passwordhasher->hashPassword($user, "user");
            $user->setPassword($pw);
            $user->setRegistrationDate($faker->dateTimeBetween('-1 year', 'now'));
            $user->setRoles(["ROLE_USER"]);
            $manager->persist($user);

            // Achats de tickets pour une ou plusieurs tombolas aléatoires
            $numTickets = $faker->numberBetween(0, 10);
            for ($j = 0; $j < $numTickets; $j++) {
                // Récupère une référence à une tombola existante
                $ticket = new Tickets();
                $ticket->setUser($user);
                $ticket->setDraw($draw);
                $ticket->setTicketNumber($faker->unique()->randomNumber());
                $ticket->setPurchaseDate($faker->dateTimeBetween('-1 month', 'now'));
                $ticket->setStatus('purchased'); // Status du ticket
                $manager->persist($ticket);
            }
        }

        $manager->flush();
    }
}
