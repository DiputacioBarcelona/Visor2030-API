<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    protected string $username;
    protected ?string $password;
    protected array $roles;

    // public function __construct(string $username, ?string $password = null, array $roles = [])
    // {
    //     $this->username = $username;
    //     $this->password = $password;
    //     $this->roles = $roles;
    // }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    public function getRoles(): array
    {
        // return $this->roles ?? ['ROLE_USER'];
        // if you're a user, then you're an admin
        return $this->roles ?? ['ROLE_ADMIN', 'PUBLIC_ACCESS', 'ROLE_USER'];
    }

    public function setRoles(array $roles): self
    {
        // Ensures always exists user role.
        $this->roles = array_unique(array_merge($roles, ['ROLE_USER', 'ROLE_ADMIN', 'PUBLIC_ACCESS']));
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        // return $this->password;
        // not needed for apps that do not check user passwords.
        return null;
    }

    // set password
    // public function setPassword(?string $password): self
    // {
    //     $this->password = $password;
    //     return $this;
    // }

    /**
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        // not needed for apps that do not check user passwords.
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
