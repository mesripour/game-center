<?php

namespace main;

use service\HubException;

class BotMain extends MainMain
{
    public $runtimeVariable;

    /**
     * @param string $userId
     * @param string $inlineMessageId
     * @param int $score
     */
    public function upsertTelegramScore(string $userId, string $inlineMessageId, int $score)
    {
        if ($inlineMessageId) {
            file_get_contents(
                $this->setting['bot']['id'] .
                '/setGameScore?score=' . $score .
                '&inline_message_id=' . $inlineMessageId .
                '&user_id=' . $userId .
                '&disable_edit_message=' . false
            );
        }
    }

    public function load()
    {
        return header("Location: https://telegram.me/" . $this->setting['bot']['name']);
    }

    public function loadParameters()
    {
        $this->runtimeVariable['gameId'] = $this->request->game_id;
        if (!$this->runtimeVariable['gameId']) {
            throw new HubException(HubException::ISE_MESSAGE, HubException::FGBI_CODE);
        }
    }

    public function redirectToGame()
    {
        return header("Location: http://telegram.me/" . $this->setting['bot']['name'] .
            '?start=game-' . $this->runtimeVariable['gameId']);
    }
}
