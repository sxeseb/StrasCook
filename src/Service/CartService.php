<?php


namespace App\Service;

class CartService
{
    public function addToCart()
    {
        $errors = [];
        $datas = [];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!isset($_POST['gridRadios']) || empty($_POST['gridRadios'])) {
                $errors['price'] = "Selectionnez une option de prix";
            } else {
                $price = $this->testInput($_POST['gridRadios']);
                $datas['price'] = $price;
            }

            if (!isset($_POST['menu_q']) || empty($_POST['menu_q'])) {
                $errors['quantity'] = "Selectionnez un nombre de couverts";
            } else {
                $quantity = $this->testInput($_POST['menu_q']);
                $datas['quantity'] = $quantity;
            }

            if (isset($_POST['menu_id']) && !empty($_POST['menu_id'])) {
                $menuId = $this->testInput($_POST['menu_id']);
                $datas['menuId'] = $menuId;
            }

            if (isset($_POST['menu_name']) && !empty($_POST['menu_name'])) {
                $menuName = $this->testInput($_POST['menu_name']);
                $datas['menuName'] = $menuName;
            }
        }

        if (empty($errors)) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'][] = $datas;
            } else {
                $match = 0;
                for ($i = 0; $i < count($_SESSION['cart']); $i++) {
                    if ($_SESSION['cart'][$i]['menuId'] == $datas['menuId']
                        && $_SESSION['cart'][$i]['price'] == $datas['price']) {
                        $_SESSION['cart'][$i]['quantity'] += $datas['quantity'];
                        $match++;
                    }
                }
                if ($match == 0) {
                    $_SESSION['cart'][] = $datas;
                }
            }
        }

        return array($errors, $datas);
    }

    public function calculTotal($datas) :array
    {
        $localCount = 0;
        $normalCount = 0;
        $total = 0;
        foreach ($datas as $row) {
            if ($row['price'] == 1) {
                $normalCount += $row['quantity'];
            } else {
                $localCount += $row['quantity'];
            }
        }

        $total = $localCount * 30 + $normalCount * 20;
        return array('normal' => $normalCount, 'local' => $localCount, 'total' => $total);
    }



    public function testInput($input)
    {
        $input = trim($input);
        $input = stripcslashes($input);
        $input = htmlspecialchars($input);

        return $input;
    }
}
