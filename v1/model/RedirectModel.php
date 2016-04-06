<?php

namespace model;

class RedirectModel extends MainModel
{
    /**
     * @param string $userId
     * @param array $urls
     */
    public function updateUrl(string $userId, array $urls)
    {
        $this->mongo('redirect')->updateOne(
            ['_id' => $userId],
            [
                '$set' => [
                    'h.url' => $urls['h'],
                    'm.url' => $urls['m'],
                    'l.url' => $urls['l'],
                ]
            ]
        );
    }
}