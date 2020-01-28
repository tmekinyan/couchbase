<?php namespace Adapters;

use stdClass;

class Couchbase
{
	/** @var Buckets */
	private $buckets;
	/** @var QueryBuilder */
	private $queryBuilder;
	/** @var QueryFilters */
	private $queryFilters;
	/** @var Queue */
	private $queue;
	/** @var Mutations */
	private $mutations;
	/** @var Response */
	private $response;

	/** @var Models\Metrics */
	private $metrics;

	/**
	 * Couchbase constructor.
	 *
	 * @param Buckets      $buckets
	 * @param QueryBuilder $queryBuilder
	 * @param QueryFilters $queryFilters
	 * @param Mutations    $mutations
	 * @param Queue        $queue
	 * @param Response     $response
	 */
	public function __construct(Buckets $buckets, QueryBuilder $queryBuilder, QueryFilters $queryFilters, Mutations $mutations, Queue $queue, Response $response)
	{
		$this->buckets      = $buckets;
		$this->queryBuilder = $queryBuilder;
		$this->queryFilters = $queryFilters;
		$this->mutations    = $mutations;
		$this->queue        = $queue;
		$this->response     = $response;
	}

	/**
	 * @param string $bucket
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 *
	 * @return Couchbase
	 */
	public function connectTo(string $bucket, string $host, string $user, string $pass): Couchbase
	{
		if ($this->buckets->isNotOpen($bucket)) {
			$this->buckets->open($bucket, $host, $user, $pass);
		}

		return $this;
	}

	/**
	 * @param string $cbId
	 *
	 * @return bool
	 */
	public function exist(string $cbId): bool
	{
		$bucket = $this->buckets->get();

		$response = $bucket->lookupIn($cbId)
						   ->exists('cbId')
						   ->execute();

		return ($response->value[0]['code'] === COUCHBASE_SUBDOC_PATH_ENOENT);
	}

	/**
	 * @param string $cbId
	 * @param array  $paths
	 *
	 * @return stdClass
	 */
	public function getPart(string $cbId, array $paths): stdClass
	{
		$bucket = $this->buckets->get();

		$lookupInBuilder = $bucket->lookupIn($cbId);

		foreach ($paths as $path) {
			$lookupInBuilder = $lookupInBuilder->get($path);
		}

		$response = $lookupInBuilder->execute();

		return $this->response->formatFragment($response, $paths);
	}

	/**
	 * @param array $cbId
	 * @param bool  $uniqueResultDirectReturn
	 *
	 * @return mixed
	 */
	public function get(array $cbId, bool $uniqueResultDirectReturn = true)
	{
		$bucket = $this->buckets->get();

		$response = $bucket->get($cbId); //return an array with the data in "value" key

		$formatted = $this->response->format($response); //There is always one element in $return (can be null)

		return ($uniqueResultDirectReturn && count($formatted) === 1) ? $formatted[0] : $formatted;
	}

	/**
	 * @param string $cbId
	 * @param mixed  $document
	 * @param int    $expiry
	 */
	public function set(string $cbId, $document, int $expiry = 0): void
	{
		$bucket = $this->buckets->get();

		if ($expiry > 0 && $expiry < time()) {
			$expiry += time();
		}

		$bucket->upsert([$cbId], $document, ['expiry' => $expiry]);
	}

	/**
	 * @param string $cbId
	 * @param array  $mutation
	 */
	public function setPart(string $cbId, array $mutation): void
	{
		$bucket = $this->buckets->get();

		$mutateInBuilder = $bucket->mutateIn($cbId, '');

		foreach ($mutation as $operation => $aux) {
			foreach ($aux as $path => $data) {
				$mutateInBuilder = $this->mutations->mutate($mutateInBuilder, $operation, $path, $data);
			}
		}

		$mutateInBuilder->execute();
	}

	/**
	 * @param mixed $model
	 *
	 * @return bool
	 */
	public function queue($model): bool
	{
		return $this->queue->add($model);
	}

	/**
	 * @return bool
	 */
	public function commitQueue(): bool
	{
		$models = $this->queue->getUncommitted();

		foreach ($models as $model) {
			$this->set($model->cbId, $model);
		}

		return true;
	}

	/**
	 * @param string $cbId
	 * @param mixed  $document
	 * @param string $cas
	 * @param int    $expiry
	 */
	public function replace(string $cbId, $document, string $cas, int $expiry = 0): void
	{
		$bucket = $this->buckets->get();

		$bucket->replace([$cbId], $document, ['cas' => $cas, 'expiry' => $expiry]);
	}

	/**
	 * @param string $cbId
	 */
	public function remove(string $cbId): void
	{
		$bucket = $this->buckets->get();

		$bucket->remove([$cbId]);
	}

	/**
	 * @param string $cbId
	 * @param int    $amount
	 * @param int    $initial
	 *
	 * @return int
	 */
	public function counter(string $cbId, int $amount, int $initial = 0): int
	{
		$bucket = $this->buckets->get();

		$response = $bucket->counter($cbId, $amount, ['initial' => $initial]);

		return (int) $this->response->formatDocument($response);
	}

	/**
	 * @param array $columns
	 *
	 * @return Couchbase
	 */
	public function select(array $columns = []): Couchbase
	{
		$bucketName = $this->buckets->getName();

		$this->queryBuilder->select($bucketName, $columns);

		return $this;
	}

	/**
	 * @return Couchbase
	 */
	public function delete(): Couchbase
	{
		$bucketName = $this->buckets->getName();

		$this->queryBuilder->delete($bucketName);

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return Couchbase
	 */
	public function withIndex(string $name): Couchbase
	{
		$this->queryBuilder->withIndex($name);

		return $this;
	}

	/**
	 * @param string $doctype
	 *
	 * @return Couchbase
	 */
	public function doctype(string $doctype): Couchbase
	{
		$this->queryBuilder->doctype($doctype);

		return $this;
	}

	/**
	 * @param string $query
	 *
	 * @return Couchbase
	 */
	public function where(string $query): Couchbase
	{
		$this->queryBuilder->where($query);

		return $this;
	}

	/**
	 * @param array $rawFilters
	 * @param array $options
	 *
	 * @return Couchbase
	 */
	public function filters(array $rawFilters, array $options = []): Couchbase
	{
		foreach ($rawFilters as $filter => $value) {
			$name     = $this->queryFilters->getName($filter);
			$operator = $this->queryFilters->getOperator($filter);

			$where = $this->queryFilters->make($name, $operator, $value, $options);

			$this->queryBuilder->where($where);
		}

		return $this;
	}

	/**
	 * @param string $iterable
	 * @param array  $rawFilters
	 * @param array  $options
	 *
	 * @return Couchbase
	 */
	public function filterIterable(string $iterable, array $rawFilters, array $options = []): Couchbase
	{
		foreach ($rawFilters as $filter => $value) {
			$name     = $this->queryFilters->getName($filter);
			$operator = $this->queryFilters->getOperator($filter);

			$where = $this->queryFilters->makeWithIterable($iterable, $name, $operator, $value, $options);

			$this->queryBuilder->where($where);
		}

		return $this;
	}

	/**
	 * @param string $order
	 *
	 * @return Couchbase
	 */
	public function order(string $order): Couchbase
	{
		$this->queryBuilder->order($order);

		return $this;
	}

	/**
	 * @param int      $limit
	 * @param int|null $offset
	 *
	 * @return Couchbase
	 */
	public function limit(int $limit, ?int $offset = 0): Couchbase
	{
		$this->queryBuilder->limit($limit, $offset);

		return $this;
	}

	/**
	 * @return string
	 */
	public function toSQL(): string
	{
		return $this->queryBuilder->toSQL();
	}

	/**
	 * @return mixed
	 */
	public function query()
	{
		$bucket = $this->buckets->get();

		$statement = $this->queryBuilder->query();

		return $bucket->query($statement);
	}

	/**
	 * @return array
	 */
	public function run(): array
	{
		$response = $this->query();

		$this->setLastMetrics($response);

		return $response->rows;
	}

	/**
	 * @param mixed $response
	 */
	public function setLastMetrics($response)
	{
		$metrics = new Models\Metrics();

		$metrics->count = $response->metrics["resultCount"] ?? 0;
		$metrics->total = $response->metrics["sortCount"] ?? $response->metrics["resultCount"] ?? 0;
		$metrics->time  = $response->metrics["executionTime"] ?? 0;

		$this->metrics = $metrics;
	}

	/**
	 * @return Models\Metrics
	 */
	public function getLastMetrics(): Models\Metrics
	{
		return $this->metrics;
	}
}
