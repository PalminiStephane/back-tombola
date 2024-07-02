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
        // Création de 17 tombolas
        $drawTitles = ['J2 – 25 août 2024 : OM – Reims', 'J4 – 15 septembre 2024 : OM – Nice', 'J7 – 6 octobre 2024 : OM – Angers', 'J9 – 27 octobre 2024 : OM – PSG', 'J11 – 10 novembre 2024 : OM – Auxerre', 'J13 – 1er décembre 2024 : OM – Monaco', 'J15 – 15 décembre 2024 : OM – Lille', 'J16 – 5 janvier 2025 : OM – Le Havre', 'J18 – 19 janvier 2025 : OM – Strasbourg', 'J20 – 2 février 2025 : OM – OL', 'J22 – 16 février 2025 : OM – St-Etienne', 'J24 – 2 mars 2025 : OM – Nantes', 'J25 – 9 mars 2025 : OM – Lens', 'J28 – 6 avril 2025 : OM – Toulouse', 'J30 – 20 avril 2025 : OM – Montpellier', 'J31 – 27 avril 2025 : OM – Brest', 'J34 – 18 mai 2025 : OM – Rennes'];

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
            $draw->setPicture('https://logos-world.net/wp-content/uploads/2020/11/Olympique-de-Marseille-Logo.png'); // Image de la tombola logo l'om
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
