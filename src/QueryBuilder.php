<?php namespace Adapters;

use Couchbase\N1qlQuery;

use CouchbaseN1qlQuery;

class QueryBuilder
{
	/** @var string */
	private $sql;
	/** @var array */
	private $where = [];

	/**
	 * @param string $bucket
	 * @param array  $columns
	 */
	public function select(string $bucket, array $columns)
	{
		$this->sql = 'SELECT ' . $this->generateSelectFields($bucket, $columns) . ' FROM ' . $bucket;
	}

	public function delete(string $bucket)
	{
		$this->sql = 'DELETE FROM ' . $bucket;
	}

	/**
	 * @param string $name
	 * @param string $type
	 */
	public function withIndex(string $name, string $type = "GSI")
	{
		$this->sql .= ' USE INDEX (' . $name . ' USING ' . $type . ')';
	}

	/**
	 * @param string $doctype
	 */
	public function doctype(string $doctype)
	{
		$this->where("doctype='{$doctype}'");
	}

	/**
	 * @param string $query
	 */
	public function where(string $query)
	{
		$this->where[] = $query;
	}

	public function whereBuild()
	{
		if ($this->hasWhere()) {
			$this->sql .= ' WHERE ' . implode(' AND ', $this->where);

			$this->where = [];
		}
	}

	/**
	 * @return int
	 */
	public function hasWhere(): int
	{
		return count($this->where);
	}

	/**
	 * @param string $order
	 */
	public function order(string $order)
	{
		if (!empty(trim($order))) {
			$this->whereBuild();

			$this->sql .= ' ORDER BY ' . $order;
		}
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 */
	public function limit(int $limit, int $offset = 0)
	{
		if ($limit > 0) {
			$this->whereBuild();

			$this->sql .= ' LIMIT ' . $limit . $this->offset($offset);
		}
	}

	/**
	 * @param int $offset
	 *
	 * @return string
	 */
	public function offset(int $offset): string
	{
		return ($offset > 0) ? ' OFFSET ' . $offset : '';
	}

	/**
	 * @return string
	 */
	public function toSQL(): string
	{
		$this->whereBuild();

		return $this->sql;
	}

	/**
	 * @return N1qlQuery
	 */
	public function query(): N1qlQuery
	{
		$sql = $this->toSQL();

		return CouchbaseN1qlQuery::fromString($sql);
	}

	/**
	 * @param string $bucket
	 * @param array  $columns
	 *
	 * @return string
	 */
	public function generateSelectFields(string $bucket, array $columns): string
	{
		if (empty($columns)) {
			return $this->generateSelectFieldsAll($bucket);
		}

		return $this->generateSelectFieldsProvided($bucket, $columns);
	}

	/**
	 * @param string $bucket
	 * @param array  $columns
	 *
	 * @return string
	 */
	public function generateSelectFieldsProvided(string $bucket, array $columns): string
	{
		$fields = [];

		foreach ($columns as $column) {
			$fields[] = (strpos($column, "(") !== false) ? $column : $bucket . "." . $column;
		}

		return implode(",", $fields);
	}

	/**
	 * @param string $bucket
	 *
	 * @return string
	 */
	public function generateSelectFieldsAll(string $bucket): string
	{
		return $bucket . ".*";
	}
}