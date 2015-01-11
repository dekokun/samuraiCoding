<?php

class Turn {
    const DAY_TIME = 'D';
    const NIGHT = 'N';
    const ALL_TURN = 9;

    protected $nextTurn;
    protected $nextDayKind;

    public function __construct($turn, $dayKind = null) {
        $this->nextTurn = intval($turn);
        if ($dayKind === null) {
            $dayKind = ($turn % 2 === 0) ? static::NIGHT : static::DAY_TIME;
        }
        $this->nextDayKind = $dayKind;
    }

    public function dayIter() {
        for ($i = 0; $i < $this->nextDayCount(); $i++) {
            yield $i;
        }
    }

    /**
     * @return int
     */
    public function getNextTurn() {
        return $this->nextTurn;
    }

    /**
     * @return int
     */
    public function dayTimeCountToNow() {
        if ($this->nextTurnIsNight()) {
            return ($this->nextTurn - 2) / 2;
        }
        return ($this->nextTurn - 1) / 2;
    }

    /**
     * @return int[]
     */
    public function getRemainTurns() {
        $remainTurn = (static::ALL_TURN - $this->getNextTurn()) + 1;
        if ($remainTurn % 2 === 0) {
            return [
                static::NIGHT => $remainTurn / 2,
                static::DAY_TIME => $remainTurn / 2
            ];
        }
        return [
            static::NIGHT => ($remainTurn + 1) / 2,
            static::DAY_TIME => (($remainTurn + 1) / 2) - 1
        ];
    }
    /**
     * @return bool
     */
    public function nextTurnIsNight() {
        return ($this->nextDayKind === static::NIGHT);
    }

    /**
     * @return bool
     */
    public function nextTurnIsDayTime() {
        return (! $this->nextTurnIsNight());
    }

    /**
     * @return bool
     */
    public function previousTurnIsNight() {
        return (! $this->nextTurnIsNight());
    }

    /**
     * @return bool
     */
    public function previousTurnIsDayTime() {
        return (! $this->nextTurnIsNight());
    }

    public function nextDayCount() {
        if ($this->nextTurnIsDayTime()) {
            return 5;
        }
        return 2;
    }
}
