<?php


namespace App\Controller;

class OrdersController extends AbstractController
{
    public function orderDetail($resaId)
    {
        return $this->twig->render('Admin/reservations.html.twig');
    }
}
