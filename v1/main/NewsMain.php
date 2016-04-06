<?php

namespace main;

use model\NewsModel;
use MongoDB\BSON\ObjectID;
use service\HubException;

class NewsMain extends MainMain
{
    public $runtimeVariable;

    public function newsFromDb()
    {
        $this->runtimeVariable['allNews'] = $this->newsModel()->findAllNews();
        if (!$this->runtimeVariable['allNews']) {
            throw new HubException(HubException::EMPTY_MESSAGE, HubException::SUCCESS_CODE);
        }
    }

    private function newsModel(): NewsModel
    {
        return $this->container->get('newsModel');
    }

    public function createNewsResult()
    {
        foreach ($this->runtimeVariable['allNews'] as $key => $value) {
            $result[$key]['id'] = (string)$value->_id;
            $result[$key]['title'] = $value->title;
            $result[$key]['date'] = $value->create_time;
            $result[$key]['create_time'] = (time() - $value->create_time);
        }

        $this->io->setResponse($result);
    }

    public function oneNewsParameters()
    {
        $this->runtimeVariable['newsId'] = $this->request->news_id;
        if (!$this->runtimeVariable['newsId']) {
            throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
        }
    }

    public function findNewsFromDb()
    {
        # validate mongo object id
        try {
            $newsId = new ObjectID($this->runtimeVariable['newsId']);
        } catch (\Exception $e) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::ISE_MESSAGE);
        }

        # find from database
        $this->runtimeVariable['newsDocument'] = $this->newsModel()->findNewsById($newsId);
        if (!$this->runtimeVariable['newsDocument']) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::GON_CODE);
        }
    }

    public function createOneNewsResult()
    {
        $newsDocument = $this->runtimeVariable['newsDocument'];
        unset($newsDocument->_id);
        $newsDocument['create_time'] = (time() - $newsDocument['create_time']);
        $newsDocument['img_url'] = $newsDocument->img->normal;
        $this->io->setResponse($newsDocument);
    }

    public function editParameters()
    {
        if (!$this->request->title ||
            !$this->request->content ||
            !$this->request->img_url ||
            !$this->request->tmb_url
        ) {
            throw new HubException(HubException::PARAMETER_MESSAGE, HubException::PARAMETER_CODE);
        }
    }
}
