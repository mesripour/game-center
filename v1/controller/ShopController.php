<?php

namespace controller;

use main\ShopMain;

class ShopController extends MainController
{
    private function shopMain(): ShopMain
    {
        return $this->container->get('shopMain');
    }

    public function getPackagesList()
    {
        $this->shopMain()->getPackagesList();
    }

    public function getPackageInfo()
    {
        $this->shopMain()->getPackageInfo();
    }

    public function buyPackage()
    {
        $this->shopMain()->buyPackage();
    }

    public function subscribe()
    {
        $this->shopMain()->subscribe();
    }
}
