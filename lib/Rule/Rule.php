<?php

namespace Rule;

abstract class Rule {
    /**
     * @param \Lords $lords
     * @param \Turn $turn
     * @return int
     */
    public function evaluate(\Lords $lords, \Turn $turn) {
        return $this->doEvaluate($lords, $turn);
    }

    abstract protected function doEvaluate(\Lords $lords, \Turn $turn);

    /**
     * @param \Lords $lords
     * @param \Turn $turn
     * @return \Lords
     */
    abstract public function result(\Lords $lords, \Turn $turn);
}
