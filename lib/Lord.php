<?php

class Lord {
    private $enthusiasm;
    /**
     * @var int[]
     */
    private $revealedScores;
    private $realScore;
    private $negotiated;
    private $negotiationCount = 0;
    private $index;

    function __construct(
        $index,
        $enthusiasm = 0,
        array $revealedScores = [],
        $realScore = 0,
        $dated = 0
    ) {
        $this->index = $index;
        $this->enthusiasm = $enthusiasm;
        $this->revealedScores = $revealedScores;
        $this->realScore = $realScore;
        $this->negotiated = $dated;
    }

    public function getIndex() {
        return $this->index;
    }

    /**
     * @return array
     */
    private function getRevealedScoreExcludePlayer() {
        $revealedScore = $this->revealedScores;
        array_shift($revealedScore);
        return $revealedScore;
    }

    /**
     * @return int
     */
    public function getMaxRevealedScoreExcludePlayer() {
        return max($this->getRevealedScoreExcludePlayer());
    }

    /**
     * @return int
     */
    public function getMinRevealedScoreExcludePlayer() {
        return min($this->getRevealedScoreExcludePlayer());
    }
    /**
     * @return int
     */
    public function getPlayerScore() {
        return $this->revealedScores[0];
    }

    /**
     * @return int
     */
    public function getMilitaryCount() {
        return $this->enthusiasm;
    }

    public function setEnthusiasm($enthusiasm) {
        $this->enthusiasm = $enthusiasm;
    }

    public function getRevealedScores() {
        return $this->revealedScores;
    }

    public function setRevealedScores($revealedScore) {
        $this->revealedScores = $revealedScore;
    }

    public function getRealScore() {
        return $this->realScore;
    }

    public function setRealScore($realScore) {
        $this->realScore = $realScore;
    }

    public function getNegotiated() {
        return $this->negotiated;
    }

    public function resetNegotiationCount() {
        return $this->negotiationCount = 0;
    }

    public function getNegotiationCount() {
        return $this->negotiationCount;
    }
    public function setNegotiated($isNegotiated) {
        $this->negotiationCount += $isNegotiated;
        $this->negotiated = $isNegotiated;
    }

    public function __toString() {
        return strval($this->index);
    }
}
