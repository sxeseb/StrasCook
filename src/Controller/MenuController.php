<?php


namespace App\Controller;

use App\Model\ImageManager;
use App\Model\MenuManager;
use App\Service\ValidationService;

class MenuController extends AbstractController
{
    public function menus()
    {
        $menumanager = new MenuManager();
        $menus = $menumanager ->selectAll();
        return $this->twig->render('Menu/menus.html.twig', ['menus' => $menus]);
    }

    public function show(int $id)
    {
        $menuManager = new MenuManager();
        $menu = $menuManager->selectOneMenus($id);
        $images = $menuManager->selectAllImages($id);
        return $this->twig->render('Menu/show.html.twig', ['menu' => $menu, 'images' => $images]);
    }

    public function list()
    {
        $menuManager = new MenuManager();
        $menus = $menuManager->selectAll();
        return $this->twig->render('Menu/list.html.twig', ['menus' => $menus]);
    }
}
