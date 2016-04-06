<?php

namespace controller;

use main\NewsMain;

class NewsController extends MainController
{
    public function getOneNews()
    {
        # 1.check news parameters
        $this->newsMain()->oneNewsParameters();

        # 2.find news by id from database
        $this->newsMain()->findNewsFromDb();

        # 3.create result for get one news
        $this->newsMain()->createOneNewsResult();
    }

    private function newsMain(): NewsMain
    {
        return $this->container->get('newsMain');
    }

    public function getAllNews()
    {
        # 1.get all news from database
        $this->newsMain()->newsFromDb();

        # 2.create result for news
        $this->newsMain()->createNewsResult();
    }

    /**
     * created by h.soltani
     */
    public function editNews()
    {
        # 1.check edit news parameters
        $this->newsMain()->editParameters();

        # 2.edit
        $this->newsMain()->edit();
    }
}
