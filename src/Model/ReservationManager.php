<?php


namespace App\Model;

class ReservationManager extends AbstractManager
{

    const TABLE = 'reservation';

    /**
     *  Initializes this class.
     */
    public function __construct()
    {
        parent::__construct(self::TABLE);
    }

    public function insert(array $reservation, int $user_id): int
    {
        // prepared request
        $statement = $this->pdo->prepare("INSERT INTO $this->table (status, user_id, date_booked, commentaires) VALUES (
            :status, :user_id, :date_booked, :commentaire)");
        $statement->bindValue(':status', 0, \PDO::PARAM_INT);
        $statement->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
        $statement->bindValue(':date_booked', $reservation['date_booked'], \PDO::PARAM_STR);
        $statement->bindValue('commentaire', $reservation['comment'], \PDO::PARAM_STR);
        if ($statement->execute()) {
            return (int)$this->pdo->lastInsertId();
        }
    }


    // getters

    // récupération des réservations en attente
    public function reservationPending(): array
    {
        $statement = $this->pdo->query("SELECT r.id id, 
            date_booked date_resa, 
            SUM(o.quantity) guests, 
            concat(zip, ' ', city) place, 
            concat(lastname, ' ', firstname) client 
            FROM user u 
            JOIN reservation r ON u.id = r.user_id 
            JOIN orders o ON o.reservation_id = r.id 
            WHERE r.status = 0
            GROUP BY r.id 
            ORDER BY date_resa ASC
            ;");

        return $statement->fetchAll();
    }

    // récupération des réservations confirmées
    public function reservationConfirmed(): array
    {
        $statement = $this->pdo->query("SELECT r.id id, 
            status,
            date_booked date_resa,
            SUM(o.quantity) guests, 
            concat(zip, ' ', city) place, 
            concat(lastname, ' ', firstname) client 
            FROM user u 
            JOIN reservation r ON u.id = r.user_id 
            JOIN orders o ON o.reservation_id = r.id 
            WHERE status = 1
            GROUP BY r.id 
            ORDER BY date_resa ASC, guests DESC
            ;");

        return $statement->fetchAll();
    }

    // récupération des réservations passées pour affichage/stats
    public function reservationPassed()
    {
        $statement = $this->pdo->query("SELECT r.id id, 
            status,
            date_booked date_resa,
            SUM(o.quantity) guests, 
            concat(zip, ' ', city) place, 
            concat(lastname, ' ', firstname) client 
            FROM user u 
            JOIN reservation r ON u.id = r.user_id 
            JOIN orders o ON o.reservation_id = r.id 
            WHERE status = 2
            GROUP BY r.id 
            ORDER BY date_resa ASC, guests DESC
            ;");

        return $statement->fetchAll();
    }

    // récupération des réservations passées pour update dans la base
    // retour id
    public function reservationPassedId()
    {
        $statement = $this->pdo->query("SELECT id, status FROM $this->table WHERE date_booked < NOW() AND status != 2");
        return $statement->fetchAll();
    }

    // details panier pour la réservation ciblée
    public function reservationOrderDetails($id): array
    {
        $statement = $this->pdo->prepare("SELECT m.name, p.cat_name categorie, price, quantity, r.date_booked
            FROM orders o 
            JOIN reservation r ON r.id = o.reservation_id 
            JOIN menus m ON m.id = o.menus_id 
            JOIN price p on p.id = o.price_id
            WHERE r.id = :id
            ORDER BY categorie");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);

        if ($statement->execute()) {
            return $statement->fetchAll();
        }
    }

    // details user pour la réservation ciblée
    public function reservationDetails(int $id): array
    {
        $statement = $this->pdo->prepare("SELECT r.id id, 
            date_booked date_resa, 
            concat(lastname, ' ', firstname) client, 
            adress, zip, city, phone, email, status, r.commentaires 
            FROM user u 
            JOIN reservation r ON u.id = r.user_id 
            JOIN email e ON e.id = u.email_id 
            WHERE r.id = :id
            ;");

        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        if ($statement->execute()) {
            return $statement->fetch();
        }
    }

    public function getCountReservations(): array
    {
        $statement = $this->pdo->query("SELECT COUNT(*) pendingReservations 
        FROM $this->table
        WHERE status != 1");

        return $statement->fetchAll();
    }

    public function getAllPendingDates()
    {
        $statement = $this->pdo->query("SELECT date_booked date_resa, date_passed, status, id 
            FROM $this->table WHERE status != 1");

        return $statement->fetchAll();
    }

    public function getAllConfirmedDates()
    {
        $statement = $this->pdo->query("SELECT date_booked date_resa, date_passed, status 
            FROM $this->table WHERE status = 1");

        return $statement->fetchAll();
    }


    // Actions admin

    /**
     * @param int $id
     */
    public function decline(int $id): void
    {
        // prepared request
        $statement = $this->pdo->prepare("DELETE FROM $this->table WHERE id=:id");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * @param int $id
     * @return array
     */
    public function confirm(int $id) :array
    {
        // prepared request
        $statement = $this->pdo->prepare("UPDATE $this->table SET `status` = :status WHERE id=:id");
        $statement->bindValue('id', $id, \PDO::PARAM_STR);
        $statement->bindValue('status', 1, \PDO::PARAM_INT);
        if ($statement->execute()) {
            $return = $this->pdo->prepare("SELECT date_booked date_resa, id FROM $this->table WHERE id=:id");
            $return->bindValue('id', $id, \PDO::PARAM_INT);
            if ($return->execute()) {
                return $return->fetch();
            }
        };
    }

    public function setPassed(int $id) :void
    {
        $statement = $this->pdo->prepare("UPDATE $this->table SET `status` = :status WHERE id=:id");
        $statement->bindValue('id', $id, \PDO::PARAM_STR);
        $statement->bindValue('status', 2, \PDO::PARAM_INT);
        $statement->execute();
    }
}
