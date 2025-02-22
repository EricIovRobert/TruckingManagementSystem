<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:admin-add',
    description: 'Adaugă un utilizator cu rol de admin în aplicație',
)]
class AdminAddCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Numele de utilizator pentru admin')
            ->addArgument('password', InputArgument::REQUIRED, 'Parola pentru admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        // Verifică dacă există deja un utilizator cu același username
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
            $io->error(sprintf('Un utilizator cu username-ul "%s" există deja!', $username));
            return Command::FAILURE;
        }

        // Crează un nou utilizator cu rol de admin
        $admin = new User();
        $admin->setUsername($username);
        $admin->setRoles(['ROLE_ADMIN']);

        // Hash-uiește parola
        $hashedPassword = $this->passwordHasher->hashPassword($admin, $password);
        $admin->setPassword($hashedPassword);

        // Salvează utilizatorul în baza de date
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success(sprintf('Admin "%s" a fost creat cu succes!', $username));

        return Command::SUCCESS;
    }
}
