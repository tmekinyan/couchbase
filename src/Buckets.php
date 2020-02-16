<?php namespace Adapters;

use Couchbase\Cluster;
use Couchbase\Bucket;

class Buckets
{
	/**
	 * @param Cluster $cluster
	 * @param string  $bucketName
	 *
	 * @return Bucket
	 */
	public function open(Cluster $cluster, string $bucketName): Bucket
	{
		return $cluster->openBucket($bucketName);
	}
}
