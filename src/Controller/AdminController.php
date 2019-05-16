<?php
/**
 * Created by PhpStorm.
 * User: Sylvain
 * Date: 2019-04-30
 * Time: 11:14
 */

namespace App\Controller;

use App\Model\OrdersManager;
use App\Model\MenuManager;
use App\Model\ImageManager;
use App\Model\ReservationManager;
use App\Service\DateService;
use App\Service\ValidationService;

class AdminController extends AbstractController
{
    public function admin()
    {
        $reservationController = new ReservationController();
        $reservationController->updateOutdated();
        header('location: /admin/dashboard');
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $validator = new ValidationService();
            $output = $validator->checkAdmin();
            list($errors, $datas) = $output;

            if (!empty($errors)) {
                return $this->twig->render('Admin/login.html.twig', ['errors' => $errors]);
            } else {
                $_SESSION['admin'] = $datas['user'];
                header('location: /admin/dashboard');
            }
        }

        if (!isset($_SESSION['admin']) || empty($_SESSION['admin']) || $_SESSION['admin'] != 'admin') {
            return $this->twig->render('Admin/login.html.twig');
        } else {
            header('location: /admin/dashboard');
        }
    }

    public function logout()
    {
        unset($_SESSION['admin']);
        header('location: /admin/admin');
    }

    public function dashboard()
    {
        if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
            header('location: /admin/login');
        }

        $resaManager = new ReservationManager();
        $resaPending = $resaManager-> reservationPending();
        $confirmed = $resaManager->reservationConfirmed();
        $dateService = new DateService();
        $confirmed = $dateService ->setToFormat($confirmed);

        // init array des réservations de la semaine
        $thisWeek = [];
        foreach ($confirmed as $key => $resa) {
            $date = new \DateTime($resa['date']);
            $days= $date->format('d');
            $month = $date->format('M');
            $confirmed[$key]['day']=$days;
            $confirmed[$key]['month']=$month;

            if ($resa['daysToDate'] <= 7) {
                $thisWeek[$key] = $resa;
                $thisWeek[$key]['day']=$days;
                $thisWeek[$key]['month']=$month;
            }
        }

        $nextClient = $confirmed[0]['id'];
        $clientDetails = $resaManager->reservationDetails($nextClient);

        $orderDetails= $resaManager->reservationOrderDetails($nextClient);

        return $this->twig->render('Admin/dashboard.html.twig', ['menutoday'=>$confirmed,
            'menupending'=>$resaPending, 'thisweek' => $thisWeek,
            'order', 'orderDetails' => $orderDetails, 'clientDetails' => $clientDetails]);
    }

    public function reservations(int $id = null)
    {

        if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
            header('location: /admin/login');
        }

        $resaManager = new ReservationManager();
        $overviewsPending = $resaManager->reservationPending();
        $confirmed = $resaManager->reservationConfirmed();
        $dateService = new DateService();

        // formatage des données date et heure
        $overviewsPending = $dateService->setToFormat($overviewsPending);
        $confirmed = $dateService->setToFormat($confirmed);

        //ajout de formatage supplémentaire pour affichage complémentaire
        foreach ($confirmed as $key => $resa) {
            $date = new \DateTime($resa['date']);
            $days= $date->format('d');
            $month = $date->format('M');

            $confirmed[$key]['day']=$days;
            $confirmed[$key]['month']=$month;
        }

        if (isset($id) && is_int($id)) {
            $clientDetails = $resaManager->reservationDetails($id);
            $clientDetails['daysToDate'] = $dateService->daysToNow($clientDetails['date_resa']);
            list($date, $time) = $dateService->formatFromDb($clientDetails['date_resa']);
            $clientDetails['date'] = $date;
            $clientDetails['time'] = $time;
            $orderDetails = $resaManager->reservationOrderDetails($id);


            return $this->twig->render(
                'Admin/reservations.html.twig',
                ['pending' => $overviewsPending,
                    'orderDetails' => $orderDetails, 'clientDetails' => $clientDetails, 'confirmed' => $confirmed]
            );
        }

        return $this->twig->render(
            'Admin/reservations.html.twig',
            ['pending' => $overviewsPending, 'confirmed' => $confirmed]
        );
    }

    public function confirm(int $id) :void
    {
        $reservationManager = new ReservationManager();
        $dateService = new DateService();

        // confirmation de la réservation dans la table
        $confirmed = $reservationManager->confirm($id);
        $confirmed = $confirmed['date_resa'];

        // on vérifie si d'autres reservations en attentes sont prévues pour la même date
        // si oui, on les refuse
        $dateConfirmed = $dateService->dateFromDb($confirmed);
        $pendingResa = $reservationManager->reservationPending();

        foreach ($pendingResa as $resa) {
            if ($dateService->dateFromDb($resa['date_resa']) == $dateConfirmed) {
                $this->decline($resa['id']);
            }
        }

        header('location: /admin/reservations');
    }

    // refus de la réservation :  envoi d'un email de refus
    public function decline(int $id) :void
    {
        $ordersManager = new OrdersManager();
        $ordersManager->delete($id);
        $reservationManager = new ReservationManager();
        $reservationManager->decline($id);
        header('location: /admin/reservations');
    }

    // annulation de la réservation : email d'annulation et proposition d'autres dates
    public function cancel(int $id) :void
    {
        $ordersManager = new OrdersManager();
        $ordersManager->delete($id);
        $reservationManager = new ReservationManager();
        $reservationManager->decline($id);
        header('location: /admin/reservations');
    }

    public function menus()
    {
        if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
            header('location: /admin/login');
        }
        $adminmenu = new MenuManager();
        $menus = $adminmenu ->selectAllMenus();
        return $this->twig->render('Admin/menu.html.twig', ['menus' => $menus]);
    }

    public function delete(int $id):void
    {

        $deletemenu = new MenuManager();
        $deletemenu->deleteAllImage($id);
        if ($deletemenu ->delete($id)) {
            header('location: /admin/menus/');
        }
    }

    public function deleteOneImage(int $id):void
    {
        $deleteimage = new ImageManager();
        $idMenu = $deleteimage ->deleteOneImage($id);
        header('location: /Admin/updateMenu/'.$idMenu);
    }

    public function updateMenu(int $id)
    {
        if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
            header('location: /admin/login');
        }
        $adminmenu = new MenuManager();
        $menus = $adminmenu ->selectOneMenus($id);
        $imagesmenu = new ImageManager();
        $images = $imagesmenu -> selectAllImages($id);
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $validator = new ValidationService();
            $output = $validator->checkMenu();
            list($errors, $menuDatas) = $output;
            if (!empty($errors)) {
                return $this->twig->render(
                    'Admin/menuedit.html.twig',
                    ['errors' => $errors, 'menu' => $menus]
                );
            } else {
                $menuManager = new MenuManager();
                if ($menuManager -> updateMenu($menuDatas, $id)) {
                    unset($_POST);
                    header('location: /admin/menus');
                }
            }
        }
        return $this->twig->render('Admin/menuedit.html.twig', ['menu' => $menus, 'images'=>$images]);
    }

    public function updateImage(int $id)
    {
        if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
            header('location: /admin/login');
        }
        $adminmenu = new MenuManager();
        $menus = $adminmenu ->selectOneMenus($id);
        $imagesmenu = new ImageManager();
        $images = $imagesmenu -> selectAllImages($id);
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $validatorImage = new ValidationService();
            $outputimage = $validatorImage->checkInsertImage();
            list($imageErrors, $imageDatas) = $outputimage;
            if (!empty($imagesErrors)) {
                return $this->twig->render(
                    'admin/menuedit.html.twig',
                    ['errors' => $imageErrors, 'images' => $images]
                );
            } else {
                $imageManager = new ImageManager();
                if ($imageManager->updateImage($imageDatas, $id)) {
                    unset($_POST);
                    header('location: /Admin/updateMenu/'.$id);
                }
            }
        }
        return $this->twig->render('Admin/menuedit.html.twig', ['menu' => $menus, 'images'=>$images]);
    }

    public function addMenu()
    {
        if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
            header('location: /admin/login');
        }
        $addmenu = new MenuManager();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $validator = new ValidationService();
            $output = $validator->checkMenu();
            list($errors, $menuDatas) = $output;
            if (!empty($errors)) {
                return $this->twig->render(
                    'Admin/menuadd.html.twig',
                    ['errors' => $errors, 'menu' => $menuDatas]
                );
            } else {
                $menuManager = new MenuManager();
                if ($menuManager -> addmenu($menuDatas)) {
                    unset($_POST);
                    header('location: /Admin/menus');
                }
            }
        }
        return $this->twig->render('Admin/menuadd.html.twig');
    }

    public function addImage($id)
    {
        if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
            header('location: /admin/login');
        }
        $adminmenu = new MenuManager();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $validatorImage = new ValidationService();
            $outputimage = $validatorImage->checkInsertImage();

            list($imageErrors, $imageDatas) = $outputimage;
            if (!empty($imageErrors)) {
                return $this->twig->render(
                    'Admin/menuedit.html.twig',
                    ['errors' => $imageErrors, 'image' => $imageDatas]
                );
            } else {
                $imageDatas['menu_menu_id'] = $id;

                $imageManager = new ImageManager();
                if ($imageManager->addImage($imageDatas)) {
                    unset($_POST);

                    header('location: /Admin/updateMenu/'.$id);
                }
            }
        }
        return $this->twig->render('Admin/menus.html.twig');
    }
}
