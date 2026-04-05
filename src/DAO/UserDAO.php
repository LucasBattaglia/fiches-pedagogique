<?php
// src/DAO/UserDAO.php

namespace src\DAO;

class UserDAO
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function findByEmail(string $email): ?array
    {
        $db = ConnectionPool::getConnection();
        $st = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $st->execute(['email' => strtolower($email)]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findById(int $id): ?array
    {
        $db = ConnectionPool::getConnection();
        $st = $db->prepare('SELECT id, email, nom, prenom, avatar_url, is_admin, oauth_provider FROM users WHERE id = :id');
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    public function create(string $email, ?string $password, string $nom, string $prenom, ?string $oauthProvider = null, ?string $oauthId = null, ?string $avatarUrl = null): int
    {
        $db = ConnectionPool::getConnection();
        $hash = $password ? password_hash($password, PASSWORD_BCRYPT) : null;
        $st = $db->prepare(
            'INSERT INTO users (email, password, nom, prenom, oauth_provider, oauth_id, avatar_url)
             VALUES (:email, :password, :nom, :prenom, :oauth_provider, :oauth_id, :avatar_url)
             RETURNING id'
        );
        $st->execute([
            'email'          => strtolower($email),
            'password'       => $hash,
            'nom'            => $nom,
            'prenom'         => $prenom,
            'oauth_provider' => $oauthProvider,
            'oauth_id'       => $oauthId,
            'avatar_url'     => $avatarUrl,
        ]);
        return (int)$st->fetchColumn();
    }

    public function findOrCreateByOAuth(string $provider, string $oauthId, string $email, string $nom, string $prenom, ?string $avatarUrl): array
    {
        $db = ConnectionPool::getConnection();
        // Chercher par oauth_id
        $st = $db->prepare('SELECT * FROM users WHERE oauth_provider = :p AND oauth_id = :id LIMIT 1');
        $st->execute(['p' => $provider, 'id' => $oauthId]);
        $user = $st->fetch();
        if ($user) return $user;

        // Chercher par email
        $user = $this->findByEmail($email);
        if ($user) {
            // Lier le compte OAuth
            $db->prepare('UPDATE users SET oauth_provider=:p, oauth_id=:id, avatar_url=COALESCE(:av, avatar_url) WHERE id=:uid')
               ->execute(['p' => $provider, 'id' => $oauthId, 'av' => $avatarUrl, 'uid' => $user['id']]);
            return array_merge($user, ['oauth_provider' => $provider]);
        }

        // Créer
        $newId = $this->create($email, null, $nom, $prenom, $provider, $oauthId, $avatarUrl);
        return $this->findById($newId);
    }

    public function login(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if (!$user || !$user['password']) return null;
        if (!password_verify($password, $user['password'])) return null;
        return $user;
    }

    public function updateProfile(int $id, string $nom, string $prenom): void
    {
        $db = ConnectionPool::getConnection();
        $db->prepare('UPDATE users SET nom=:nom, prenom=:prenom, updated_at=NOW() WHERE id=:id')
           ->execute(['nom' => $nom, 'prenom' => $prenom, 'id' => $id]);
    }

    public function changePassword(int $id, string $oldPassword, string $newPassword): bool
    {
        $user = $this->findById($id);
        $full = ConnectionPool::getConnection()->query("SELECT password FROM users WHERE id=$id")->fetch();
        if (!$full || !password_verify($oldPassword, $full['password'])) return false;
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        ConnectionPool::getConnection()->prepare('UPDATE users SET password=:p WHERE id=:id')
            ->execute(['p' => $hash, 'id' => $id]);
        return true;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function isAdmin($uid): bool
    {
        $user = $this->findById($uid);
        return $user ? (bool)$user['is_admin'] : false;
    }
}
