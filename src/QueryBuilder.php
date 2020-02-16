<?php namespace Adapters;

use Couchbase\N1qlQuery;

class QueryBuilder
{
	/** @var string */
	private $sql;
	/** @var array */
	private $where = [];

	/**
	 * @param string $bucketName
	 * @param array  $columns
	 */
	public function select(string $bucketName, array $columns)
	{
		$this->sql = 'SELECT ' . $this->generateSelectFields($bucketName, $columns) . ' FROM ' . $bucketName;
	}

	public function delete(string $bucketName)
	{
		$this->sql = 'DELETE FROM ' . $bucketName;
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
	 * @param string $group
	 */
	public function group(string $group)
	{
		if (!empty($group)) {
			$this->whereBuild();

			$this->sql .= ' GROUP BY ' . $group;
		}
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

		return N1qlQuery::fromString($sql);
	}

	/**
	 * @param string $bucketName
	 * @param array  $columns
	 *
	 * @return string
	 */
	public function generateSelectFields(string $bucketName, array $columns): string
	{
		if (empty($columns)) {
			return $this->generateSelectFieldsAll($bucketName);
		}

		return $this->generateSelectFieldsProvided($bucketName, $columns);
	}

	/**
	 * @param string $bucketName
	 * @param array  $columns
	 *
	 * @return string
	 */
	public function generateSelectFieldsProvided(string $bucketName, array $columns): string
	{
		$fields = [];

		foreach ($columns as $column) {
			$fields[] = (strpos($column, "(") !== false) ? $column : $bucketName . "." . $column;
		}

		return implode(",", $fields);
	}

	/**
	 * @param string $bucketName
	 *
	 * @return string
	 */
	public function generateSelectFieldsAll(string $bucketName): string
	{
		return $bucketName . ".*";
	}
}
