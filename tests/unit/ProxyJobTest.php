<?php
namespace common\tests;

use common\modules\list_crosses\jobs\ProxyJob;
use yii\base\ErrorException;

class ProxyJobTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testProxyPathParams()
    {
        $job = new ProxyJob([
            'params' => [
                'first' => 'first',
                'second' => ['second'],
                'third' => null
            ]
        ]);
        $context = new class
        {
            protected $tester;

            function testJob($params)
            {
                $this->tester->assertEquals($params,[
                    'first' => 'first',
                    'second' => ['second'],
                    'third' => null
                ] );
            }
            function sendJob($job, $tester)
            {
                $this->tester = $tester;
                $job->context = $this;
                $job->method = 'testJob';
                $job->execute('queue');
            }

            function sendInvalidJob($job, $tester)
            {
                $this->tester = $tester;
                $job->context = $this;
                $job->method = 'unsupportedNameJob';
                $job->execute('queue');
            }

            function sendInvalidContext($job, $tester)
            {
                $this->tester = $tester;
                $job->context = 'someInvalidContext';
                $job->method = 'unsupportedNameJob';
                $job->execute('queue');
            }
        };
        $context->sendJob($job, $this);
        $this->expectException(ErrorException::class);
        $context->sendInvalidJob($job, $this);
        $context->sendInvalidContext($job, $this);
    }
}