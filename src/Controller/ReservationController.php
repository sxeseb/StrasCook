<?php


namespace App\Controller;

use App\Model\MenuManager;
use App\Model\ReservationManager;
use App\Model\UsersManager;
use App\Service\CartService;
use App\Service\ValidationService;
use App\Model\OrdersManager;

class ReservationController extends AbstractController
{
    public function reserver()
    {
        if (isset($_SESSION['emailConfirmation'])) {
            unset($_SESSION['emailConfirmation']);
        }

        $menuManager = new MenuManager();
        $menus = $menuManager->selectAllMenus();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $validator = new CartService();
            $output = $validator->addToCart();
            list($errors, $datas) = $output;
        }

        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            $cartService = new CartService();
            $panier = $_SESSION['cart'];
            $count = $cartService->calculTotal($panier);
            $resaErrors = [];
            $cartDatas = [];
            if (isset($_SESSION['errors'])) {
                $resaErrors = $_SESSION['errors'];
            }
            if (isset($_SESSION['cartDatas'])) {
                $cartDatas = $_SESSION['cartDatas'];
            }

            return $this->twig->render(
                'Reservations/reserver.html.twig',
                ['menus' => $menus, 'panier' => $panier,
                    'count' => $count, 'errors' => $resaErrors, 'cartDatas' => $cartDatas]
            );
        } else {
            return $this->twig->render('Reservations/reserver.html.twig', ['menus' => $menus]);
        }
    }

    public function checkCart()
    {
        if (isset($_SESSION['errors'])) {
            unset($_SESSION['errors']);
            unset($_SESSION['cartDatas']);
        }
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            $validator = new ValidationService();
            $output = $validator->checkCart();
            list($errors, $resaDatas) = $output;
            if (empty($errors)) {
                $_SESSION['resaDatas'] = $resaDatas;
                header('location: /users/infos');
            } else {
                $_SESSION['errors'] = $errors;
                $_SESSION['cartDatas'] = $resaDatas;
                header('location: /reservation/reserver');
            }
        } else {
            header('location: /reservation/reserver');
        }
    }

    public function validReservation($userDatas)
    {
        $userManager = new UsersManager();

        if (!isset($userDatas['email_id'])) {
            $emailId = $userManager->insertMail($userDatas);
        } else {
            $emailId = $userDatas['email_id'];
        }

        if ($emailId) {
            $_SESSION['emailConfirmation'] = $userDatas['email'];
        }

        // insertion user
        if (!isset($userDatas['id'])) {
            $userId = $userManager->insert($userDatas, $emailId);
        } else {
            $userId = $userDatas['id'];
        }


        // insertion reservation
        $date = new \DateTime($_SESSION['resaDatas']['date']);
        $date = $date->setTime($_SESSION['resaDatas']['arrival'], 0, 0);
        $date = $date->format('Y-m-d H:i:s');
        $_SESSION['resaDatas']['date_booked'] = $date;
        $resaDatas = $_SESSION['resaDatas'];

        $resaManager = new ReservationManager();
        $resaId = $resaManager->insert($resaDatas, $userId);

        // insertion orders
        $orderManager = new OrdersManager();

        foreach ($_SESSION['cart'] as $order) {
            $orderId = $orderManager->insert($order, $resaId);
        }

        if ($emailId && $userId && $resaId) {
            unset($_SESSION['cart']);
            unset($_SESSION['resaDatas']);
            unset($_POST);

            return 1;
        } else {
            return -1;
        }
    }

    public function success()
    {
        if (isset($_SESSION['emailConfirmation'])) {
            $email = $_SESSION['emailConfirmation'];
            return $this->twig->render('Reservations/success.html.twig', ['email' => $email]);
        } else {
            header('location: /reservation/reserver');
        }
    }

    public function updateOutdated() :void
    {
        $resaManager = new ReservationManager();
        $passedResa = $resaManager->reservationPassedId();
        foreach ($passedResa as $resa) {
            if ($resa['status'] == 0) {
                $this->deleteOutdated($resa['id']);
            } elseif ($resa['status'] == 1) {
                $resaManager->setPassed($resa['id']);
            }
        }
    }

    public function deleteOutdated(int $id) :void
    {
        $ordersManager = new OrdersManager();
        $ordersManager->delete($id);
        $reservationManager = new ReservationManager();
        $reservationManager->decline($id);
    }
}
