<?php namespace Adapters;

use Adapters\Models\Metric;

class Metrics
{
	/** @var Metric */
	private $metric;

	/**
	 * @param mixed $response
	 */
	public function set($response)
	{
		$metric = new Metric();

		$metric->count = $response->metrics["resultCount"] ?? 0;
		$metric->total = $response->metrics["sortCount"] ?? $response->metrics["resultCount"] ?? 0;
		$metric->time  = $response->metrics["executionTime"] ?? 0;

		$this->metric = $metric;
	}

	/**
	 * @return Metric
	 */
	public function get(): Metric
	{
		return $this->metric;
	}
}
