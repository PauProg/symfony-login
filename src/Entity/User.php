<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface {
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 180, unique: true)]
  private string $email = '';

  #[ORM\Column(length: 50, unique: true)]
  private string $username = '';

  /** @var list<string> */
  #[ORM\Column(type: 'json')]
  private array $roles = [];

  /**
   * Hash de la contraseña. Nunca se guarda la contraseña en texto plano.
   */
  #[ORM\Column]
  private string $password = '';

  public function getId(): ?int {
    return $this->id;
  }

  public function getEmail(): string {
    return $this->email;
  }

  public function setEmail(string $email): static {
    $this->email = mb_strtolower(trim($email));
    return $this;
  }

  public function getUsername(): string {
    return $this->username;
  }

  public function setUsername(string $username): static {
    $this->username = mb_strtolower(trim($username));
    return $this;
  }

  /**
   * @return list<string>
   */
  public function getRoles(): array {
    $roles = $this->roles;

    // Todo usuario tiene al menos ROLE_USER
    $roles[] = 'ROLE_USER';

    return array_values(array_unique($roles));
  }

  /**
   * @param list<string> $roles
   */
  public function setRoles(array $roles): static{
    $this->roles = array_values(array_unique($roles));
    return $this;
  }

  public function getPassword(): ?string {
    return $this->password;
  }

  public function setPassword(string $password): static {
    $this->password = $password;
    return $this;
  }

  public function getUserIdentifier(): string {
    return $this->username;
  }
}