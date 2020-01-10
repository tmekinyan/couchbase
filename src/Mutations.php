<?php namespace Adapters;

use Couchbase\MutateInBuilder;

class Mutations
{
	/**
	 * @param MutateInBuilder $mutateInBuilder
	 * @param string          $mutation
	 * @param string          $path
	 * @param mixed           $data
	 *
	 * @return MutateInBuilder
	 */
	public function mutate(MutateInBuilder $mutateInBuilder, string $mutation, string $path, $data): MutateInBuilder
	{
		if ($mutation === 'remove') {
			$mutateInBuilder = $mutateInBuilder->remove($path);
		}

		if ($mutation === 'counter') {
			if ($data !== 0) {
				$mutateInBuilder = $mutateInBuilder->counter($path, $data);
			}
		}

		if ($mutation === 'prepend') {
			$mutateInBuilder = $mutateInBuilder->arrayPrepend($path, $data);
		}

		if ($mutation === 'append') {
			$mutateInBuilder = $mutateInBuilder->arrayAppend($path, $data);
		}

		if ($mutation === 'appendUnique') {
			$mutateInBuilder = $mutateInBuilder->arrayAddUnique($path, $data);
		}

		if ($mutation === 'replace') {
			$mutateInBuilder = $mutateInBuilder->replace($path, $data);
		}

		if ($mutation === 'upsert') {
			$mutateInBuilder = $mutateInBuilder->upsert($path, $data, true);
		}

		return $mutateInBuilder;
	}
}
