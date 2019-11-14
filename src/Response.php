<?php namespace Adapters;

use Couchbase\Document;
use Couchbase\DocumentFragment;

use stdClass;

class Response
{
	/**
	 * @param array $documents
	 *
	 * @return array
	 */
	public function format(array $documents): array
	{
		$return = [];

		foreach ($documents as $document) {
			$return[] = $this->formatDocument($document);
		}

		return $return;
	}

	/**
	 * @param Document $document
	 *
	 * @return mixed|null
	 */
	public function formatDocument(Document $document)
	{
		return empty($document->error) ? $document->value : null;
	}

	/**
	 * @param DocumentFragment $documentFragment
	 * @param array            $paths
	 *
	 * @return stdClass
	 */
	public function formatFragment(DocumentFragment $documentFragment, array $paths): stdClass
	{
		$return = new stdClass();

		foreach ($documentFragment->value as $values) {
			foreach ($values as $key => $partial) {
				$pathName = $paths[$key];

				$return->$pathName = $partial->value;
			}
		}

		return $return;
	}
}