<?php

namespace Rule;

class AllOneSelect extends Rule {
    protected function doEvaluate(\Lords $lords, \Turn $turn) {
        if ($turn->getNextTurn() === 1) {
            return 0;
        }
        return 0;
    }
    public function result(\Lords $lords, \Turn $turn) {
        $resultLords = [];
        $maxLords = $lords->getMaxMilitaryCountsLord();
        $selectedLord = $maxLords->getRandomLord();
        foreach($turn->dayIter() as $_) {
            $resultLords[] = $selectedLord;
        }
        return new \Lords($resultLords);
    }
}
