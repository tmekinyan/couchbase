<?php namespace Adapters;

use Couchbase\Cluster;

class Clusters
{
	/**
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 *
	 * @return Cluster
	 */
	public function authenticate(string $host, string $user, string $pass): Cluster
	{
		// Connect to Couchbase Server
		$cluster = new Cluster('couchbase://' . $host);

		//Authenticate with username and password
		$cluster->authenticateAs($user, $pass);

		return $cluster;
	}
}
