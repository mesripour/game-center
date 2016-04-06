<?php

namespace model;

class ShopModel extends MainModel
{
    /**
     * lists all available packages
     * @return array
     */
    public function findAllPackages()
    {
        return $this->mongo('packages')->find()->toArray();
    }

    /**
     * returns single package information
     * @param int $packageId
     * @return array|null|object
     */
    public function findPackageById(int $packageId)
    {
        return $this->mongo('packages')->findOne(['package_id' => $packageId]);
    }
}
