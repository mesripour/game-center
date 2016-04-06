<?php

namespace model;

use MongoDB\BSON\ObjectID;

class NewsModel extends MainModel
{
    /**
     * @param ObjectID $newsId
     * @return array|null|object
     */
    public function findNewsById(ObjectID $newsId)
    {
        return $this->mongo('news')->findOne(['_id' => $newsId]);
    }

    /**
     * @return \MongoDB\Driver\Cursor
     */
    public function findAllNews()
    {
        return $this->mongo('news')->find([], ['sort' => ['create_time' => 1]]);
    }

    /**
     * @param string $title
     * @param string $content
     * @param string $imgUrl
     * @param string $tmbUrl
     */
    public function addNews(string $title, string $content, string $imgUrl, string $tmbUrl)
    {
        $this->mongo('news')->insertOne([
            'title' => $title,
            'content' => $content,
            'img' => [
                'normal' => $imgUrl,
                'thumbnail' => $tmbUrl
            ],
            'create_time' => time()
        ]);
    }

    /**
     * @param ObjectID $newsId
     * @param string $title
     * @param string $content
     * @param string $imgUrl
     * @param string $tmbUrl
     */
    public function editNews(ObjectID $newsId, string $title, string $content, string $imgUrl, string $tmbUrl)
    {
        $this->mongo('news')->updateOne(
            ['_id' => $newsId],
            ['$set' => [
                'title' => $title,
                'content' => $content,
                'img.normal' => $imgUrl,
                'img.thumbnail' => $tmbUrl,
            ]]
        );
    }
}
