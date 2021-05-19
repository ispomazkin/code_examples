<?php
namespace backend\tests;

use backend\modules\claim_crosses\providers\ClaimCrossesDataProvider;
use backend\modules\claim_crosses\providers\ClaimCrossesDataProviderInterface;
use backend\modules\claim_crosses\requests\ClaimRequest;
use backend\modules\claim_crosses\services\CrossesClaimService;
use backend\modules\nomenclature\components\Search;
use Codeception\Stub;
use common\models\User;
use yii\web\IdentityInterface;

/**
 * codecept run  unit CrossesClaimServiceTest -c backend
 *
 * @package backend\tests
 */
class CrossesClaimServiceTest extends \Codeception\Test\Unit
{
    use UnitTrait;
    /**
     * @var \backend\tests\UnitTester
     */
    protected $tester;
    /**
     * @var CrossesClaimService
     */
    protected $service;

    protected function _before()
    {
        \Yii::$container->set(ClaimCrossesDataProviderInterface::class, function (){
            return Stub::make(ClaimCrossesDataProvider::class,[
                'add' => function() {
                    return 1;
                }
            ]);
        });
        \Yii::$container->set(IdentityInterface::class, function (){
            return Stub::make(User::class,[
                'login_guid'=>'some_login_gid'
            ]);
        });
        $this->service = \Yii::$container->get(CrossesClaimService::class);
    }

    // tests
    public function testAddSuccess()
    {
        $request = new ClaimRequest([
            'hash' => 'some_hash',
            'claimed_hash' => 'claimed_hash',
            'comment' => 'some_comment'
        ]);
        $this->assertEquals(1, $this->service->add($request));
    }

    // tests
    public function testAddError()
    {
        $request = new ClaimRequest();
        $this->assertEquals(null, $this->service->add($request));
    }

    /**
     * @dataProvider searchProvider
     */
    public function testShouldAddClaimedHash($article, $brand, $hash, $assertion)
    {
        $searchDataProvider = $this->make(Search::class,[
            'article' => $article,
            'brand' => $brand,
            'hash' => $hash
        ]);
        $method = $this->getMethod('shouldAddClaimedHash', $this->service);
        $this->assertEquals($assertion, $method->invoke($this->service,$searchDataProvider));
    }

    /**
     * @return array
     */
    public function searchProvider() {
        return [
            [ 'article'=>'article', 'brand' => 'brand', 'hash' => 'hash', 'assertion' => true ],
            [ 'article'=>'article', 'brand' => 'brand', 'hash' => '', 'assertion' => false ],
            [ 'article'=>'article', 'brand' => '', 'hash' => 'hash', 'assertion' => false ],
            [ 'article'=>'', 'brand' => 'brand', 'hash' => 'hash', 'assertion' => false ],
            [ 'article'=>null, 'brand' => 'brand', 'hash' => 'hash', 'assertion' => false ],
            [ 'article'=>'article', 'brand' => null, 'hash' => 'hash', 'assertion' => false ],
            [ 'article'=>'article', 'brand' => 'brand', 'hash' => null, 'assertion' => true ],
        ];
    }
}