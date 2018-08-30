<?php

namespace Topoff\LaravelUserLogger\Parsers;

use Illuminate\Http\Request;

/**
 * Class UtmSourceGoogle
 *
 * @package Topoff\LaravelUserLogger\Parsers
 */
class UtmSourceGoogle extends AbstractUtmSource
{
    /**
     * Devices translation, keys are the values from google delivered
     *
     * @var array
     */
    private $devices = ['m' => 'mobile', 't' => 'tablet', 'c' => 'desktop'];

    /**
     * Matchtypes translation, keys are the values from google delivered
     *
     * @var array
     */
    private $matchtypes = ['e' => 'exact', 'p' => 'phrase', 'b' => 'broad'];

    /**
     * Networks translation, keys are the values from google delivered
     *
     * @var array
     */
    private $networks = ['g' => 'search', 's' => 'network', 'd' => 'display'];

    /**
     * UtmSourceGoogle constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * Translates the device
     *
     * @return string
     */
    protected function getDevice(): string
    {
        if (array_key_exists($this->request->get('device'), $this->devices)) {
            return $this->devices[$this->request->get('device')];
        } else {
            return '';
        }
    }

    /**
     * Translates the matchtype
     *
     * @return string
     */
    protected function getMatchtype(): string
    {
        if (array_key_exists($this->request->get('matchtype'), $this->matchtypes)) {
            return $this->matchtypes[$this->request->get('matchtype')];
        } else {
            return '';
        }
    }

    /**
     * Translates the network
     *
     * @return string
     */
    protected function getNetwork(): string
    {
        if (array_key_exists($this->request->get('network'), $this->networks)) {
            return $this->networks[$this->request->get('network')];
        } else {
            return '';
        }
    }
}