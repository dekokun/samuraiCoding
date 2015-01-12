<?php

class Game {

    /**
     * @var StdIO
     */
    protected $io;
    /**
     * @var GameSettings
     */
    protected $setting;
    /**
     * @var Turn
     */
    protected $turn;
    /**
     * @var Lords
     */
    protected $lords;
    /**
     * @var \Rule\RuleSelector
     */
    protected $ruleSelector;

    public function __construct() {
        $this->io = new StdIO(fopen('php://stdin', 'r'));
        $this->setting = new GameSettings();
        $this->ruleSelector = new \Rule\RuleSelector([
            new \Rule\MonteCarloFirstHalf(),
            new \Rule\MonteCarloSecondHalf(),
            new \Rule\AllOneSelect(),
        ]);
    }

    public function main() {
        $this->io->outPut('READY' . PHP_EOL);
        $this->readGameSetting();

        for ($i = 0; $i < $this->setting->maxTurn; $i++) {
            $this->readData();
            $rule = $this->ruleSelector
                ->choice($this->lords, $this->turn);

            if ($this->turn->getNextTurn() === 6) {
                // 6ターン目から一度情報が新しくなるためデート回数はリセットしておきたかったがなぜか弱くなる
                // $this->lords->resetEstimatedNegotiationCount();
                // $result = $rule->storeIntermediateResult($this->lords);
                record($result);
            }
            $lords = $rule->result($this->lords, $this->turn);
            $this->io->outPutArray($lords->toArray());
        }

    }

    private function readGameSetting() {
        $this->setting->setSettingOne($this->io->getInArray());

        $this->setting->setSettingTwo($this->io->getInArray());
        $lords = [];
        foreach ($this->setting->militaryCounts as $index => $militaryCount) {
            array_push($lords, new Lord($index, (integer)$militaryCount));
        }
        $this->lords = new Lords($lords);
    }

    private function readData() {
        list($turn, $day) = $this->io->getInArray();
        $this->turn = new Turn($turn, $day);
        foreach($this->lords as $i => $_) {
            $revealedScores = $this->io->getInArray();
            $this->lords[$i]->setRevealedScores($revealedScores);
        }
        $realScores = $this->io->getInArray();
        foreach($this->lords as $i => $_) {
            $this->lords[$i]->setRealScore((integer)$realScores[$i]);
        }
        if ($this->turn->previousTurnIsNight()) {
            $dated = $this->io->getInArray();
            $allDateCount = array_sum($dated);
            if ($allDateCount) {
                foreach ($this->lords as $i => $_) {
                    if ($allDateCount) {
                        $this->lords[$i]->setEstimatedNegotiationCountInTurn((integer)$dated[$i] / $allDateCount);
                    }
                }
            }
        }
    }
}
