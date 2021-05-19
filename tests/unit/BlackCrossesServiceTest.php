<?php
namespace common\tests;

use common\modules\list_crosses\dataProviders\ConnectionDataProvider;
use common\modules\list_crosses\interfaces\BlackCrossesInterface;
use common\modules\list_crosses\interfaces\ConnectionInterface;
use common\modules\list_crosses\interfaces\QueueManagerInterface;
use common\modules\list_crosses\interfaces\WordformsInterface;
use common\modules\list_crosses\managers\QueueManager;
use common\modules\list_crosses\managers\WordformsManager;
use common\modules\list_crosses\services\BlackCrossesService;
use yii\db\Connection;

class BlackCrossesServiceTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
        $connection = $this->makeEmpty(Connection::class,['createCommand'=>function(){
            return new class{
                public function execute()
                {
                    return 1;
                }
            };
        }]);
        $connectionDataProvider = $this->make(ConnectionDataProvider::class,[]);
        $connectionDataProvider->setMysqlConnection($connection);
        \Yii::$container->set(QueueManagerInterface::class, QueueManager::class);
        \Yii::$container->set(ConnectionInterface::class, function()use($connectionDataProvider){
            return $connectionDataProvider;
        });
        \Yii::$container->set(WordformsInterface::class, WordformsManager::class);
        \Yii::$container->set(BlackCrossesInterface::class, BlackCrossesService::class);
    }

    protected function _after()
    {
    }

    /**
     * @dataProvider articleProvider
     */
    public function testInsert($article, $brand, $article_destination, $brand_destintaion)
    {
        $service = \Yii::$container->get(BlackCrossesService::class);
        $this->assertEquals(1,$service->insert([
            'article' => $article,
            'brand' => $brand,
            'article_destination'=>$article_destination,
            'brand_destination' => $brand_destintaion
        ]));
    }


    public function articleProvider()
    {
        return [
            [
                'article' => 'article',
                'brand' => 'brand',
                'article_destination' => 'article_destination',
                'brand_destination' => 'brand_destination',
            ],
            [
                'article' => 'article_destination',
                'brand' => 'brand_destination',
                'article_destination' => 'article',
                'brand_destination' => 'brand',
            ],
            [
                'article' => 'article',
                'brand' => 'brand',
                'article_destination' => 'article_destination',
                'brand_destination' => 'brand_destination',
            ],
        ];
    }
}