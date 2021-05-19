<?php
namespace common\tests;

use common\modules\list_crosses\interfaces\QueueManagerInterface;
use common\modules\list_crosses\managers\QueueManager;
use yii\queue\amqp_interop\Queue;
use yii\queue\JobInterface;
use Codeception\Stub;

class QueueManagerTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
        \Yii::$container->set(QueueManagerInterface::class, QueueManager::class);
    }

    protected function _after()
    {

    }

    // tests
    public function testManagerSendsQueue()
    {
        $job = Stub::makeEmpty(JobInterface::class);
        $dataProvider = Stub::make(Queue::class, [
            'push' => Stub\Expected::once()
        ]);
        $manager = \Yii::$container->get(QueueManagerInterface::class);
        $manager->setDataProvider($dataProvider);
        $manager->setJob($job)->push();
    }
}