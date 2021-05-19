<?php
namespace backend\tests;

use backend\modules\nomenclature\components\Search;
use common\helpers\Helper;

class SearchTest extends \Codeception\Test\Unit
{
    use UnitTrait;
    /**
     * @var \backend\tests\UnitTester
     */
    protected $tester;

    /**
     * @var Search
     */
    protected $search;

    
    protected function _before()
    {
        $container = \Yii::$container;
        $this->search = $container->get(Search::class);
    }

    protected function _after()
    {
        unset($this->search);
    }

    /**
     * @dataProvider  filterTypeProvider
     */
    public function testOriginalReplacements($pool,$explicity,$original, $expectedResult)
    {
        $method = $this->getMethod('filterByType', $this->search);
        $result = $method->invokeArgs($this->search, array($original, $explicity));
        $this->assertEquals(array_values($result), $expectedResult);
        return $result;
    }


    /**
     * @depends testOriginalReplacements
     * @dataProvider  filterTypeProvider
     */
    public function testCrossesHashes($pool,$explicity,$original, $resultTestOriginalReplacements, $expectedResult)
    {
        $method = $this->getMethod('filterByType', $this->search);
        $result = $method->invokeArgs($this->search, array($pool, $resultTestOriginalReplacements, $explicity));
        $this->assertEquals(array_values($result), $expectedResult);
    }

    /**
     * @depends testOriginalReplacements
     * @dataProvider  filterTypeProvider
     */
    public function testMergeAndUnique(
        $pool,
        $explicity,
        $original,
        $expectedResult1,
        $expectedResult2,
        $expectedResult3)
    {
        $method = $this->getMethod('mergeAndUnique', $this->search);
        $result = $method->invokeArgs($this->search, array($expectedResult1, $expectedResult2));
        $this->assertEquals(array_values($result), $expectedResult3);
    }



    /**
     * @return array
     */
    public function filterTypeProvider() {
        return [
            [
                'pool' => [123,456,789,654,321,333, 444],
                'explicity' => [333,444],
                'original' => [123,456,789,333],
                'expectedResult' => [123,456,789],
                'expectedResult2' => [654,321],
                'expectedResult3' => [123,456,789,654,321]
            ],
        ];
    }

}