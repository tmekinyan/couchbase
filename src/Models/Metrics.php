<?php namespace Adapters\Models;

class Metrics
{
	/** @var int */
	public $count = 0;
	/** @var int */
	public $total = 0;
	/** @var int */
	public $time = 0;

	/**
	 * @param int $count
	 * @param int $total
	 * @param int $time
	 *
	 * @return Metrics
	 */
	public function set(int $count, int $total, int $time): Metrics
	{
		$this->count = $count;
		$this->total = $total;
		$this->time  = $time;

		return $this;
	}
}