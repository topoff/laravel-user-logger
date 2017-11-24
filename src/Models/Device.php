<?php

namespace Topoff\Tracker\Models;

class Device extends Base
{
	/**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
	protected $table = 'devices';
	/**
	 * Mass Fillable Filds
	 *
	 * @var array
	 */
	protected $fillable = [];
}