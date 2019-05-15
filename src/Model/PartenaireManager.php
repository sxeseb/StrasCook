<?php


namespace App\Model;

class PartenaireManager extends AbstractManager
{
    const TABLE = 'partenaire';

    /**
     *  Initializes this class.
     */
    public function __construct()
    {
        parent::__construct(self::TABLE);
    }


    public function selectAllPart(): array
    {
        $statement = $this->pdo->query("SELECT * FROM $this->table");

        return $statement = $statement ->fetchAll();
    }
}
