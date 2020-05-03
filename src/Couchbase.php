<?php namespace Adapters;

use Adapters\Models\Metric;

use Couchbase\Cluster;
use Couchbase\Bucket;

class Couchbase
{
    /** @var QueryBuilder */
    private $queryBuilder;
    /** @var QueryFilters */
    private $queryFilters;
    /** @var Mutations */
    private $mutations;
    /** @var Responses */
    private $responses;
    /** @var Metrics */
    private $metrics;

    /** @var mixed[] */
    private $queue = [];

    /** @var Bucket[] */
    private $buckets = [];

    /** @var Cluster */
    private $cluster;
    /** @var Bucket */
    private $bucket;

    /**
     * Couchbase constructor.
     *
     * @param QueryBuilder $queryBuilder
     * @param QueryFilters $queryFilters
     * @param Mutations    $mutations
     * @param Responses    $responses
     * @param Metrics      $metrics
     */
    public function __construct(QueryBuilder $queryBuilder, QueryFilters $queryFilters, Mutations $mutations, Responses $responses, Metrics $metrics)
    {
        $this->queryBuilder = $queryBuilder;
        $this->queryFilters = $queryFilters;
        $this->mutations    = $mutations;
        $this->responses    = $responses;
        $this->metrics      = $metrics;
    }

    /**
     * @param string $host
     * @param string $user
     * @param string $pass
     */
    public function setCluster(string $host, string $user, string $pass): void
    {
        $clusters = new Clusters();

        $this->cluster = $clusters->authenticate($host, $user, $pass);
    }

    /**
     * @param string $bucketName
     */
    public function setBucket(string $bucketName): void
    {
        $buckets = new Buckets();

        $this->buckets[$bucketName] = $buckets->open($this->cluster, $bucketName);
    }

    /**
     * @param string $bucketName
     */
    public function getBucket(string $bucketName): void
    {
        if (!array_key_exists($bucketName, $this->buckets)) {
            $this->setBucket($bucketName);
        }

        $this->bucket = $this->buckets[$bucketName];
    }

    /**
     * @param string $cbId
     *
     * @return bool
     */
    public function exist(string $cbId): bool
    {
        $response = $this->bucket->lookupIn($cbId)
                                 ->exists('cbId')
                                 ->execute();

        return ($response->value[0]['code'] === COUCHBASE_SUBDOC_PATH_ENOENT);
    }

    /**
     * @param string $cbId
     * @param array  $paths
     *
     * @return object
     */
    public function getPart(string $cbId, array $paths)
    {
        $lookupInBuilder = $this->bucket->lookupIn($cbId);

        foreach ($paths as $path) {
            $lookupInBuilder = $lookupInBuilder->get($path);
        }

        $response = $lookupInBuilder->execute();

        return $this->responses->formatFragment($response, $paths);
    }

    /**
     * @param array $cbId
     * @param bool  $uniqueResultDirectReturn
     *
     * @return mixed
     */
    public function get(array $cbId, bool $uniqueResultDirectReturn = true)
    {
        $response = $this->bucket->get($cbId); //return an array with the data in "value" key

        $formatted = $this->responses->format($response); //There is always one element in $return (can be null)

        return ($uniqueResultDirectReturn && count($formatted) === 1) ? $formatted[0] : $formatted;
    }

    /**
     * @param string $cbId
     * @param mixed  $document
     * @param int    $expiry
     */
    public function set(string $cbId, $document, int $expiry = 0): void
    {
        if ($expiry > 0 && $expiry < time()) {
            $expiry += time();
        }

        $this->bucket->upsert([$cbId], $document, ['expiry' => $expiry]);
    }

    /**
     * @param string $cbId
     * @param array  $mutation
     */
    public function setPart(string $cbId, array $mutation): void
    {
        $mutateInBuilder = $this->bucket->mutateIn($cbId, '');

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
        $this->queue[] = $model;

        return true;
    }

    /**
     * @return bool
     */
    public function commitQueue(): bool
    {
        foreach ($this->queue as $model) {
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
        $this->bucket->replace([$cbId], $document, ['cas' => $cas, 'expiry' => $expiry]);
    }

    /**
     * @param string $cbId
     */
    public function remove(string $cbId): void
    {
        $this->bucket->remove([$cbId]);
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
        $response = $this->bucket->counter($cbId, $amount, ['initial' => $initial]);

        return (int) $this->responses->formatDocument($response);
    }

    /**
     * @param string $sql
     *
     * @return $this
     */
    public function raw(string $sql): Couchbase
    {
        $this->queryBuilder->raw($sql);

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return Couchbase
     */
    public function select(array $columns = []): Couchbase
    {
        $this->queryBuilder->select($this->bucket->getName(), $columns);

        return $this;
    }

    /**
     * @return Couchbase
     */
    public function delete(): Couchbase
    {
        $this->queryBuilder->delete($this->bucket->getName());

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

            if (strpos($name, '->') !== false) {
                [$iterable, $name] = explode('->', $name);

                $where = $this->queryFilters->makeWithIterable($iterable, $name, $operator, $value, $options);
            } else {
                $where = $this->queryFilters->make($name, $operator, $value, $options);
            }

            $this->queryBuilder->where($where);
        }

        return $this;
    }

    /**
     * @param string $group
     *
     * @return Couchbase
     */
    public function group(string $group): Couchbase
    {
        $this->queryBuilder->group($group);

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
        $statement = $this->queryBuilder->query();

        return $this->bucket->query($statement);
    }

    /**
     * @return array
     */
    public function run(): array
    {
        $response = $this->query();

        $this->metrics->set($response);

        return $response->rows;
    }

    /**
     * @return Metric
     */
    public function getLastMetrics(): Metric
    {
        return $this->metrics->get();
    }
}
