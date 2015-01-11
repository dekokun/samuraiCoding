<?php

class AllTest extends PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function Turnがいい感じに動く()
    {
        $turn = new Turn(1, Turn::DAY_TIME);
        $this->assertEquals(1, $turn->getNextTurn());
    }

    /**
     * @test
     */
    public function getRemainTurnがいい感じに動く()
    {
        $turn = new Turn(1, Turn::DAY_TIME);
        $this->assertEquals(['W' => 5, 'H' => 5], $turn->getRemainTurns());
        $turn = new Turn(2, Turn::NIGHT);
        $this->assertEquals(['W' => 4, 'H' => 5], $turn->getRemainTurns());
        $turn = new Turn(10, Turn::NIGHT);
        $this->assertEquals(['W' => 0, 'H' => 1], $turn->getRemainTurns());
    }

    /**
     * @test
     */
    public function getWhichHeroineがそれっぽい値を返している() {
        $rule = new \Rule\MonteCarlo();
        $this->assertEquals(
            10,
            array_sum($rule->getWhichLord(10, new Lords([0,0,0,0,0,0,0,0]))
            )
        );
        $this->assertEquals(
            101,
            array_sum($rule->getWhichLord(101, new Lords([0,0,0,0,0,0,0,0]))
            )
        );
        $this->assertEquals(
            8,
            count($rule->getWhichLord(101, new Lords([0,0,0,0,0,0,0,0]))
            )
        );
        $this->assertTrue(array_key_exists(0, $rule->getWhichLord(101, new Lords([0,0,0,0,0,0,0,0]))));
        $this->assertTrue(array_key_exists(1, $rule->getWhichLord(101, new Lords([0,0,0,0,0,0,0,0]))));
        $this->assertTrue(array_key_exists(2, $rule->getWhichLord(101, new Lords([0,0,0,0,0,0,0,0]))));
        $this->assertTrue(array_key_exists(3, $rule->getWhichLord(101, new Lords([0,0,0,0,0,0,0,0]))));
        $this->assertTrue(array_key_exists(4, $rule->getWhichLord(101, new Lords([0,0,0,0,0,0,0,0]))));
        $this->assertTrue(array_key_exists(5, $rule->getWhichLord(101, new Lords([0,0,0,0,0,0,0,0]))));
        $this->assertTrue(array_key_exists(6, $rule->getWhichLord(101, new Lords([0,0,0,0,0,0,0,0]))));
        $this->assertTrue(array_key_exists(7, $rule->getWhichLord(101, new Lords([0,0,0,0,0,0,0,0]))));
    }

    /**
     * @test
     */
    public function allRemainPointsがそれっぽい動きをしている() {
        $rule = new \Rule\MonteCarlo();
        $this->assertEquals([40, 45, 45, 45], array_map(function($val) {return array_sum($val);}, $rule->allRemainPoints(new Lords([0, 0, 0, 0, 0, 0, 0, 0]), new \Turn(1))));
        $this->assertEquals([36, 40, 40, 40], array_map(function($val) {return array_sum($val);}, $rule->allRemainPoints(new Lords([0, 0, 0, 0, 0, 0, 0, 0]), new \Turn(2))));
        $this->assertEquals([0, 4, 4, 4], array_map(function($val) {return array_sum($val);}, $rule->allRemainPoints(new Lords([0, 0, 0, 0, 0, 0, 0, 0]), new \Turn(10))));
        foreach($rule->allRemainPoints(new Lords([0, 0, 0, 0, 0, 0, 0, 0]), new \Turn(10)) as $points) {
            foreach($points as $point) {
                $this->assertGreaterThanOrEqual(0, $point);
            }
        }
        $this->assertNotEquals($rule->allRemainPoints(new Lords([0, 0, 0, 0, 0, 0, 0, 0]), new \Turn(10)), $rule->allRemainPoints(new Lords([0, 0, 0, 0, 0, 0, 0, 0]), new \Turn(10)), 'こちら、ごくごく稀に落ちるので注意');
    }
}
