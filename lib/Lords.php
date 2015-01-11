<?php


class Lords implements ArrayAccess, Iterator, Countable {
    /**
     * @var \Lord[]
     */
    private $lords = [];

    public function __construct(array $lords) {
        $this->lords = $lords;
    }

    /**
     * @return \Lords
     */
    public function getMaxMilitaryCountsLord() {
        return new static(
            array_filter($this->toArray(), function(\Lord $lord) {
            return $lord->getMilitaryCount() === $this->getMaxMilitaryCount();
        }));
    }

    public function allNegotiationCount() {
        $result = 0;
        foreach($this->lords as $lords) {
            $result += $lords->getNegotiationCount();
        }
        return $result;
    }

    /**
     * @return \Lords
     */
    public function getMinMilitaryCountLords() {
        return new static(
            array_filter($this->toArray(), function(\Lord $lord) {
            return $lord->getMilitaryCount() === $this->getMinMilitaryCount();
        }));
    }
    /**
     * @param int $order
     * @return \Lords
     * @throws \Exception
     */
    public function getLordsSortedByMilitaryCount($order = SORT_DESC) {
        $lords = $this->lords;
        usort($lords, function(\Lord $lord1, \Lord $lord2) {
            if ($lord1->getMilitaryCount() === $lord2->getMilitaryCount()) {
                return 0;
            }
            return
                ($lord1->getMilitaryCount() > $lord2->getMilitaryCount())
                    ? -1 : 1;
        });
        if ($order === SORT_DESC) {
            return new static($lords);
        }
        if ($order === SORT_ASC) {
            return new static(array_reverse($lords));
        }
        throw new \Exception('SORT_ASCかSORT_DESC使ってね');
    }

    /**
     * @param $index
     * @return \Lord
     */
    public function getLord($index) {
        return $this->lords[$index];
    }
    /**
     * @return int
     */
    public function getMaxMilitaryCount() {
        return max($this->getMilitaryCounts());
    }

    /**
     * @return int
     */
    public function getMinMilitaryCount() {
        return min($this->getMilitaryCounts());
    }

    /**
     * @return \Lord
     */
    public function getRandomLord() {
        $lords = $this->toArray();
        shuffle($lords);
        return end($lords);
    }
    /**
     * @return array
     */
    public function getMilitaryCounts() {
        static $cache;
        if (! $cache) {
            $cache = array_map(function(\Lord $lord) {
                    return $lord->getMilitaryCount();
                }, $this->toArray());
        }
        return $cache;
    }


    /**
     * @return \Lord[]
     */
    public function toArray() {
        return $this->lords;
    }

    public function rewind()  {
        reset($this->lords);
    }
    public function current() {
        return current($this->lords);
    }
    public function key() {
        return key($this->lords);
    }
    public function next() {
        return next($this->lords);
    }
    public function valid() {
        return ($this->current() !== false);
    }
    public function count() {
        return count($this->lords);
    }

    /**
     * オフセットが存在するかどうか
     * @param mixed $offset 調べたいオフセット
     * @return bool 成功した場合に TRUE を、失敗した場合に FALSE を返します。
     */
    public function offsetExists ($offset) {
        return array_key_exists($offset, $this->lords);
    }
    /**
     * オフセットを取得する
     * @param mixed $offset 調べたいオフセット
     * @return mixed 指定したオフセットの値
     */
    public function offsetGet ($offset) {
        return $this->lords[$offset];
    }
    /**
     * オフセットを設定する
     * @param mixed $offset 調べたいオフセット
     * @param mixed $value 設定したい値
     */
    public function offsetSet ($offset ,$value ) {
        $this->lords[$offset] = $value;
    }
    /**
     * オフセットの設定を解除する
     * @param mixed $offset 設定解除したいオフセット
     */
    public function offsetUnset ($offset ) {
        unset($this->lords[$offset]);
    }

}

