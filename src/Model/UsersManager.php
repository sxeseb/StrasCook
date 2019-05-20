<?php


namespace App\Model;

class UsersManager extends AbstractManager
{
    const TABLE = 'user';

    /**
     *  Initializes this class.
     */
    public function __construct()
    {
        parent::__construct(self::TABLE);
    }

    public function insert(array $datas, int $email_id):int
    {
        // prepared request
        $statement = $this->pdo->prepare("INSERT INTO $this->table 
        (firstname, lastname, adress, phone, city, zip, email_id)
        VALUES (:firstname, :lastname, :adress, :phone, :city, :zip, :email_id)");
        $statement->bindValue('firstname', $datas['firstname'], \PDO::PARAM_STR);
        $statement->bindValue('lastname', $datas['lastname'], \PDO::PARAM_STR);
        $statement->bindValue('adress', $datas['adress'], \PDO::PARAM_STR);
        $statement->bindValue('phone', $datas['phone'], \PDO::PARAM_STR);
        $statement->bindValue('city', $datas['city'], \PDO::PARAM_STR);
        $statement->bindValue('zip', $datas['zip'], \PDO::PARAM_STR);
        $statement->bindValue('email_id', $email_id, \PDO::PARAM_INT);

        if ($statement->execute()) {
            return (int)$this->pdo->lastInsertId();
        }
    }

    // methode de TEST, sera placée dans le controleur adapté
    public function insertMail($datas)
    {
        $statement = $this->pdo->prepare("INSERT INTO email (email) VALUES (:email)");
        $statement->bindValue('email', $datas['email'], \PDO::PARAM_STR);

        if ($statement->execute()) {
            return (int)$this->pdo->lastInsertId();
        }
    }

    public function checkMail($mail)
    {
        $statement = $this->pdo->prepare('SELECT id from email WHERE email = :mail');
        $statement->bindValue('mail', $mail, \PDO::PARAM_STR);
        if ($statement->execute()) {
            return $statement->fetch();
        }
    }

    public function getUserInfos($mailId)
    {
        $statement = $this->pdo->prepare(
            "SELECT u.id id, firstname, lastname, adress, phone, city, zip, email_id, email 
            FROM $this->table u
            JOIN email e 
            ON e.id =  u.email_id
            WHERE email_id = :mailId"
        );
        $statement->bindValue('mailId', $mailId, \PDO::PARAM_INT);
        if ($statement->execute()) {
            return $statement->fetch();
        }
    }
}
