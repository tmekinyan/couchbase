<?php namespace Adapters;

use Exception;

class Generator
{
	/**
	 * @param bool $long
	 *
	 * @return string
	 */
	public static function cbId($long = true): string
	{
		$prefix = bin2hex(self::randomByte());

		return ($long) ? str_replace('.', '', uniqid($prefix, true)) : uniqid($prefix);
	}

	/**
	 * @param int $length
	 *
	 * @return string
	 */
	public static function randomByte(int $length = 1): string
	{
		try {
			$bytes = random_bytes($length);
		} catch (Exception $e) {
			$bytes = str_repeat('q', $length);
		}

		return $bytes;
	}
}