<?php

namespace Rule;

class RuleSelector {
    protected $rules = [];

    public function __construct(array $rules) {
        $this->rules = $rules;
    }

    /**
     * @param \Lords $lords
     * @param \Turn $turn
     * @return Rule
     */
    public function choice(\Lords $lords, \Turn $turn) {
        $evaluatedValues = array_map(function(Rule $rule) use($lords, $turn) {
            return $rule->evaluate($lords, $turn);
        }, $this->rules);
        $selectedRule = $this->rules[array_search(
            max($evaluatedValues), $evaluatedValues
        )];
        logging('selected rule: ' . get_class($selectedRule));
        return $selectedRule;
    }
}
