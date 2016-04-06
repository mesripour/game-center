<?php

namespace model;

use MongoDB\BSON\ObjectID;

class LogModel extends MainModel
{
    /**
     * @param array $data
     * @return mixed
     */
    public function insertLog(array $data)
    {
        return $this->mongo('analytic')->insertOne($data)->getInsertedId();
    }

    public function updateLog($logObject, $property, $value)
    {
        $this->mongo('analytic')->updateOne(
            ['_id' => $logObject],
            [
                '$set' => [
                    $property => $value
                ]
            ]
        );
    }

    /**
     * @param ObjectID $logObject
     * @param array $data
     */
    public function updateLogData(ObjectID $logObject, array $data)
    {
        $this->mongo('analytic')->updateOne(
            ['_id' => $logObject],
            [
                '$set' => $data
            ]
        );
    }
}
