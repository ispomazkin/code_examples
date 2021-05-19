<?php
namespace backend\tests;

use backend\models\NomenclatureMock;
use common\helpers\Helper;
use common\models\Nomenclature;
use common\services\IdenticalService;

class IdenticalServiceTest extends \Codeception\Test\Unit
{
    use UnitTrait;
    /**
     * @var \backend\tests\UnitTester
     */
    protected $tester;

    protected $identicalService;
    
    protected function _before()
    {
        $this->identicalService = new IdenticalService();
    }

    protected function _after()
    {
        unset($this->identicalService);
    }

    /**
     * @dataProvider synonymsProvider
     * @throws \ReflectionException
     */
    public function testGetSynonymsHashes($article, $wordforms, $expectedResult)
    {
        $method = $this->getMethod('getSynonymsHashes', $this->identicalService);
        $result = $method->invokeArgs($this->identicalService, array($article, $wordforms));
        $this->assertEquals($result, $expectedResult);
    }

    /**
     * @dataProvider synonymsProvider
     * @throws \ReflectionException
     */
    public function testMergeIdenticalHashes($article, $wordforms, $expectedResult, $replaceArticle, $hash, $expectedResult2)
    {
        $nomenclature = $this->constructEmpty(NomenclatureMock::class,[],[
            'article' => $article,
            'replace_article' => $replaceArticle,
            'hash' => $hash
        ]);
        $method = $this->getMethod('mergeIdenticalHashes', $this->identicalService);
        $result = $method->invokeArgs($this->identicalService, array($nomenclature, $wordforms));
        $this->assertEquals(array_values($result), $expectedResult2);
    }


    /**
     * @return array
     */
    public function synonymsProvider() {
        return [
            [
                'article' => 'article',
                'wordforms' => ['brand1','brand2'],
                'expectedResult' => ['2526499786','262013552'],
                'replace_article' => 'replace_article',
                'hash' => '123456',
                'expectedResult2' => ['123456','2526499786','262013552', '823235138', '2820195320']
            ],
        ];
    }
}