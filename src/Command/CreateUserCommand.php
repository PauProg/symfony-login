<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Crea o actualiza un usuario de prueba con contraseña hasheada',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Nombre de usuario')
            ->addArgument('email', InputArgument::REQUIRED, 'Correo electrónico')
            ->addArgument('password', InputArgument::OPTIONAL, 'Contraseña (si se omite, se pide de forma oculta)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = mb_strtolower(trim((string) $input->getArgument('username')));
        $email = mb_strtolower(trim((string) $input->getArgument('email')));

        if ('' === $username || '' === $email) {
            $io->error('El usuario y el email son obligatorios.');

            return Command::FAILURE;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('El email no es válido.');

            return Command::FAILURE;
        }

        $plainPassword = $input->getArgument('password') ?? $io->askHidden('Contraseña (no se mostrará)');

        if (!is_string($plainPassword) || '' === $plainPassword) {
            $io->error('La contraseña no puede estar vacía.');

            return Command::FAILURE;
        }

        $user = $this->users->findOneBy(['username' => $username]);

        if (null === $user) {
            $user = (new User())
                ->setUsername($username)
                ->setEmail($email);

            $this->entityManager->persist($user);
        } else {
            $user->setEmail($email);
        }

        // hashPassword usa el hasher configurado en security.yaml ('auto').
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->flush();

        $io->success(sprintf('Usuario "%s" guardado con contraseña hasheada.', $username));

        return Command::SUCCESS;
    }
}