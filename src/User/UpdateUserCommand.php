<?php
/**
 * @copyright Copyright (c) Wizacha
 * @license Proprietary
 */
declare(strict_types = 1);

namespace Wizaplace\SDK\User;

use Wizaplace\SDK\Exception\SomeParametersAreInvalid;

/**
 * Class UpdateUserCommand
 * @package Wizaplace\SDK\User
 */
final class UpdateUserCommand
{
    /** @var int */
    private $id;

    /** @var string */
    private $email;

    /** @var string */
    private $firstName;

    /** @var string */
    private $lastName;

    /** @var UserTitle|null */
    private $title;

    /** @var \DateTimeInterface|null */
    private $birthday;

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return UserTitle|null
     */
    public function getTitle(): ?UserTitle
    {
        return $this->title;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    /**
     * @param int $userId
     *
     * @return UpdateUserCommand
     */
    public function setUserId(int $userId): self
    {
        $this->id = $userId;

        return $this;
    }

    /**
     * @param string $email
     *
     * @return UpdateUserCommand
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param string $firstName
     *
     * @return UpdateUserCommand
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @param string $lastName
     *
     * @return UpdateUserCommand
     */
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @param UserTitle|null $title
     *
     * @return UpdateUserCommand
     */
    public function setTitle(?UserTitle $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param \DateTimeInterface|null $birthday
     *
     * @return UpdateUserCommand
     */
    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * @throws SomeParametersAreInvalid
     */
    public function validate(): void
    {
        if (!isset($this->id)) {
            throw new SomeParametersAreInvalid('Missing customer ID');
        }

        if (!isset($this->email)) {
            throw new SomeParametersAreInvalid('Missing customer\'s email');
        }

        if (!isset($this->firstName)) {
            throw new SomeParametersAreInvalid('Missing customer\'s first name');
        }

        if (!isset($this->lastName)) {
            throw new SomeParametersAreInvalid('Missing customer\'s last name');
        }
    }
}
