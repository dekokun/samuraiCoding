<?php

namespace Rule;

abstract class MonteCarlo extends Rule
{
    const NIGHT_TRIAL_COUNT = 100;
    const DAY_TIME_TRIAL_RATE = 6;

    public function result(\Lords $lords, \Turn $turn)
    {
        $myPointChoiceCombination = $this->myPointChoiceCombination($turn);
        $topCounts = array_fill(0, count($myPointChoiceCombination), 0);
        if ($turn->nextTurnIsNight()) {
            $trialCount = static::NIGHT_TRIAL_COUNT * static::DAY_TIME_TRIAL_RATE;
        } else {
            $trialCount = static::NIGHT_TRIAL_COUNT;
        }
        record('count :: ' . $trialCount);
        for ($i = 0; $i < $trialCount; $i++) {
            // 過去の実績を足す
            $allRemainPoints = $this->allRemainPoints($lords, $turn);
            foreach($lords as $lord) {
                // 休日のデートを加味したスコアは自分(0番)のものは完全にわかっているためそれを足す
                $allRemainPoints[0][$lord->getIndex()]
                    += $lord->getRealScore();

                // 自分以外は、休日のデートはわからないため期待値を足す
                foreach([1,2,3] as $playerIndex) {
                     $allRemainPoints[$playerIndex][$lord->getIndex()]
                         += ($lord->getEstimatedNegotiationCount() * 2);
                }
                // 自分以外のわかっている値を足す
                foreach($lord->getRevealedScores() as $playerIndex => $point) {
                    if ($playerIndex === 0) {continue;}
                    $allRemainPoints[$playerIndex][$lord->getIndex()] += $point;
                }
            }
            $firstFlag = true;
            $transversePoints = $this->transverseMatrix($allRemainPoints);
            foreach ($myPointChoiceCombination as $j => $choice) {
                if ($this->isTop($lords, $transversePoints, $choice, $firstFlag)) {
                    $topCounts[$j] += 1;
                }
                $firstFlag = false;
            }
        }
        $maxTopCount = max($topCounts);
        $maxTopStrategies = array_keys($topCounts, $maxTopCount, true);
        $maxTopStrategy = $maxTopStrategies[array_rand($maxTopStrategies)];
        $result = [];

        // 結果の整形
        foreach(
            $myPointChoiceCombination[$maxTopStrategy] as $lordIndex => $count
        ) {
            if ($turn->nextTurnIsNight()) {
                $count = $count / 2;
            }
            for($i = 0; $i < $count; $i++) {
                $result[] = $lordIndex;
            }
        }
        return new \Lords($result);
    }

    /**
     * 自分の今回のターンの値が反映されていないポイントに反映する
     * @param array $points
     * @param array $choice
     * @return array
     */
    private function reflectMyChoice(array $points, array $choice) {
        $result = $points;
        foreach($choice as $lordIndex => $point) {
            $result[$lordIndex][0] += $point;
        }
        return $result;
    }

    public function isTop(\Lords $lords, array $points, array $choice, $firstFlag) {
        // $pointsは次のターンの自分の行動分の得点が含まれていないため、その代わりに自分が選んだ行動の得点を足す
        $result = array_fill(0, 4, 0);
        $points = $this->reflectMyChoice($points, $choice);
        foreach($points as $lordIndex => $lordPoints) {
            $maxPoint = max($lordPoints);
            $minPoint = min($lordPoints);
            $winPlayers = array_keys($lordPoints, $maxPoint, true);
            $loosePlayers = array_keys($lordPoints, $minPoint, true);
            $enthusiasm = $lords->getMilitaryCounts()[$lordIndex];
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
    abstract public function myPointChoiceCombination(\Turn $turn);

    /**
     * 皆の最後までの行動をランダムに選択
     * ただし、自分のものだけは次のターンは自分で選ぶため、次の次のターンからの行動を返す
     * @param \Lords $lords
     * @param \Turn $turn
     * @return array
     */
    public function allRemainPoints(\Lords $lords, \Turn $turn)
    {
        $afterMyActionTurn = new \Turn($turn->getNextTurn() + 1);
        $me = $this->getRemainPoints($lords, $afterMyActionTurn);
        $p1 = $this->getRemainPoints($lords, $turn);
        $p2 = $this->getRemainPoints($lords, $turn);
        $p3 = $this->getRemainPoints($lords, $turn);
        return [$me, $p1, $p2, $p3];
    }

    public function getRemainPoints(\Lords $lords, \Turn $turn)
    {
        $remainActionCounts = $this->getRemainActionCounts($turn->getRemainTurns());
        $weekDayPoints = $this->getWhichLord(
            $remainActionCounts[\Turn::DAY_TIME], $lords
        );
        $holidayPoints = array_map(
            function ($value) {
                return $value * 2;
            },
            $this->getWhichLord(
                $remainActionCounts[\Turn::NIGHT], $lords
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
        $remainHolidayActionCount = $remainTurns[\Turn::NIGHT] * 2;
        $remainWeekdayActionCount = $remainTurns[\Turn::DAY_TIME] * 5;
        return [
            \Turn::NIGHT => $remainHolidayActionCount,
            \Turn::DAY_TIME => $remainWeekdayActionCount
        ];
    }

    /**
     * @param $count
     * @param \Lords $lords
     * @return array
     */
    public function getWhichLord($count, \Lords $lords)
    {
        $lordCount = count($lords);
        $result = array_fill(0, $lordCount, 0);
        // array_randがキーを順番通りに出力する性質を使用
        $divisionPoses = array_rand(
            array_fill(0, $lordCount + $count - 1, 0),
            $lordCount - 1
        );
        $beforeDivisionPos = -1;
        foreach ($divisionPoses as $i => $divisionPos) {
            $result[$i] = ($divisionPos - $beforeDivisionPos) - 1;
            $beforeDivisionPos = $divisionPos;
        }
        $result[$lordCount - 1]
            = $lordCount + $count - 1 - $beforeDivisionPos - 1;
        return $result;
    }
}
