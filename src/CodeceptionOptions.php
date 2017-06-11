<?php

namespace Phizzl\PHPCI\Plugins\Codeception;


class CodeceptionOptions
{
    /**
     * @var array
     */
    private $options;

    /**
     * CodeceptionOptions constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param string $index
     * @param mixed $default
     * @return mixed
     */
    public function getOption($index, $default)
    {
        return isset($this->options[$index]) ? $this->options[$index] : $default;
    }
}