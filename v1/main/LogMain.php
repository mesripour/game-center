<?php

namespace main;

use model\LogModel;

class LogMain extends MainMain
{
    public $data;

    /**
     * @return LogModel
     */
    private function logModel(): LogModel
    {
        return $this->container->get('logModel');
    }

    public function saveLog()
    {
        return $this->logModel()->insertLog($this->data);
    }

    /**
     * @param $logType
     */
    public function setLogType($logType)
    {
        switch ($logType) {
            case 'game-start':
                $this->data = $this->startData();
                break;
            case 'set-score':
                $this->data = $this->finishData();
                break;
        }
    }

    /**
     * @param $property
     * @param $value
     */
    public function updateProperty($property, $value)
    {
        $keys = explode(':', $property);
        $reference = &$this->data;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $reference)) {
                $reference[$key] = [];
            }
            $reference = &$reference[$key];
        }
        $reference = $value;
        unset($reference);
    }

    /**
     * @param $logObject
     * @param $property
     * @param $value
     */
    public function updateDatabaseProperty($logObject, $property, $value)
    {
        $property = str_replace(':', '.', $property);
        if ($value === false) {
            return $this->logModel()->updateLog($logObject, 'pass_all', $value);
        }
        return $this->logModel()->updateLog($logObject, $property, $value);
    }

    /**
     * @param $logObject
     */
    public function updateDatabaseArray($logObject)
    {
        return $this->logModel()->updateLogData($logObject, $this->data);
    }

    /**
     * @return array
     */
    private function startData(): array
    {
        return [
            'pass_all' => true,
            'user_id' => null,
            'username' => null,
            'userType' => null,
            'game_id' => null,
            'time' => [
                'start' => time(),
                'finish' => 0,
                'duration' => 0,
            ],
        ];
    }

    /**
     * @return array
     */
    private function finishData(): array
    {
        return [
            'finish' => [
                'check_parameter_pass' => true,
                'score_hash_pass' => true,
                'verify_data_hash_pass' => true,
                'verify_user_type_pass' => true,
                'data_count_pass' => true,
                'pure_score' => 0,
                'final_score' => 0,
                'user_ban_pass' => true,
                'play_count_pass' => true,
                'asset' => [
                    'coin' => 0
                ]
            ]
        ];
    }
}
