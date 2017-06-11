<?php


namespace Phizzl\PHPCI\Plugins\Codeception;


use PHPCI\Builder;
use PHPCI\Model\Build;
use PHPCI\Store\BuildStore;

class ReportHandler
{
    /**
     * @var Build
     */
    private $build;

    /**
     * @var array
     */
    private $codeceptConfig;

    /**
     * @var BuildStore
     */
    private $buildStore;

    /**
     * ReportHandler constructor.
     * @param Build $build
     * @param BuildStore $buildStore
     * @param array $codeceptConfig
     */
    public function __construct(Build $build, BuildStore $buildStore, array $codeceptConfig)
    {
        $this->build = $build;
        $this->buildStore = $buildStore;
        $this->codeceptConfig = $codeceptConfig;
    }

    /**
     * @param array $codeceptConfig
     */
    public function run()
    {
        $reportFilepath = $this->build->getBuildPath() .
            $this->getLogPath() . DIRECTORY_SEPARATOR . 'report.json';

        $parser = new ReportParserJson($reportFilepath);
        $parsed = $parser->parse();

        $totalTests = 0;
        $totalTime = 0;
        $totalFailures = 0;

        array_walk($parsed, function($item) use(&$totalTests, &$totalTime, &$totalFailures){
            $totalTests++;
            $totalTime += $item['time'];
            if(!$item['pass']){
                $totalFailures++;
            }
        });

        $meta = array(
            'tests'     => $totalTests,
            'timetaken' => $totalTime,
            'failures'  => $totalFailures
        );

        $storedMeta = $this->getBuildMeta('codeception-meta', array('tests' => 0, 'timetaken' => .0, 'failures' => 0));
        $storedMeta['tests'] += $meta['tests'];
        $storedMeta['timetaken'] += $meta['timetaken'];
        $storedMeta['failures'] += $meta['failures'];

        $storedData = $this->getBuildMeta('codeception-data', array());
        $storedData = array_merge($storedData, $parsed);

        $storedErrors = $this->getBuildMeta('codeception-errors', 0);
        $storedErrors += $totalFailures;

        $this->build->storeMeta('codeception-meta', $storedMeta);
        $this->build->storeMeta('codeception-data', $storedData);
        $this->build->storeMeta('codeception-errors', $storedErrors);
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getLogPath()
    {
        $logpath = '';
        if(isset($this->codeceptConfig['paths']['log'])){
            $logpath = $this->codeceptConfig['paths']['log'];
        }

        if(isset($this->codeceptConfig['paths']['output'])){
            $logpath = $this->codeceptConfig['paths']['output'];
        }

        if($logpath === ''){
            throw new Exception("Log or output path not configured in codeception.yml");
        }

        return $logpath;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getBuildMeta($key, $default)
    {
        $meta = $this->buildStore->getMeta(
            $key,
            $this->build->getProjectId(),
            $this->build->getId(),
            $this->build->getBranch());

        if(is_array($meta)){
            $meta = current($meta);
        }

        return is_array($meta) && isset($meta['meta_value']) ? $meta['meta_value'] : $default;
    }
}