<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'task')]
class Task
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id = null;

	#[ORM\Column(type: 'string', length: 255)]
	private string $title;

	#[ORM\Column(type: 'text')]
	private string $content;

	#[ORM\Column(type: 'datetime')]
	private \DateTimeInterface $createdAt;

	#[ORM\Column(type: 'boolean')]
	private bool $isDone = false;

	#[ORM\Column(type: 'boolean')]
	private bool $isDeleted = false;

	#[ORM\ManyToOne(targetEntity: User::class)]
	#[ORM\JoinColumn(nullable: true)]
	private ?User $author = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function setTitle(string $title): self
	{
		$this->title = $title;
		return $this;
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function setContent(string $content): self
	{
		$this->content = $content;
		return $this;
	}

	public function getCreatedAt(): \DateTimeInterface
	{
		return $this->createdAt;
	}

	public function setCreatedAt(\DateTimeInterface $createdAt): self
	{
		$this->createdAt = $createdAt;
		return $this;
	}

	public function isDone(): bool
	{
		return $this->isDone;
	}

	public function setIsDone(bool $isDone): self
	{
		$this->isDone = $isDone;
		return $this;
	}

	public function isDeleted(): bool
	{
		return $this->isDeleted;
	}

	public function setIsDeleted(bool $isDeleted): self
	{
		$this->isDeleted = $isDeleted;
		return $this;
	}

	public function getAuthor(): ?User
	{
		return $this->author;
	}

	public function setAuthor(?User $author): self
	{
		$this->author = $author;
		return $this;
	}
}
