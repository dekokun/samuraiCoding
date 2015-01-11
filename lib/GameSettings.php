<?php

class GameSettings {
    public $maxTurn;
    public $numOfPlayers;
    public $numOfLords;
    public $militaryCounts;

    public function setSettingOne(array $input) {
        $this->maxTurn = $input[0];
        $this->numOfPlayers = $input[1];
        $this->numOfLords = $input[2];
    }

    public function setSettingTwo(array $input) {
        $this->militaryCounts = $input;
    }
}
