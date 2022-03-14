<?php

namespace Vinelab\NeoEloquent\DatabaseDriver\Drivers;

use Illuminate\Support\Arr;

abstract class ClientAbstract
{
    protected $config;

    /**
     * @return string
     */
    public function buildUriFromConfig(array $config): string
    {
        $uri = '';
        $scheme = $this->getScheme($config);
        if ($scheme) {
            $uri .= $scheme.'://';
        }

        $host = $this->getHost($config);
        if ($host) {
            $uri .= '@'.$host;
        }

        $port = $this->getPort($config);
        if ($port) {
            $uri .= ':'.$port;
        }

        return $uri;
    }

    /**
     * Get the connection host.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->getConfig('host');
    }

    public function getTimeout()
    {
        return $this->getConfig('default_timeout');
    }

    public function getFetchSize()
    {
        return $this->getConfig('fetch_size');
    }

    /**
     * Get the connection port.
     *
     * @return int|string
     */
    public function getPort()
    {
        return $this->getConfig('port');
    }

    /**
     * Get the connection username.
     *
     * @return int|string
     */
    public function getUsername()
    {
        return $this->getConfig('username');
    }

    /**
     * Get the connection password.
     *
     * @return int|string
     */
    public function getPassword()
    {
        return $this->getConfig('password');
    }

    /**
     * Get an option from the configuration options.
     *
     * @param string|null $option
     *
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return Arr::get($this->config, $option);
    }

    public function getScheme()
    {
        return Arr::get($this->config, 'scheme');
    }
}
