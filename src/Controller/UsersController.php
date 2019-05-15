<?php


namespace App\Controller;

use App\Model\ReservationManager;
use App\Model\UsersManager;
use App\Service\ValidationService;

class UsersController extends AbstractController
{
    public function infos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $validator = new ValidationService();
            $output = $validator->checkCoord();
            list($errors, $userDatas) = $output;
            if (!empty($errors)) {
                return $this->twig->render(
                    'Users/infos.html.twig',
                    ['errors' => $errors, 'datas' => $userDatas]
                );
            } else {
                // appel du controller de reservation pour lancer la procédure d'insertion
                $insertController = new ReservationController();
                if ($insertController->validReservation($userDatas)) {
                    header('location: /reservation/success');
                } else {
                    header('location: /reservation/error');
                }
            }
        }
        return $this->twig->render('Users/infos.html.twig');
    }
}
