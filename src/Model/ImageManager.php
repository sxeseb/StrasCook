<?php


namespace App\Model;

class ImageManager extends AbstractManager
{
    const TABLE = 'images';

    /**
     *  Initializes this class.
     */
    public function __construct()
    {
        parent::__construct(self::TABLE);
    }


    /**
     * @param array $item
     * @return int
     */
    public function insert(array $item): int
    {
        // prepared request
        $statement = $this->pdo->prepare("INSERT INTO $this->table (`title`) VALUES (:title)");
        $statement->bindValue('title', $item['title'], \PDO::PARAM_STR);

        if ($statement->execute()) {
            return (int)$this->pdo->lastInsertId();
        }
    }

    public function selectAllImages(int $id)
    {
        $statement = $this->pdo->prepare("SELECT i.id id, img_src, thumb, menus_id 
        FROM $this->table i JOIN menus m on m.id = i.menus_id 
        WHERE m.id=:id;");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();
        return $statement ->fetchAll();
    }

    public function deleteAllImage(int $id): bool
    {
        $statement = $this->pdo->prepare("DELETE FROM images WHERE menus_id=:id");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        return $statement->execute();
    }



    public function deleteOneImage(int $id)
    {
        $statement = $this->pdo->prepare("SELECT menus_id FROM $this->table WHERE id=:id");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();
        $idMenu = $statement->fetch();
        $statement = $this->pdo->prepare("DELETE FROM $this->table
        WHERE id=:id");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        if ($statement->execute()) {
            return $idMenu['menus_id'];
        }
    }

    public function updateImage(array $item, $id)
    {
        $statement = $this->pdo->prepare("UPDATE $this->table i SET `img_src` = :img_src, `thumb` = :thumb
        WHERE id=:id");
        $statement->bindValue('img_src', $item['menu_img_src'], \PDO::PARAM_STR);
        $statement->bindValue('thumb', $item['menu_thumb'], \PDO::PARAM_BOOL);
        $statement->bindvalue('id', $id, \PDO::PARAM_INT);
        return $statement->execute();
    }

    public function addImage(array $item)
    {
        $statement = $this->pdo->prepare("INSERT INTO $this->table (`img_src`, `thumb`, `menus_id`)
        VALUES (:img_src, :thumb, :menus_id)");
        $statement->bindValue('img_src', $item['menu_img_src'], \PDO::PARAM_STR);
        $statement->bindValue('thumb', $item['menu_thumb'], \PDO::PARAM_BOOL);
        $statement->bindValue('menus_id', $item['menu_menu_id'], \PDO::PARAM_INT);
        var_dump($item);
        if ($statement->execute()) {
            return (int)$this->pdo->lastInsertId();
        }
    }
}
