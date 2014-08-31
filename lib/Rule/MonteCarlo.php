<?php

namespace Rule;

class MonteCarlo extends Rule
{
    const HOLIDAY_TRIAL_COUNT = 22;
    const WEEKDAY_TRIAL_RATE = 18;

    protected function doEvaluate(\Heroines $heroines, \Turn $turn)
    {
        return 1;
    }

    public function result(\Heroines $heroines, \Turn $turn)
    {
        $myPointChoiceCombination = $this->myPointChoiceCombination($turn);
        $topCounts = array_fill(0, count($myPointChoiceCombination), 0);
        if ($turn->nextTurnIsHoliday()) {
            $trialCount = static::HOLIDAY_TRIAL_COUNT * static::WEEKDAY_TRIAL_RATE;
        } else {
            $trialCount = static::HOLIDAY_TRIAL_COUNT;
        }
        logging('count :: ' . $trialCount);
        for ($i = 0; $i < $trialCount; $i++) {
            // 過去の実績を足す
            $allRemainPoints = $this->allRemainPoints($heroines, $turn);
            foreach($heroines as $heroine) {
                // 休日のデートを加味したスコアは自分(0番)のものは完全にわかっているためそれを足す
                $allRemainPoints[0][$heroine->getIndex()]
                    += $heroine->getRealScore();

                // 自分以外は、休日のデートはわからないため期待値を足す
                foreach([1,2,3] as $playerIndex) {
                     $allRemainPoints[$playerIndex][$heroine->getIndex()]
                         += ($heroine->getDateCount() * 4);
                }
                // 自分以外のわかっている値を足す
                foreach($heroine->getRevealedScores() as $playerIndex => $point) {
                    if ($playerIndex === 0) {continue;}
                    $allRemainPoints[$playerIndex][$heroine->getIndex()] += $point;
                }
            }
            foreach ($myPointChoiceCombination as $j => $choice) {
                // 自分の点数を置き換え
                $points = $allRemainPoints;
                $myPoints = array_map(
                    function ($a, $b) {
                        return $a + $b;
                    },
                    $points[0],
                    $choice
                );
                $points[0] = $myPoints;
                if ($this->isTop($heroines, $points)) {
                    $topCounts[$j] += 1;
                }
            }
        }
        $maxTopCount = max($topCounts);
        $maxTopStrategies = array_keys($topCounts, $maxTopCount, true);
        $maxTopStrategy = $maxTopStrategies[array_rand($maxTopStrategies)];
        $result = [];

        // 結果の整形
        foreach(
            $myPointChoiceCombination[$maxTopStrategy] as $heroineIndex => $count
        ) {
            if ($turn->nextTurnIsHoliday()) {
                $count = $count / 2;
            }
            for($i = 0; $i < $count; $i++) {
                $result[] = $heroineIndex;
            }
        }
        return new \Heroines($result);
    }

    public function isTop(\Heroines $heroines, array $points) {
        $result = array_fill(0, 4, 0);
        $transposition = $this->transverseMatrix($points);
        foreach($transposition as $heroineIndex => $heroinePoints) {
            $maxPoint = max($heroinePoints);
            $minPoint = min($heroinePoints);
            $winPlayers = array_keys($heroinePoints, $maxPoint, true);
            $loosePlayers = array_keys($heroinePoints, $minPoint, true);
            $enthusiasm = $heroines->getEnthusiasms()[$heroineIndex];
            $point = $enthusiasm / count($winPlayers);
            $loosePoint = $enthusiasm / count($loosePlayers);
            foreach($winPlayers as $index) {
                $result[$index] += $point;
            }
            foreach($loosePlayers as $index) {
                $result[$index] -= $loosePoint;
            }
        }
        if ($result[0] === max($result)) {
            return true;
        }
        return false;
    }

    private function transverseMatrix($points) {
        return call_user_func_array(
            "array_map",
            array_merge(
                array(null),
                $points
            )
        );
    }

    /**
     * @param \Turn $turn
     * @return array
     */
    public function myPointChoiceCombination(\Turn $turn)
    {
        static $combies;
        if ($turn->nextTurnIsHoliday()) {
            if (! isset($combies[\Turn::HOLIDAY])) {
                $combies[\Turn::HOLIDAY] = [
                    [4, 0, 0, 0, 0, 0, 0, 0],
                    [2, 2, 0, 0, 0, 0, 0, 0],
                    [2, 0, 2, 0, 0, 0, 0, 0],
                    [2, 0, 0, 2, 0, 0, 0, 0],
                    [2, 0, 0, 0, 2, 0, 0, 0],
                    [2, 0, 0, 0, 0, 2, 0, 0],
                    [2, 0, 0, 0, 0, 0, 2, 0],
                    [2, 0, 0, 0, 0, 0, 0, 2],
                    [0, 4, 0, 0, 0, 0, 0, 0],
                    [0, 2, 2, 0, 0, 0, 0, 0],
                    [0, 2, 0, 2, 0, 0, 0, 0],
                    [0, 2, 0, 0, 2, 0, 0, 0],
                    [0, 2, 0, 0, 0, 2, 0, 0],
                    [0, 2, 0, 0, 0, 0, 2, 0],
                    [0, 2, 0, 0, 0, 0, 0, 2],
                    [0, 0, 4, 0, 0, 0, 0, 0],
                    [0, 0, 2, 2, 0, 0, 0, 0],
                    [0, 0, 2, 0, 2, 0, 0, 0],
                    [0, 0, 2, 0, 0, 2, 0, 0],
                    [0, 0, 2, 0, 0, 0, 2, 0],
                    [0, 0, 2, 0, 0, 0, 0, 2],
                    [0, 0, 0, 4, 0, 0, 0, 0],
                    [0, 0, 0, 2, 2, 0, 0, 0],
                    [0, 0, 0, 2, 0, 2, 0, 0],
                    [0, 0, 0, 2, 0, 0, 2, 0],
                    [0, 0, 0, 2, 0, 0, 0, 2],
                    [0, 0, 0, 0, 4, 0, 0, 0],
                    [0, 0, 0, 0, 2, 2, 0, 0],
                    [0, 0, 0, 0, 2, 0, 2, 0],
                    [0, 0, 0, 0, 2, 0, 0, 2],
                    [0, 0, 0, 0, 0, 4, 0, 0],
                    [0, 0, 0, 0, 0, 2, 2, 0],
                    [0, 0, 0, 0, 0, 2, 0, 2],
                    [0, 0, 0, 0, 0, 0, 4, 0],
                    [0, 0, 0, 0, 0, 0, 2, 2],
                    [0, 0, 0, 0, 0, 0, 0, 4],
                ];
            }
            return $combies[\Turn::HOLIDAY];
        }
        if (! isset($combies[\Turn::WEEKDAY])) {
            $combies[\Turn::WEEKDAY] = [
                [5, 0, 0, 0, 0, 0, 0, 0],
                [4, 1, 0, 0, 0, 0, 0, 0],
                [4, 0, 1, 0, 0, 0, 0, 0],
                [4, 0, 0, 1, 0, 0, 0, 0],
                [4, 0, 0, 0, 1, 0, 0, 0],
                [4, 0, 0, 0, 0, 1, 0, 0],
                [4, 0, 0, 0, 0, 0, 1, 0],
                [4, 0, 0, 0, 0, 0, 0, 1],
                [3, 2, 0, 0, 0, 0, 0, 0],
                [3, 1, 1, 0, 0, 0, 0, 0],
                [3, 1, 0, 1, 0, 0, 0, 0],
                [3, 1, 0, 0, 1, 0, 0, 0],
                [3, 1, 0, 0, 0, 1, 0, 0],
                [3, 1, 0, 0, 0, 0, 1, 0],
                [3, 1, 0, 0, 0, 0, 0, 1],
                [3, 0, 2, 0, 0, 0, 0, 0],
                [3, 0, 1, 1, 0, 0, 0, 0],
                [3, 0, 1, 0, 1, 0, 0, 0],
                [3, 0, 1, 0, 0, 1, 0, 0],
                [3, 0, 1, 0, 0, 0, 1, 0],
                [3, 0, 1, 0, 0, 0, 0, 1],
                [3, 0, 0, 2, 0, 0, 0, 0],
                [3, 0, 0, 1, 1, 0, 0, 0],
                [3, 0, 0, 1, 0, 1, 0, 0],
                [3, 0, 0, 1, 0, 0, 1, 0],
                [3, 0, 0, 1, 0, 0, 0, 1],
                [3, 0, 0, 0, 2, 0, 0, 0],
                [3, 0, 0, 0, 1, 1, 0, 0],
                [3, 0, 0, 0, 1, 0, 1, 0],
                [3, 0, 0, 0, 1, 0, 0, 1],
                [3, 0, 0, 0, 0, 2, 0, 0],
                [3, 0, 0, 0, 0, 1, 1, 0],
                [3, 0, 0, 0, 0, 1, 0, 1],
                [3, 0, 0, 0, 0, 0, 2, 0],
                [3, 0, 0, 0, 0, 0, 1, 1],
                [3, 0, 0, 0, 0, 0, 0, 2],
                [2, 3, 0, 0, 0, 0, 0, 0],
                [2, 2, 1, 0, 0, 0, 0, 0],
                [2, 2, 0, 1, 0, 0, 0, 0],
                [2, 2, 0, 0, 1, 0, 0, 0],
                [2, 2, 0, 0, 0, 1, 0, 0],
                [2, 2, 0, 0, 0, 0, 1, 0],
                [2, 2, 0, 0, 0, 0, 0, 1],
                [2, 1, 2, 0, 0, 0, 0, 0],
                [2, 1, 1, 1, 0, 0, 0, 0],
                [2, 1, 1, 0, 1, 0, 0, 0],
                [2, 1, 1, 0, 0, 1, 0, 0],
                [2, 1, 1, 0, 0, 0, 1, 0],
                [2, 1, 1, 0, 0, 0, 0, 1],
                [2, 1, 0, 2, 0, 0, 0, 0],
                [2, 1, 0, 1, 1, 0, 0, 0],
                [2, 1, 0, 1, 0, 1, 0, 0],
                [2, 1, 0, 1, 0, 0, 1, 0],
                [2, 1, 0, 1, 0, 0, 0, 1],
                [2, 1, 0, 0, 2, 0, 0, 0],
                [2, 1, 0, 0, 1, 1, 0, 0],
                [2, 1, 0, 0, 1, 0, 1, 0],
                [2, 1, 0, 0, 1, 0, 0, 1],
                [2, 1, 0, 0, 0, 2, 0, 0],
                [2, 1, 0, 0, 0, 1, 1, 0],
                [2, 1, 0, 0, 0, 1, 0, 1],
                [2, 1, 0, 0, 0, 0, 2, 0],
                [2, 1, 0, 0, 0, 0, 1, 1],
                [2, 1, 0, 0, 0, 0, 0, 2],
                [1, 4, 0, 0, 0, 0, 0, 0],
                [1, 3, 1, 0, 0, 0, 0, 0],
                [1, 3, 0, 1, 0, 0, 0, 0],
                [1, 3, 0, 0, 1, 0, 0, 0],
                [1, 3, 0, 0, 0, 1, 0, 0],
                [1, 3, 0, 0, 0, 0, 1, 0],
                [1, 3, 0, 0, 0, 0, 0, 1],
                [1, 2, 2, 0, 0, 0, 0, 0],
                [1, 2, 1, 1, 0, 0, 0, 0],
                [1, 2, 1, 0, 1, 0, 0, 0],
                [1, 2, 1, 0, 0, 1, 0, 0],
                [1, 2, 1, 0, 0, 0, 1, 0],
                [1, 2, 1, 0, 0, 0, 0, 1],
                [1, 2, 0, 2, 0, 0, 0, 0],
                [1, 2, 0, 1, 1, 0, 0, 0],
                [1, 2, 0, 1, 0, 1, 0, 0],
                [1, 2, 0, 1, 0, 0, 1, 0],
                [1, 2, 0, 1, 0, 0, 0, 1],
                [1, 2, 0, 0, 2, 0, 0, 0],
                [1, 2, 0, 0, 1, 1, 0, 0],
                [1, 2, 0, 0, 1, 0, 1, 0],
                [1, 2, 0, 0, 1, 0, 0, 1],
                [1, 2, 0, 0, 0, 2, 0, 0],
                [1, 2, 0, 0, 0, 1, 1, 0],
                [1, 2, 0, 0, 0, 1, 0, 1],
                [1, 2, 0, 0, 0, 0, 2, 0],
                [1, 2, 0, 0, 0, 0, 1, 1],
                [1, 2, 0, 0, 0, 0, 0, 2],
                [1, 1, 3, 0, 0, 0, 0, 0],
                [1, 1, 2, 1, 0, 0, 0, 0],
                [1, 1, 2, 0, 1, 0, 0, 0],
                [1, 1, 2, 0, 0, 1, 0, 0],
                [1, 1, 2, 0, 0, 0, 1, 0],
                [1, 1, 2, 0, 0, 0, 0, 1],
                [1, 1, 1, 2, 0, 0, 0, 0],
                [1, 1, 1, 1, 1, 0, 0, 0],
                [1, 1, 1, 1, 0, 1, 0, 0],
                [1, 1, 1, 1, 0, 0, 1, 0],
                [1, 1, 1, 1, 0, 0, 0, 1],
                [1, 1, 1, 0, 2, 0, 0, 0],
                [1, 1, 1, 0, 1, 1, 0, 0],
                [1, 1, 1, 0, 1, 0, 1, 0],
                [1, 1, 1, 0, 1, 0, 0, 1],
                [1, 1, 1, 0, 0, 2, 0, 0],
                [1, 1, 1, 0, 0, 1, 1, 0],
                [1, 1, 1, 0, 0, 1, 0, 1],
                [1, 1, 1, 0, 0, 0, 2, 0],
                [1, 1, 1, 0, 0, 0, 1, 1],
                [1, 1, 1, 0, 0, 0, 0, 2],
                [1, 1, 0, 3, 0, 0, 0, 0],
                [1, 1, 0, 2, 1, 0, 0, 0],
                [1, 1, 0, 2, 0, 1, 0, 0],
                [1, 1, 0, 2, 0, 0, 1, 0],
                [1, 1, 0, 2, 0, 0, 0, 1],
                [1, 1, 0, 1, 2, 0, 0, 0],
                [1, 1, 0, 1, 1, 1, 0, 0],
                [1, 1, 0, 1, 1, 0, 1, 0],
                [1, 1, 0, 1, 1, 0, 0, 1],
                [1, 1, 0, 1, 0, 2, 0, 0],
                [1, 1, 0, 1, 0, 1, 1, 0],
                [1, 1, 0, 1, 0, 1, 0, 1],
                [1, 1, 0, 1, 0, 0, 2, 0],
                [1, 1, 0, 1, 0, 0, 1, 1],
                [1, 1, 0, 1, 0, 0, 0, 2],
                [1, 1, 0, 0, 3, 0, 0, 0],
                [1, 1, 0, 0, 2, 1, 0, 0],
                [1, 1, 0, 0, 2, 0, 1, 0],
                [1, 1, 0, 0, 2, 0, 0, 1],
                [1, 1, 0, 0, 1, 2, 0, 0],
                [1, 1, 0, 0, 1, 1, 1, 0],
                [1, 1, 0, 0, 1, 1, 0, 1],
                [1, 1, 0, 0, 1, 0, 2, 0],
                [1, 1, 0, 0, 1, 0, 1, 1],
                [1, 1, 0, 0, 1, 0, 0, 2],
                [1, 1, 0, 0, 0, 3, 0, 0],
                [1, 1, 0, 0, 0, 2, 1, 0],
                [1, 1, 0, 0, 0, 2, 0, 1],
                [1, 1, 0, 0, 0, 1, 2, 0],
                [1, 1, 0, 0, 0, 1, 1, 1],
                [1, 1, 0, 0, 0, 0, 3, 0],
                [1, 1, 0, 0, 0, 0, 2, 1],
                [1, 1, 0, 0, 0, 0, 1, 2],
                [1, 1, 0, 0, 0, 0, 0, 3],
                [1, 0, 4, 0, 0, 0, 0, 0],
                [1, 0, 3, 1, 0, 0, 0, 0],
                [1, 0, 3, 0, 1, 0, 0, 0],
                [1, 0, 3, 0, 0, 1, 0, 0],
                [1, 0, 3, 0, 0, 0, 1, 0],
                [1, 0, 3, 0, 0, 0, 0, 1],
                [1, 0, 2, 2, 0, 0, 0, 0],
                [1, 0, 2, 1, 1, 0, 0, 0],
                [1, 0, 2, 1, 0, 1, 0, 0],
                [1, 0, 2, 1, 0, 0, 1, 0],
                [1, 0, 2, 1, 0, 0, 0, 1],
                [1, 0, 2, 0, 2, 0, 0, 0],
                [1, 0, 2, 0, 1, 1, 0, 0],
                [1, 0, 2, 0, 1, 0, 1, 0],
                [1, 0, 2, 0, 1, 0, 0, 1],
                [1, 0, 2, 0, 0, 2, 0, 0],
                [1, 0, 2, 0, 0, 1, 1, 0],
                [1, 0, 2, 0, 0, 1, 0, 1],
                [1, 0, 2, 0, 0, 0, 2, 0],
                [1, 0, 2, 0, 0, 0, 1, 1],
                [1, 0, 2, 0, 0, 0, 0, 2],
                [1, 0, 1, 3, 0, 0, 0, 0],
                [1, 0, 1, 2, 1, 0, 0, 0],
                [1, 0, 1, 2, 0, 1, 0, 0],
                [1, 0, 1, 2, 0, 0, 1, 0],
                [1, 0, 1, 2, 0, 0, 0, 1],
                [1, 0, 1, 1, 2, 0, 0, 0],
                [1, 0, 1, 1, 1, 1, 0, 0],
                [1, 0, 1, 1, 1, 0, 1, 0],
                [1, 0, 1, 1, 1, 0, 0, 1],
                [1, 0, 1, 1, 0, 2, 0, 0],
                [1, 0, 1, 1, 0, 1, 1, 0],
                [1, 0, 1, 1, 0, 1, 0, 1],
                [1, 0, 1, 1, 0, 0, 2, 0],
                [1, 0, 1, 1, 0, 0, 1, 1],
                [1, 0, 1, 1, 0, 0, 0, 2],
                [1, 0, 1, 0, 3, 0, 0, 0],
                [1, 0, 1, 0, 2, 1, 0, 0],
                [1, 0, 1, 0, 2, 0, 1, 0],
                [1, 0, 1, 0, 2, 0, 0, 1],
                [1, 0, 1, 0, 1, 2, 0, 0],
                [1, 0, 1, 0, 1, 1, 1, 0],
                [1, 0, 1, 0, 1, 1, 0, 1],
                [1, 0, 1, 0, 1, 0, 2, 0],
                [1, 0, 1, 0, 1, 0, 1, 1],
                [1, 0, 1, 0, 1, 0, 0, 2],
                [1, 0, 1, 0, 0, 3, 0, 0],
                [1, 0, 1, 0, 0, 2, 1, 0],
                [1, 0, 1, 0, 0, 2, 0, 1],
                [1, 0, 1, 0, 0, 1, 2, 0],
                [1, 0, 1, 0, 0, 1, 1, 1],
                [1, 0, 1, 0, 0, 1, 0, 2],
                [1, 0, 1, 0, 0, 0, 3, 0],
                [1, 0, 1, 0, 0, 0, 2, 1],
                [1, 0, 1, 0, 0, 0, 1, 2],
                [1, 0, 1, 0, 0, 0, 0, 3],
                [1, 0, 0, 4, 0, 0, 0, 0],
                [1, 0, 0, 3, 1, 0, 0, 0],
                [1, 0, 0, 3, 0, 1, 0, 0],
                [1, 0, 0, 3, 0, 0, 1, 0],
                [1, 0, 0, 3, 0, 0, 0, 1],
                [1, 0, 0, 2, 2, 0, 0, 0],
                [1, 0, 0, 2, 1, 1, 0, 0],
                [1, 0, 0, 2, 1, 0, 1, 0],
                [1, 0, 0, 2, 1, 0, 0, 1],
                [1, 0, 0, 2, 0, 2, 0, 0],
                [1, 0, 0, 2, 0, 1, 1, 0],
                [1, 0, 0, 2, 0, 1, 0, 1],
                [1, 0, 0, 2, 0, 0, 2, 0],
                [1, 0, 0, 2, 0, 0, 1, 1],
                [1, 0, 0, 2, 0, 0, 0, 2],
                [1, 0, 0, 1, 3, 0, 0, 0],
                [1, 0, 0, 1, 2, 1, 0, 0],
                [1, 0, 0, 1, 2, 0, 1, 0],
                [1, 0, 0, 1, 2, 0, 0, 1],
                [1, 0, 0, 1, 1, 2, 0, 0],
                [1, 0, 0, 1, 1, 1, 1, 0],
                [1, 0, 0, 1, 1, 1, 0, 1],
                [1, 0, 0, 1, 1, 0, 2, 0],
                [1, 0, 0, 1, 1, 0, 1, 1],
                [1, 0, 0, 1, 1, 0, 0, 2],
                [1, 0, 0, 1, 0, 3, 0, 0],
                [1, 0, 0, 1, 0, 2, 1, 0],
                [1, 0, 0, 1, 0, 2, 0, 1],
                [1, 0, 0, 1, 0, 1, 2, 0],
                [1, 0, 0, 1, 0, 1, 1, 1],
                [1, 0, 0, 1, 0, 0, 3, 0],
                [1, 0, 0, 1, 0, 0, 2, 1],
                [1, 0, 0, 1, 0, 0, 0, 3],
                [1, 0, 0, 0, 4, 0, 0, 0],
                [1, 0, 0, 0, 3, 1, 0, 0],
                [1, 0, 0, 0, 3, 0, 1, 0],
                [1, 0, 0, 0, 3, 0, 0, 1],
                [1, 0, 0, 0, 2, 2, 0, 0],
                [1, 0, 0, 0, 2, 1, 1, 0],
                [1, 0, 0, 0, 2, 1, 0, 1],
                [1, 0, 0, 0, 2, 0, 2, 0],
                [1, 0, 0, 0, 2, 0, 1, 1],
                [1, 0, 0, 0, 2, 0, 0, 2],
                [1, 0, 0, 0, 1, 3, 0, 0],
                [1, 0, 0, 0, 1, 2, 1, 0],
                [1, 0, 0, 0, 1, 2, 0, 1],
                [1, 0, 0, 0, 1, 1, 2, 0],
                [1, 0, 0, 0, 1, 1, 1, 1],
                [1, 0, 0, 0, 1, 1, 0, 2],
                [1, 0, 0, 0, 1, 0, 3, 0],
                [1, 0, 0, 0, 1, 0, 2, 1],
                [1, 0, 0, 0, 1, 0, 1, 2],
                [1, 0, 0, 0, 1, 0, 0, 3],
                [0, 5, 0, 0, 0, 0, 0, 0],
                [0, 4, 1, 0, 0, 0, 0, 0],
                [0, 4, 0, 1, 0, 0, 0, 0],
                [0, 4, 0, 0, 1, 0, 0, 0],
                [0, 4, 0, 0, 0, 1, 0, 0],
                [0, 4, 0, 0, 0, 0, 1, 0],
                [0, 4, 0, 0, 0, 0, 0, 1],
                [0, 3, 2, 0, 0, 0, 0, 0],
                [0, 3, 1, 1, 0, 0, 0, 0],
                [0, 3, 1, 0, 1, 0, 0, 0],
                [0, 3, 1, 0, 0, 1, 0, 0],
                [0, 3, 1, 0, 0, 0, 1, 0],
                [0, 3, 1, 0, 0, 0, 0, 1],
                [0, 3, 0, 2, 0, 0, 0, 0],
                [0, 3, 0, 1, 1, 0, 0, 0],
                [0, 3, 0, 1, 0, 1, 0, 0],
                [0, 3, 0, 1, 0, 0, 1, 0],
                [0, 3, 0, 1, 0, 0, 0, 1],
                [0, 3, 0, 0, 2, 0, 0, 0],
                [0, 3, 0, 0, 1, 1, 0, 0],
                [0, 3, 0, 0, 1, 0, 1, 0],
                [0, 3, 0, 0, 1, 0, 0, 1],
                [0, 3, 0, 0, 0, 2, 0, 0],
                [0, 3, 0, 0, 0, 1, 1, 0],
                [0, 3, 0, 0, 0, 1, 0, 1],
                [0, 3, 0, 0, 0, 0, 1, 1],
                [0, 3, 0, 0, 0, 0, 0, 2],
                [0, 2, 3, 0, 0, 0, 0, 0],
                [0, 2, 2, 1, 0, 0, 0, 0],
                [0, 2, 2, 0, 1, 0, 0, 0],
                [0, 2, 2, 0, 0, 1, 0, 0],
                [0, 2, 2, 0, 0, 0, 1, 0],
                [0, 2, 2, 0, 0, 0, 0, 1],
                [0, 2, 1, 2, 0, 0, 0, 0],
                [0, 2, 1, 1, 1, 0, 0, 0],
                [0, 2, 1, 1, 0, 1, 0, 0],
                [0, 2, 1, 1, 0, 0, 1, 0],
                [0, 2, 1, 1, 0, 0, 0, 1],
                [0, 2, 1, 0, 2, 0, 0, 0],
                [0, 2, 1, 0, 1, 1, 0, 0],
                [0, 2, 1, 0, 1, 0, 1, 0],
                [0, 2, 1, 0, 1, 0, 0, 1],
                [0, 2, 1, 0, 0, 2, 0, 0],
                [0, 2, 1, 0, 0, 1, 1, 0],
                [0, 2, 1, 0, 0, 1, 0, 1],
                [0, 2, 1, 0, 0, 0, 2, 0],
                [0, 2, 1, 0, 0, 0, 1, 1],
                [0, 2, 1, 0, 0, 0, 0, 2],
                [0, 2, 0, 3, 0, 0, 0, 0],
                [0, 2, 0, 2, 1, 0, 0, 0],
                [0, 2, 0, 2, 0, 1, 0, 0],
                [0, 2, 0, 2, 0, 0, 1, 0],
                [0, 2, 0, 2, 0, 0, 0, 1],
                [0, 2, 0, 1, 2, 0, 0, 0],
                [0, 2, 0, 1, 1, 1, 0, 0],
                [0, 2, 0, 1, 1, 0, 1, 0],
                [0, 2, 0, 1, 1, 0, 0, 1],
                [0, 2, 0, 1, 0, 2, 0, 0],
                [0, 2, 0, 1, 0, 1, 1, 0],
                [0, 2, 0, 1, 0, 1, 0, 1],
                [0, 2, 0, 1, 0, 0, 1, 1],
                [0, 2, 0, 1, 0, 0, 0, 2],
                [0, 2, 0, 0, 3, 0, 0, 0],
                [0, 2, 0, 0, 2, 1, 0, 0],
                [0, 2, 0, 0, 2, 0, 1, 0],
                [0, 2, 0, 0, 2, 0, 0, 1],
                [0, 2, 0, 0, 1, 2, 0, 0],
                [0, 2, 0, 0, 1, 1, 1, 0],
                [0, 2, 0, 0, 1, 1, 0, 1],
                [0, 2, 0, 0, 1, 0, 2, 0],
                [0, 2, 0, 0, 1, 0, 1, 1],
                [0, 2, 0, 0, 1, 0, 0, 2],
                [0, 2, 0, 0, 0, 3, 0, 0],
                [0, 2, 0, 0, 0, 2, 1, 0],
                [0, 2, 0, 0, 0, 2, 0, 1],
                [0, 2, 0, 0, 0, 1, 2, 0],
                [0, 2, 0, 0, 0, 1, 1, 1],
                [0, 2, 0, 0, 0, 1, 0, 2],
                [0, 2, 0, 0, 0, 0, 3, 0],
                [0, 2, 0, 0, 0, 0, 2, 1],
                [0, 2, 0, 0, 0, 0, 1, 2],
                [0, 2, 0, 0, 0, 0, 0, 3],
                [0, 1, 4, 0, 0, 0, 0, 0],
                [0, 1, 3, 1, 0, 0, 0, 0],
                [0, 1, 3, 0, 1, 0, 0, 0],
                [0, 1, 3, 0, 0, 1, 0, 0],
                [0, 1, 3, 0, 0, 0, 1, 0],
                [0, 1, 3, 0, 0, 0, 0, 1],
                [0, 1, 2, 2, 0, 0, 0, 0],
                [0, 1, 2, 1, 1, 0, 0, 0],
                [0, 1, 2, 1, 0, 1, 0, 0],
                [0, 1, 2, 1, 0, 0, 1, 0],
                [0, 1, 2, 1, 0, 0, 0, 1],
                [0, 1, 2, 0, 2, 0, 0, 0],
                [0, 1, 2, 0, 1, 1, 0, 0],
                [0, 1, 2, 0, 1, 0, 1, 0],
                [0, 1, 2, 0, 1, 0, 0, 1],
                [0, 1, 2, 0, 0, 2, 0, 0],
                [0, 1, 2, 0, 0, 1, 1, 0],
                [0, 1, 2, 0, 0, 1, 0, 1],
                [0, 1, 2, 0, 0, 0, 1, 1],
                [0, 1, 2, 0, 0, 0, 0, 2],
                [0, 1, 1, 3, 0, 0, 0, 0],
                [0, 1, 1, 2, 1, 0, 0, 0],
                [0, 1, 1, 2, 0, 1, 0, 0],
                [0, 1, 1, 2, 0, 0, 1, 0],
                [0, 1, 1, 2, 0, 0, 0, 1],
                [0, 1, 1, 1, 2, 0, 0, 0],
                [0, 1, 1, 1, 1, 1, 0, 0],
                [0, 1, 1, 1, 1, 0, 1, 0],
                [0, 1, 1, 1, 1, 0, 0, 1],
                [0, 1, 1, 1, 0, 2, 0, 0],
                [0, 1, 1, 1, 0, 1, 1, 0],
                [0, 1, 1, 1, 0, 1, 0, 1],
                [0, 1, 1, 1, 0, 0, 2, 0],
                [0, 1, 1, 1, 0, 0, 1, 1],
                [0, 1, 1, 1, 0, 0, 0, 2],
                [0, 1, 1, 0, 3, 0, 0, 0],
                [0, 1, 1, 0, 2, 1, 0, 0],
                [0, 1, 1, 0, 2, 0, 1, 0],
                [0, 1, 1, 0, 2, 0, 0, 1],
                [0, 1, 1, 0, 1, 2, 0, 0],
                [0, 1, 1, 0, 1, 1, 1, 0],
                [0, 1, 1, 0, 1, 1, 0, 1],
                [0, 1, 1, 0, 1, 0, 2, 0],
                [0, 1, 1, 0, 1, 0, 1, 1],
                [0, 1, 1, 0, 1, 0, 0, 2],
                [0, 1, 1, 0, 0, 3, 0, 0],
                [0, 1, 1, 0, 0, 2, 1, 0],
                [0, 1, 1, 0, 0, 2, 0, 1],
                [0, 1, 1, 0, 0, 1, 2, 0],
                [0, 1, 1, 0, 0, 1, 1, 1],
                [0, 1, 1, 0, 0, 1, 0, 2],
                [0, 1, 1, 0, 0, 0, 3, 0],
                [0, 1, 1, 0, 0, 0, 2, 1],
                [0, 1, 1, 0, 0, 0, 1, 2],
                [0, 1, 1, 0, 0, 0, 0, 3],
                [0, 1, 0, 4, 0, 0, 0, 0],
                [0, 1, 0, 3, 1, 0, 0, 0],
                [0, 1, 0, 3, 0, 1, 0, 0],
                [0, 1, 0, 3, 0, 0, 1, 0],
                [0, 1, 0, 3, 0, 0, 0, 1],
                [0, 1, 0, 2, 2, 0, 0, 0],
                [0, 1, 0, 2, 1, 1, 0, 0],
                [0, 1, 0, 2, 1, 0, 1, 0],
                [0, 1, 0, 2, 1, 0, 0, 1],
                [0, 1, 0, 2, 0, 2, 0, 0],
                [0, 1, 0, 2, 0, 1, 1, 0],
                [0, 1, 0, 2, 0, 1, 0, 1],
                [0, 1, 0, 2, 0, 0, 2, 0],
                [0, 1, 0, 2, 0, 0, 1, 1],
                [0, 1, 0, 2, 0, 0, 0, 2],
                [0, 1, 0, 1, 3, 0, 0, 0],
                [0, 1, 0, 1, 2, 1, 0, 0],
                [0, 1, 0, 1, 2, 0, 1, 0],
                [0, 1, 0, 1, 2, 0, 0, 1],
                [0, 1, 0, 1, 1, 2, 0, 0],
                [0, 1, 0, 1, 1, 1, 1, 0],
                [0, 1, 0, 1, 1, 1, 0, 1],
                [0, 1, 0, 1, 1, 0, 2, 0],
                [0, 1, 0, 1, 1, 0, 1, 1],
                [0, 1, 0, 1, 1, 0, 0, 2],
                [0, 1, 0, 1, 0, 3, 0, 0],
                [0, 1, 0, 1, 0, 2, 1, 0],
                [0, 1, 0, 1, 0, 2, 0, 1],
                [0, 1, 0, 1, 0, 1, 2, 0],
                [0, 1, 0, 1, 0, 1, 1, 1],
                [0, 1, 0, 1, 0, 1, 0, 2],
                [0, 1, 0, 1, 0, 0, 3, 0],
                [0, 1, 0, 1, 0, 0, 2, 1],
                [0, 1, 0, 1, 0, 0, 1, 2],
                [0, 1, 0, 1, 0, 0, 0, 3],
                [0, 1, 0, 0, 4, 0, 0, 0],
                [0, 1, 0, 0, 3, 1, 0, 0],
                [0, 1, 0, 0, 3, 0, 1, 0],
                [0, 1, 0, 0, 3, 0, 0, 1],
                [0, 1, 0, 0, 2, 2, 0, 0],
                [0, 1, 0, 0, 2, 1, 1, 0],
                [0, 1, 0, 0, 2, 1, 0, 1],
                [0, 1, 0, 0, 2, 0, 2, 0],
                [0, 1, 0, 0, 2, 0, 1, 1],
                [0, 1, 0, 0, 2, 0, 0, 2],
                [0, 1, 0, 0, 1, 3, 0, 0],
                [0, 1, 0, 0, 1, 2, 1, 0],
                [0, 1, 0, 0, 1, 2, 0, 1],
                [0, 1, 0, 0, 1, 1, 2, 0],
                [0, 1, 0, 0, 1, 1, 1, 1],
                [0, 1, 0, 0, 1, 1, 0, 2],
                [0, 1, 0, 0, 1, 0, 3, 0],
                [0, 1, 0, 0, 1, 0, 2, 1],
                [0, 1, 0, 0, 1, 0, 1, 2],
                [0, 1, 0, 0, 1, 0, 0, 3],
                [0, 1, 0, 0, 0, 4, 0, 0],
                [0, 1, 0, 0, 0, 3, 1, 0],
                [0, 1, 0, 0, 0, 3, 0, 1],
                [0, 1, 0, 0, 0, 2, 2, 0],
                [0, 1, 0, 0, 0, 2, 1, 1],
                [0, 1, 0, 0, 0, 1, 3, 0],
                [0, 1, 0, 0, 0, 1, 2, 1],
                [0, 1, 0, 0, 0, 1, 1, 2],
                [0, 1, 0, 0, 0, 1, 0, 3],
                [0, 1, 0, 0, 0, 0, 4, 0],
                [0, 1, 0, 0, 0, 0, 3, 1],
                [0, 1, 0, 0, 0, 0, 2, 2],
                [0, 1, 0, 0, 0, 0, 1, 3],
                [0, 1, 0, 0, 0, 0, 0, 4],
                [0, 0, 5, 0, 0, 0, 0, 0],
                [0, 0, 4, 1, 0, 0, 0, 0],
                [0, 0, 4, 0, 1, 0, 0, 0],
                [0, 0, 4, 0, 0, 1, 0, 0],
                [0, 0, 4, 0, 0, 0, 1, 0],
                [0, 0, 4, 0, 0, 0, 0, 1],
                [0, 0, 3, 2, 0, 0, 0, 0],
                [0, 0, 3, 1, 1, 0, 0, 0],
                [0, 0, 3, 1, 0, 1, 0, 0],
                [0, 0, 3, 1, 0, 0, 1, 0],
                [0, 0, 3, 1, 0, 0, 0, 1],
                [0, 0, 3, 0, 2, 0, 0, 0],
                [0, 0, 3, 0, 1, 1, 0, 0],
                [0, 0, 3, 0, 1, 0, 1, 0],
                [0, 0, 3, 0, 1, 0, 0, 1],
                [0, 0, 3, 0, 0, 2, 0, 0],
                [0, 0, 3, 0, 0, 1, 1, 0],
                [0, 0, 3, 0, 0, 1, 0, 1],
                [0, 0, 3, 0, 0, 0, 2, 0],
                [0, 0, 3, 0, 0, 0, 1, 1],
                [0, 0, 3, 0, 0, 0, 0, 2],
                [0, 0, 2, 3, 0, 0, 0, 0],
                [0, 0, 2, 2, 1, 0, 0, 0],
                [0, 0, 2, 2, 0, 1, 0, 0],
                [0, 0, 2, 2, 0, 0, 1, 0],
                [0, 0, 2, 2, 0, 0, 0, 1],
                [0, 0, 2, 1, 2, 0, 0, 0],
                [0, 0, 2, 1, 1, 1, 0, 0],
                [0, 0, 2, 1, 1, 0, 1, 0],
                [0, 0, 2, 1, 1, 0, 0, 1],
                [0, 0, 2, 1, 0, 2, 0, 0],
                [0, 0, 2, 1, 0, 1, 1, 0],
                [0, 0, 2, 1, 0, 1, 0, 1],
                [0, 0, 2, 1, 0, 0, 2, 0],
                [0, 0, 2, 1, 0, 0, 1, 1],
                [0, 0, 2, 1, 0, 0, 0, 2],
                [0, 0, 2, 0, 3, 0, 0, 0],
                [0, 0, 2, 0, 2, 1, 0, 0],
                [0, 0, 2, 0, 2, 0, 1, 0],
                [0, 0, 2, 0, 2, 0, 0, 1],
                [0, 0, 2, 0, 1, 2, 0, 0],
                [0, 0, 2, 0, 1, 1, 1, 0],
                [0, 0, 2, 0, 1, 1, 0, 1],
                [0, 0, 2, 0, 1, 0, 2, 0],
                [0, 0, 2, 0, 1, 0, 1, 1],
                [0, 0, 2, 0, 1, 0, 0, 2],
                [0, 0, 2, 0, 0, 3, 0, 0],
                [0, 0, 2, 0, 0, 2, 1, 0],
                [0, 0, 2, 0, 0, 2, 0, 1],
                [0, 0, 2, 0, 0, 1, 2, 0],
                [0, 0, 2, 0, 0, 1, 1, 1],
                [0, 0, 2, 0, 0, 1, 0, 2],
                [0, 0, 2, 0, 0, 0, 3, 0],
                [0, 0, 2, 0, 0, 0, 2, 1],
                [0, 0, 2, 0, 0, 0, 1, 2],
                [0, 0, 2, 0, 0, 0, 0, 3],
                [0, 0, 1, 4, 0, 0, 0, 0],
                [0, 0, 1, 3, 1, 0, 0, 0],
                [0, 0, 1, 3, 0, 1, 0, 0],
                [0, 0, 1, 3, 0, 0, 1, 0],
                [0, 0, 1, 3, 0, 0, 0, 1],
                [0, 0, 1, 2, 2, 0, 0, 0],
                [0, 0, 1, 2, 1, 1, 0, 0],
                [0, 0, 1, 2, 1, 0, 1, 0],
                [0, 0, 1, 2, 1, 0, 0, 1],
                [0, 0, 1, 2, 0, 2, 0, 0],
                [0, 0, 1, 2, 0, 1, 1, 0],
                [0, 0, 1, 2, 0, 1, 0, 1],
                [0, 0, 1, 2, 0, 0, 2, 0],
                [0, 0, 1, 2, 0, 0, 1, 1],
                [0, 0, 1, 2, 0, 0, 0, 2],
                [0, 0, 1, 1, 3, 0, 0, 0],
                [0, 0, 1, 1, 2, 1, 0, 0],
                [0, 0, 1, 1, 2, 0, 1, 0],
                [0, 0, 1, 1, 2, 0, 0, 1],
                [0, 0, 1, 1, 1, 2, 0, 0],
                [0, 0, 1, 1, 1, 1, 1, 0],
                [0, 0, 1, 1, 1, 1, 0, 1],
                [0, 0, 1, 1, 1, 0, 2, 0],
                [0, 0, 1, 1, 1, 0, 1, 1],
                [0, 0, 1, 1, 1, 0, 0, 2],
                [0, 0, 1, 1, 0, 3, 0, 0],
                [0, 0, 1, 1, 0, 2, 1, 0],
                [0, 0, 1, 1, 0, 2, 0, 1],
                [0, 0, 1, 1, 0, 1, 2, 0],
                [0, 0, 1, 1, 0, 1, 1, 1],
                [0, 0, 1, 1, 0, 1, 0, 2],
                [0, 0, 1, 1, 0, 0, 3, 0],
                [0, 0, 1, 1, 0, 0, 2, 1],
                [0, 0, 1, 1, 0, 0, 1, 2],
                [0, 0, 1, 1, 0, 0, 0, 3],
                [0, 0, 1, 0, 4, 0, 0, 0],
                [0, 0, 1, 0, 3, 1, 0, 0],
                [0, 0, 1, 0, 3, 0, 1, 0],
                [0, 0, 1, 0, 3, 0, 0, 1],
                [0, 0, 1, 0, 2, 2, 0, 0],
                [0, 0, 1, 0, 2, 1, 1, 0],
                [0, 0, 1, 0, 2, 1, 0, 1],
                [0, 0, 1, 0, 2, 0, 2, 0],
                [0, 0, 1, 0, 2, 0, 1, 1],
                [0, 0, 1, 0, 2, 0, 0, 2],
                [0, 0, 1, 0, 1, 3, 0, 0],
                [0, 0, 1, 0, 1, 2, 1, 0],
                [0, 0, 1, 0, 1, 2, 0, 1],
                [0, 0, 1, 0, 1, 1, 2, 0],
                [0, 0, 1, 0, 1, 1, 1, 1],
                [0, 0, 1, 0, 1, 1, 0, 2],
                [0, 0, 1, 0, 0, 4, 0, 0],
                [0, 0, 1, 0, 0, 3, 1, 0],
                [0, 0, 1, 0, 0, 3, 0, 1],
                [0, 0, 1, 0, 0, 2, 2, 0],
                [0, 0, 1, 0, 0, 2, 1, 1],
                [0, 0, 1, 0, 0, 2, 0, 2],
                [0, 0, 1, 0, 0, 1, 3, 0],
                [0, 0, 1, 0, 0, 1, 2, 1],
                [0, 0, 1, 0, 0, 1, 1, 2],
                [0, 0, 1, 0, 0, 1, 0, 3],
                [0, 0, 1, 0, 0, 0, 4, 0],
                [0, 0, 1, 0, 0, 0, 3, 1],
                [0, 0, 1, 0, 0, 0, 2, 2],
                [0, 0, 1, 0, 0, 0, 1, 3],
                [0, 0, 1, 0, 0, 0, 0, 4],
                [0, 0, 0, 5, 0, 0, 0, 0],
                [0, 0, 0, 4, 1, 0, 0, 0],
                [0, 0, 0, 4, 0, 1, 0, 0],
                [0, 0, 0, 4, 0, 0, 1, 0],
                [0, 0, 0, 4, 0, 0, 0, 1],
                [0, 0, 0, 3, 2, 0, 0, 0],
                [0, 0, 0, 3, 1, 1, 0, 0],
                [0, 0, 0, 3, 1, 0, 1, 0],
                [0, 0, 0, 3, 1, 0, 0, 1],
                [0, 0, 0, 3, 0, 2, 0, 0],
                [0, 0, 0, 3, 0, 1, 1, 0],
                [0, 0, 0, 3, 0, 1, 0, 1],
                [0, 0, 0, 3, 0, 0, 2, 0],
                [0, 0, 0, 3, 0, 0, 1, 1],
                [0, 0, 0, 3, 0, 0, 0, 2],
                [0, 0, 0, 2, 3, 0, 0, 0],
                [0, 0, 0, 2, 2, 1, 0, 0],
                [0, 0, 0, 2, 2, 0, 1, 0],
                [0, 0, 0, 2, 2, 0, 0, 1],
                [0, 0, 0, 2, 1, 2, 0, 0],
                [0, 0, 0, 2, 1, 1, 1, 0],
                [0, 0, 0, 2, 1, 1, 0, 1],
                [0, 0, 0, 2, 1, 0, 2, 0],
                [0, 0, 0, 2, 1, 0, 1, 1],
                [0, 0, 0, 2, 1, 0, 0, 2],
                [0, 0, 0, 2, 0, 3, 0, 0],
                [0, 0, 0, 2, 0, 2, 1, 0],
                [0, 0, 0, 2, 0, 2, 0, 1],
                [0, 0, 0, 2, 0, 1, 2, 0],
                [0, 0, 0, 2, 0, 1, 1, 1],
                [0, 0, 0, 2, 0, 1, 0, 2],
                [0, 0, 0, 2, 0, 0, 3, 0],
                [0, 0, 0, 2, 0, 0, 2, 1],
                [0, 0, 0, 2, 0, 0, 1, 2],
                [0, 0, 0, 2, 0, 0, 0, 3],
                [0, 0, 0, 1, 4, 0, 0, 0],
                [0, 0, 0, 1, 3, 1, 0, 0],
                [0, 0, 0, 1, 3, 0, 1, 0],
                [0, 0, 0, 1, 3, 0, 0, 1],
                [0, 0, 0, 1, 2, 2, 0, 0],
                [0, 0, 0, 1, 2, 1, 1, 0],
                [0, 0, 0, 1, 2, 1, 0, 1],
                [0, 0, 0, 1, 2, 0, 2, 0],
                [0, 0, 0, 1, 2, 0, 1, 1],
                [0, 0, 0, 1, 2, 0, 0, 2],
                [0, 0, 0, 1, 1, 3, 0, 0],
                [0, 0, 0, 1, 1, 2, 1, 0],
                [0, 0, 0, 1, 1, 2, 0, 1],
                [0, 0, 0, 1, 1, 1, 2, 0],
                [0, 0, 0, 1, 1, 1, 1, 1],
                [0, 0, 0, 1, 1, 1, 0, 2],
                [0, 0, 0, 1, 0, 4, 0, 0],
                [0, 0, 0, 1, 0, 3, 1, 0],
                [0, 0, 0, 1, 0, 3, 0, 1],
                [0, 0, 0, 1, 0, 2, 2, 0],
                [0, 0, 0, 1, 0, 2, 1, 1],
                [0, 0, 0, 1, 0, 2, 0, 2],
                [0, 0, 0, 1, 0, 1, 3, 0],
                [0, 0, 0, 1, 0, 1, 2, 1],
                [0, 0, 0, 1, 0, 1, 1, 2],
                [0, 0, 0, 1, 0, 0, 4, 0],
                [0, 0, 0, 1, 0, 0, 3, 1],
                [0, 0, 0, 1, 0, 0, 2, 2],
                [0, 0, 0, 1, 0, 0, 1, 3],
                [0, 0, 0, 1, 0, 0, 0, 4],
                [0, 0, 0, 0, 5, 0, 0, 0],
                [0, 0, 0, 0, 4, 1, 0, 0],
                [0, 0, 0, 0, 4, 0, 1, 0],
                [0, 0, 0, 0, 4, 0, 0, 1],
                [0, 0, 0, 0, 3, 2, 0, 0],
                [0, 0, 0, 0, 3, 1, 1, 0],
                [0, 0, 0, 0, 3, 1, 0, 1],
                [0, 0, 0, 0, 3, 0, 2, 0],
                [0, 0, 0, 0, 3, 0, 1, 1],
                [0, 0, 0, 0, 3, 0, 0, 2],
                [0, 0, 0, 0, 2, 3, 0, 0],
                [0, 0, 0, 0, 2, 2, 1, 0],
                [0, 0, 0, 0, 2, 2, 0, 1],
                [0, 0, 0, 0, 2, 1, 2, 0],
                [0, 0, 0, 0, 2, 1, 1, 1],
                [0, 0, 0, 0, 2, 0, 3, 0],
                [0, 0, 0, 0, 2, 0, 2, 1],
                [0, 0, 0, 0, 2, 0, 1, 2],
                [0, 0, 0, 0, 2, 0, 0, 3],
                [0, 0, 0, 0, 1, 4, 0, 0],
                [0, 0, 0, 0, 1, 3, 1, 0],
                [0, 0, 0, 0, 1, 3, 0, 1],
                [0, 0, 0, 0, 1, 2, 2, 0],
                [0, 0, 0, 0, 1, 2, 1, 1],
                [0, 0, 0, 0, 1, 2, 0, 2],
                [0, 0, 0, 0, 1, 1, 3, 0],
                [0, 0, 0, 0, 1, 1, 2, 1],
                [0, 0, 0, 0, 1, 1, 1, 2],
                [0, 0, 0, 0, 1, 1, 0, 3],
                [0, 0, 0, 0, 1, 0, 4, 0],
                [0, 0, 0, 0, 1, 0, 3, 1],
                [0, 0, 0, 0, 1, 0, 2, 2],
                [0, 0, 0, 0, 1, 0, 1, 3],
                [0, 0, 0, 0, 1, 0, 0, 4],
                [0, 0, 0, 0, 0, 5, 0, 0],
                [0, 0, 0, 0, 0, 4, 1, 0],
                [0, 0, 0, 0, 0, 4, 0, 1],
                [0, 0, 0, 0, 0, 3, 2, 0],
                [0, 0, 0, 0, 0, 3, 1, 1],
                [0, 0, 0, 0, 0, 3, 0, 2],
                [0, 0, 0, 0, 0, 2, 3, 0],
                [0, 0, 0, 0, 0, 2, 2, 1],
                [0, 0, 0, 0, 0, 2, 1, 2],
                [0, 0, 0, 0, 0, 2, 0, 3],
                [0, 0, 0, 0, 0, 1, 4, 0],
                [0, 0, 0, 0, 0, 1, 3, 1],
                [0, 0, 0, 0, 0, 1, 2, 2],
                [0, 0, 0, 0, 0, 1, 1, 3],
                [0, 0, 0, 0, 0, 1, 0, 4],
                [0, 0, 0, 0, 0, 0, 5, 0],
                [0, 0, 0, 0, 0, 0, 4, 1],
                [0, 0, 0, 0, 0, 0, 3, 2],
                [0, 0, 0, 0, 0, 0, 2, 3],
                [0, 0, 0, 0, 0, 0, 1, 4],
                [0, 0, 0, 0, 0, 0, 0, 5],
            ];
        }
        return $combies[\Turn::WEEKDAY];
    }

    /**
     * 皆の最後までの行動をランダムに選択
     * ただし、自分のものだけは次のターンは自分で選ぶため、次の次のターンからの行動を返す
     * @param \Heroines $heroines
     * @param \Turn $turn
     * @return array
     */
    public function allRemainPoints(\Heroines $heroines, \Turn $turn)
    {
        $afterMyActionTurn = new \Turn($turn->getNextTurn() + 1);
        $me = $this->getRemainPoints($heroines, $afterMyActionTurn);
        $p1 = $this->getRemainPoints($heroines, $turn);
        $p2 = $this->getRemainPoints($heroines, $turn);
        $p3 = $this->getRemainPoints($heroines, $turn);
        return [$me, $p1, $p2, $p3];
    }

    public function getRemainPoints(\Heroines $heroines, \Turn $turn)
    {
        $remainActionCounts = $this->getRemainActionCounts($turn->getRemainTurns());
        $weekDayPoints = $this->getWhichHeroine(
            $remainActionCounts[\Turn::WEEKDAY], $heroines
        );
        $holidayPoints = array_map(
            function ($value) {
                return $value * 2;
            },
            $this->getWhichHeroine(
                $remainActionCounts[\Turn::HOLIDAY], $heroines
            )
        );
        $allPoints = array_map(
            function ($a, $b) {
                return $a + $b;
            },
            $weekDayPoints,
            $holidayPoints
        );
        return $allPoints;
    }

    private function getRemainActionCounts(array $remainTurns)
    {
        $remainHolidayActionCount = $remainTurns[\Turn::HOLIDAY] * 2;
        $remainWeekdayActionCount = $remainTurns[\Turn::WEEKDAY] * 5;
        return [
            \Turn::HOLIDAY => $remainHolidayActionCount,
            \Turn::WEEKDAY => $remainWeekdayActionCount
        ];
    }

    /**
     * @param $count
     * @param \Heroines $heroines
     * @return array
     */
    public function getWhichHeroine($count, \Heroines $heroines)
    {
        $result = array_fill(0, 8, 0);
        $heroineCount = count($heroines);
        // array_randがキーを順番通りに出力する性質を使用
        $divisionPoses = array_rand(
            array_fill(0, $heroineCount + $count - 1, 0),
            $heroineCount - 1
        );
        $beforeDivisionPos = -1;
        foreach ($divisionPoses as $i => $divisionPos) {
            $result[$i] = ($divisionPos - $beforeDivisionPos) - 1;
            $beforeDivisionPos = $divisionPos;
        }
        $result[$heroineCount - 1]
            = $heroineCount + $count - 1 - $beforeDivisionPos - 1;
        return $result;
    }
}
