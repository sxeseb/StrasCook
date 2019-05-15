<?php


namespace App\Model;

class AdminManager extends AbstractManager
{
    const TABLE = 'admin';

    public function __construct()
    {
        parent::__construct(self::TABLE);
    }

    public function selectOneByLogin($login)
    {
        $statement = $this->pdo->prepare("SELECT * FROM $this->table WHERE login = :login");
        $statement->bindValue('login', $login, \PDO::PARAM_STR);
        if ($statement->execute()) {
            return $statement->fetch();
        }
    }
}
