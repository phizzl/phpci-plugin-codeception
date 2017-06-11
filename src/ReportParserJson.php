<?php


namespace Phizzl\PHPCI\Plugins\Codeception;


class ReportParserJson
{
    /**
     * @var string
     */
    private static $lineBreak = "<br />";

    /**
     * @var string
     */
    private static $tabulator = "&nbsp;&nbsp;&nbsp;";

    /**
     * @var string
     */
    private $filepath;

    /**
     * ReportParserJson constructor.
     * @param $filepath
     */
    public function __construct($filepath)
    {
        $this->filepath = $filepath;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function parse()
    {
        if(!$contents = file_get_contents($this->filepath)){
            throw new Exception("File could not be opened!");
        }

        if(!$parsed = json_decode($contents, true)){
            $parsed = $this->tryToParseParts($contents);
        }

        return $this->formatParsed($parsed);
    }

    /**
     * @param array $parsed
     * @return array
     */
    private function formatParsed(array $parsed)
    {
        $formatted = array();
        foreach($parsed as $item){
            if(!isset($item['status'])
                || !isset($item['test'])){
                continue;
            }

            $testClass = '';
            $testName = $item['test'];
            if(strpos($item['test'], '::') !== false){
                $testClassAndMethod = explode('::', (string)$item['test']);
                $testClass = $testClassAndMethod[0];
                $testName = $testClassAndMethod[1];
            }
            elseif(($pos = strpos($item['test'], ':')) !== false
                && strpos($item['test'], 'Cest') !== false){
                $testClass = substr($item['test'], 0, $pos);
            }

            $message = isset($item['message']) ? (string)$item['message'] : '';

            if(isset($item['trace'])
                && is_array($item['trace'])){
                $message .= static::$lineBreak . static::$lineBreak . $this->formatTrace($item['trace']);
            }

            $formatted[] = array(
                'suite' => isset($item['suite']) ? (string)$item['suite'] : '',
                'name' => $testName,
                'feature' => (string)$item['test'],
                'assertions' => isset($item['assertions']) ? (int)$item['assertions'] : '',
                'time' => isset($item['time']) ? (float)$item['time'] : '',
                'class' => $testClass,
                'file' => $testClass . '.php',
                'pass' => isset($item['status']) && $item['status'] === 'pass',
                'event' => isset($item['event']) ? (string)$item['event'] : '',
                'output' => isset($item['output']) ? (string)$item['output'] : '',
                'trace' => isset($item['trace']) ? $item['trace'] : array(),
                'message' => $message
            );
        }

        return $formatted;
    }

    /**
     * @param array $trace
     * @return string
     */
    private function formatTrace(array $trace)
    {
        $stringTrace = "";
        foreach($trace as $i => $step){
            $stringTrace .= "Trace " . ($i+1) . static::$lineBreak;
            $stringTrace .= $this->formatTraceStep($step);
            $stringTrace .=  static::$lineBreak;
        }

        return $stringTrace;
    }

    /**
     * @param array $step
     * @return string
     */
    private function formatTraceStep(array $step)
    {
        $stepTrace = "";
        if(isset($step['file'])){
            $stepTrace .= static::$tabulator . "File: {$step['file']}:{$step['line']}" . static::$lineBreak;
        }

        if(isset($step['class'])){
            $stepTrace .= static::$tabulator . "Class call: {$step['class']}{$step['type']}{$step['function']}" . static::$lineBreak;
        }
        elseif(isset($step['function'])){
            $stepTrace .= static::$tabulator . "Function call: {$step['function']}" . static::$lineBreak;
        }

        if(isset($step['args'])
            && count($step['args'])){
            $args = array();
            array_walk($step['args'], function($item) use (&$args){
                $args[] = is_object($item) ? get_class($item) : gettype($item);
            });

            $stepTrace .= static::$tabulator . "Args: " . implode(', ', $args) . static::$lineBreak;
        }

        return $stepTrace;
    }

    /**
     * @param string $contents
     * @return array
     * @throws Exception
     */
    private function tryToParseParts($contents)
    {
        $contents = '[' . str_replace('}{', '},{', $contents) . ']';

        if(!$parsed = json_decode($contents, true)){
            throw new Exception("File does not contain JSON");
        }

        return $parsed;
    }
}