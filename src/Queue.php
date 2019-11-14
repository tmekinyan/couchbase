<?php namespace Adapters;

class Queue
{
	/** @var array */
	private $queue = [];

	/**
	 * @param mixed $model
	 *
	 * @return bool
	 */
	public function add($model): bool
	{
		$this->queue[] = $model;

		return true;
	}

	/**
	 * @return array
	 */
	public function getUncommitted(): array
	{
		return $this->queue;
	}
}