<?php

namespace main;

use model\ShopModel;
use model\UserModel;
use service\HubException;

class ShopMain extends MainMain
{
    private function shopModel(): ShopModel
    {
        return $this->container->get('shopModel');
    }

    private function userModel(): UserModel
    {
        return $this->container->get('userModel');
    }

    private function userMain(): UserMain
    {
        return $this->container->get('userMain');
    }

    /**
     * returns available packages list
     * @throws HubException
     */
    public function getPackagesList()
    {
        $packages = $this->shopModel()->findAllPackages();

        if (!$packages) {
            throw new HubException(HubException::NAP_MESSAGE, HubException::FAP_CODE);
        }

        foreach ($packages as $key => $packageDocument) {
            $result[$key]['id'] = $packageDocument->package_id;
            $result[$key]['title'] = $packageDocument->package_title_persian;
            $result[$key]['gems'] = $packageDocument->gems;
            $result[$key]['lives'] = $packageDocument->lives;
        }

        $this->io->setResponse($result ?? null);
    }

    public function getPackageInfo()
    {
        $packageId = $this->request->packageid;
        $package = $this->shopModel()->findPackageById($packageId);

        if (!$package) {
            throw new HubException(HubException::NAP_MESSAGE, HubException::FAP_CODE);
        }

        $result['id'] = $package->package_id;
        $result['title'] = $package->package_title_persian;
        $result['gems'] = $package->gems;
        $result['lives'] = $package->lives;
        $result['currency'] = $package->currency;
        $result['price'] = $package->price;

        $this->io->setResponse($result ?? null);
    }

    public function getPackage($package_id)
    {
        $package = $this->shopModel()->findPackageById($package_id);

        if (!$package) {
            throw new HubException(HubException::NAP_MESSAGE, HubException::NAP_CODE);
        }

        return $package;
    }

    public function subscribe()
    {
        $result['1'] = "test";
        $this->io->setResponse($result ?? null);
    }
}
