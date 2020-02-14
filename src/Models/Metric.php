<?php namespace Adapters\Models;

class Metric
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
	 * @return Metric
	 */
	public function set(int $count, int $total, int $time): Metric
	{
		$this->count = $count;
		$this->total = $total;
		$this->time  = $time;

		return $this;
	}
}
