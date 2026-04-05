<?php
// src/DAO/ConnectionPool.php

namespace src\DAO;

class ConnectionPool
{
    private static ?\PDO $connection = null;
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function getConnection(): \PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                self::$config['host'],
                self::$config['port'],
                self::$config['dbname']
            );
            self::$connection = new \PDO($dsn, self::$config['user'], self::$config['password'], [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$connection->exec("SET client_encoding TO 'UTF8'");
        }
        return self::$connection;
    }

    public static function beginTransaction(): void
    {
        self::getConnection()->beginTransaction();
    }

    public static function commit(): void
    {
        if (self::$connection && self::$connection->inTransaction()) {
            self::$connection->commit();
        }
    }

    public static function rollback(): void
    {
        if (self::$connection && self::$connection->inTransaction()) {
            self::$connection->rollBack();
        }
    }
}
