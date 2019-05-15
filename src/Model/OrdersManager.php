<?php


namespace App\Model;

class OrdersManager extends AbstractManager
{
    const TABLE = 'orders';

    /**
     *  Initializes this class.
     */
    public function __construct()
    {
        parent::__construct(self::TABLE);
    }

    public function insert(array $panier, $resaId)
    {
        $statement = $this->pdo->prepare("INSERT INTO $this->table (quantity, reservation_id, menus_id, price_id)
        VALUES (:quantity, :resa_id, :menus_id, :price_id)");
        $statement->bindValue(':quantity', $panier['quantity'], \PDO::PARAM_INT);
        $statement->bindValue(':resa_id', $resaId, \PDO::PARAM_INT);
        $statement->bindValue(':menus_id', $panier['menuId'], \PDO::PARAM_INT);
        $statement->bindValue('price_id', $panier['price'], \PDO::PARAM_INT);
        if ($statement->execute()) {
            return (int)$this->pdo->lastInsertId();
        }
    }

    public function delete($id) :void
    {
        $statement = $this->pdo->prepare("DELETE FROM $this->table WHERE reservation_id = :id");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();
    }
}
