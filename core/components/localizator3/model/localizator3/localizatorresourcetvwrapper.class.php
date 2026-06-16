<?php

/**
 * Wrapper for modResource that returns pre-rendered TV values for localized TVs.
 * Prevents double application of TV output (e.g. image basePath) when outputting
 * via $_modx->resource.tvName in Fenom templates. MODX 3 only.
 *
 * @package localizator3
 */
class LocalizatorResourceTVWrapper
{
    /** @var \MODX\Revolution\modResource */
    protected $resource;
    /** @var array TV name => already rendered output value */
    protected $tvOutput = [];

    /**
     * @param \MODX\Revolution\modResource $resource
     * @param array $tvOutput [ tvName => renderedValue, ... ]
     */
    public function __construct($resource, array $tvOutput = [])
    {
        $this->resource = $resource;
        $this->tvOutput = $tvOutput;
    }

    /**
     * Return pre-rendered value for localized TVs to avoid double path (e.g. image baseUrl).
     *
     * @param string $key TV name
     * @return mixed
     */
    public function getTVValue($key)
    {
        if (isset($this->tvOutput[$key])) {
            return $this->tvOutput[$key];
        }
        return $this->resource->getTVValue($key);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->resource, $name], $arguments);
    }

    public function __get($name)
    {
        return $this->resource->$name;
    }

    public function __set($name, $value)
    {
        $this->resource->$name = $value;
    }

    public function __isset($name)
    {
        return isset($this->resource->$name);
    }

    public function __unset($name)
    {
        unset($this->resource->$name);
    }
}
