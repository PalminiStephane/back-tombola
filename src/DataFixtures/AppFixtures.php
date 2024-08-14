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

    public function __construct(UserPasswordHasherInterface $userPasswordHasherInterface)
    {
        $this->passwordhasher = $userPasswordHasherInterface;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Création de 17 tombolas avec leurs images et dates
        $drawData = [
            ['title' => 'J2 – 25 août 2024 : OM – Reims', 'date' => '2024-08-25', 'image' => 'reims.png'],
            ['title' => 'J4 – 15 septembre 2024 : OM – Nice', 'date' => '2024-09-15', 'image' => 'nice.png'],
            ['title' => 'J7 – 6 octobre 2024 : OM – Angers', 'date' => '2024-10-06', 'image' => 'angers.png'],
            ['title' => 'J9 – 27 octobre 2024 : OM – PSG', 'date' => '2024-10-27', 'image' => 'psg.png'],
            ['title' => 'J11 – 10 novembre 2024 : OM – Auxerre', 'date' => '2024-11-10', 'image' => 'auxerre.png'],
            ['title' => 'J13 – 1er décembre 2024 : OM – Monaco', 'date' => '2024-12-01', 'image' => 'monaco.png'],
            ['title' => 'J15 – 15 décembre 2024 : OM – Lille', 'date' => '2024-12-15', 'image' => 'lille.png'],
            ['title' => 'J16 – 5 janvier 2025 : OM – Le Havre', 'date' => '2025-01-05', 'image' => 'le_havre.png'],
            ['title' => 'J18 – 19 janvier 2025 : OM – Strasbourg', 'date' => '2025-01-19', 'image' => 'strasbourg.png'],
            ['title' => 'J20 – 2 février 2025 : OM – OL', 'date' => '2025-02-02', 'image' => 'lyon.png'],
            ['title' => 'J22 – 16 février 2025 : OM – St-Etienne', 'date' => '2025-02-16', 'image' => 'st_etienne.png'],
            ['title' => 'J24 – 2 mars 2025 : OM – Nantes', 'date' => '2025-03-02', 'image' => 'nantes.png'],
            ['title' => 'J25 – 9 mars 2025 : OM – Lens', 'date' => '2025-03-09', 'image' => 'lens.png'],
            ['title' => 'J28 – 6 avril 2025 : OM – Toulouse', 'date' => '2025-04-06', 'image' => 'toulouse.png'],
            ['title' => 'J30 – 20 avril 2025 : OM – Montpellier', 'date' => '2025-04-20', 'image' => 'montpellier.png'],
            ['title' => 'J31 – 27 avril 2025 : OM – Brest', 'date' => '2025-04-27', 'image' => 'brest.png'],
            ['title' => 'J34 – 18 mai 2025 : OM – Rennes', 'date' => '2025-05-18', 'image' => 'rennes.png'],
        ];

        $drawReferences = [];
        foreach ($drawData as $index => $data) {
            $draw = new Draws();
            $draw->setTitle($data['title']);
            $draw->setDescription('2 places en loge au stade Orange Vélodrome pour le match ' . ($data['title']));
            $draw->setDrawDate(new \DateTime($data['date']));
            $draw->setTicketValidityDuration(new \DateInterval('P7D'));
            $draw->setStatus('open');
            $draw->setTicketPrice(10.00);
            $draw->setTicketsAvailable(1000);
            $draw->setTotalTickets(1000);
            $draw->setPicture('images/match/' . $data['image']);
            $manager->persist($draw);

            $drawReferences[] = $draw;
        }
        $manager->flush();

        // Création d'un administrateur
        $admin = new User();
        $admin->setName('admin');
        $admin->setEmail('po@po.fr');
        $pwa = $this->passwordhasher->hashPassword($admin, "admin");
        $admin->setPassword($pwa);
        $admin->setRegistrationDate($faker->dateTimeBetween('-1 year', 'now'));
        $admin->setRoles(["ROLE_ADMIN"]);
        $manager->persist($admin);

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
                $randomDraw = $drawReferences[array_rand($drawReferences)];

                $ticket = new Tickets();
                $ticket->setUser($user);
                $ticket->setDraw($randomDraw);
                $ticket->setTicketNumber($faker->unique()->randomNumber());
                $ticket->setPurchaseDate($faker->dateTimeBetween('-1 month', 'now'));
                $ticket->setStatus('purchased');
                $manager->persist($ticket);
            }
        }

        $manager->flush();
    }
}
