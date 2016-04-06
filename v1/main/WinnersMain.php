<?php

namespace main;

use model\WinnerModel;

class WinnersMain extends MainMain
{

    private function WinnerModel(): WinnerModel
    {
        return $this->container->get('winnerModel');
    }

    public function getAllWinners()
    {
        if($this->request->winner_title){
            $title = $this->request->winner_title;
            $winnersDocuments = $this->WinnerModel()->findOneWinner($title);
            $description = (array)$winnersDocuments['description'];
            $winnersDocuments = array_splice($description,10);
        }else{
            $winnersDocuments = $this->WinnerModel()->findAllWinners();
            foreach ($winnersDocuments as $key => $value) {
                unset($winnersDocuments[$key]['_id']);
                $description = (array)$value['description'];
                $winnersDocuments[$key]['description'] = array_splice($description,0,10);
            }
        }
        $this->io->setResponse($winnersDocuments ?? null);
    }
}