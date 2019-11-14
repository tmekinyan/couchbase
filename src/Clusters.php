<?php namespace Adapters;

use CouchbaseCluster;

class Clusters
{
	/**
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 *
	 * @return CouchbaseCluster
	 */
	public function authenticate(string $host, string $user, string $pass): CouchbaseCluster
	{
		// Connect to Couchbase Server
		$cluster = new CouchbaseCluster('couchbase://' . $host);

		//Authenticate with username and password
		$cluster->authenticateAs($user, $pass);

		return $cluster;
	}
}