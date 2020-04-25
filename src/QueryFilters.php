<?php namespace Adapters;

class QueryFilters
{
	/** @var array */
	const OPERATORS = [
		'like' => ' LIKE ',
		'in'   => ' IN ',
		'eq'   => '=',
		'ne'   => '!=',
		'gt'   => '>',
		'ge'   => '>=',
		'lt'   => '<',
		'le'   => '<=',
	];

	/**
	 * @param string $filter
	 *
	 * @return string
	 */
	public function getName(string $filter): string
	{
		$items = explode('.', $filter);

		return (string) str_replace('.' . end($items), '', $filter);
	}

	/**
	 * @param string $filter
	 *
	 * @return string
	 */
	public function getOperator(string $filter): string
	{
		$items = explode('.', $filter);

		return self::OPERATORS[end($items)];
	}

	/**
	 * @param string $key
	 * @param string $operator
	 * @param string $value
	 * @param array  $options
	 *
	 * @return string
	 */
	public function make(string $key, string $operator, string $value, array $options = []): string
	{
		if (!empty($options) && !in_array($value, $options)) {
			return '';
		}

		if ($operator === ' IN ') {
			$value = $this->inValue($value);
		} else {
			if ($operator === ' LIKE ') {
				$value = $this->likeValue($value);
			}

			if ($this->shouldHaveQuote($value)) {
				$value = '"' . $value . '"';
			}
		}

		return $key . $operator . $value;
	}

	/**
	 * @param string $iterable
	 * @param string $key
	 * @param string $operator
	 * @param string $value
	 * @param array  $options
	 *
	 * @return string
	 */
	public function makeWithIterable(string $iterable, string $key, string $operator, string $value, array $options = []): string
	{
		$subQuery = $this->make('item.' . $key, $operator, $value, $options);

		return (empty($subQuery)) ? '' : 'ANY item IN ' . $iterable . ' SATISFIES ' . $subQuery . ' END';
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function likeValue(string $value): string
	{
		return '%' . $value . '%';
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function inValue(string $value): string
	{
		if ($this->shouldHaveQuote($value)) {
			return '["' . implode('","', explode(',', $value)) . '"]';
		}

		return '[' . implode(',', explode(',', (string) $value)) . ']';
	}

	/**
	 * @param string $value
	 *
	 * @return bool
	 */
	public function shouldHaveQuote(string $value): bool
	{
		$withoutCommas = str_replace(',', '', $value);

		if (!is_numeric($withoutCommas) && !in_array($withoutCommas, ['true', 'false'])) {
			return true;
		}

		return false;
	}
}
