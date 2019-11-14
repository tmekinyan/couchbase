<?php namespace Adapters;

use Couchbase\Bucket;

class Buckets
{
	/** @var Clusters */
	private $clusters;

	/** @var Bucket */
	private $bucket;

	/**
	 * Connections constructor.
	 *
	 * @param Clusters $clusters
	 */
	public function __construct(Clusters $clusters)
	{
		$this->clusters = $clusters;
	}

	/**
	 * @param string $bucket
	 *
	 * @return bool
	 */
	public function isOpen(string $bucket): bool
	{
		return (isset($this->bucket) && $this->getName() === $bucket);
	}

	/**
	 * @param string $bucket
	 *
	 * @return bool
	 */
	public function isNotOpen(string $bucket): bool
	{
		return !$this->isOpen($bucket);
	}

	/**
	 * @param string $bucket
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 */
	public function open(string $bucket, string $host, string $user, string $pass): void
	{
		$cluster = $this->clusters->authenticate($host, $user, $pass);

		$this->bucket = $cluster->openBucket($bucket);
	}

	/**
	 * @return Bucket
	 */
	public function get(): Bucket
	{
		return $this->bucket;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		$bucket = $this->get();

		return $bucket->getName();
	}
}