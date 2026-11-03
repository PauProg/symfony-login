<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface, PasswordUpgraderInterface {
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, User::class);
  }

  public function loadUserByIdentifier(string $identifier): ?UserInterface {
    $identifier = mb_strtolower(trim($identifier));

    return $this->createQueryBuilder('u')
      ->where('LOWER(u.username) = :identifier')
      // ->orWhere('LOWER(u.email) = :identifier') // descomenta para aceptar email
      ->setParameter('identifier', $identifier)
      ->getQuery()
      ->getOneOrNullResult();
  }

  public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void {
    if (!$user instanceof User) {
      throw new UnsupportedUserException();
    }

    $user->setPassword($newHashedPassword);
    $this->getEntityManager()->flush();
  }
}