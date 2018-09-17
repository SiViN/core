<?php declare(strict_types = 1);

namespace Apitte\Core\Schema\Serialization;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Core\Schema\Endpoint;
use Apitte\Core\Schema\EndpointHandler;
use Apitte\Core\Schema\EndpointNegotiation;
use Apitte\Core\Schema\EndpointParameter;
use Apitte\Core\Schema\EndpointRequestMapper;
use Apitte\Core\Schema\EndpointResponseMapper;
use Apitte\Core\Schema\Schema;

final class ArrayHydrator implements IHydrator
{

	/**
	 * @param mixed[] $data
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function hydrate($data): Schema
	{
		$schema = new Schema();

		foreach ($data as $endpoint) {
			$endpoint = $this->hydrateEndpoint($endpoint);
			$schema->addEndpoint($endpoint);
		}

		return $schema;
	}

	/**
	 * @param mixed[] $data
	 */
	private function hydrateEndpoint(array $data): Endpoint
	{
		if (!isset($data['handler'])) {
			throw new InvalidStateException("Schema route 'handler' is required");
		}

		$handler = new EndpointHandler(
			$data['handler']['class'],
			$data['handler']['method']
		);
		$handler->setArguments($data['handler']['arguments']);

		$endpoint = new Endpoint($handler);
		$endpoint->setMethods($data['methods']);
		$endpoint->setMask($data['mask']);

		if (isset($data['description'])) {
			$endpoint->setDescription($data['description']);
		}

		if (isset($data['tags'])) {
			foreach ($data['tags'] as $name => $value) {
				$endpoint->addTag($name, $value);
			}
		}

		if (isset($data['id'])) {
			$endpoint->addTag(Endpoint::TAG_ID, $data['id']);
		}

		if (isset($data['attributes']['pattern'])) {
			$endpoint->setAttribute('pattern', $data['attributes']['pattern']);
		}

		if (isset($data['parameters'])) {
			foreach ($data['parameters'] as $param) {
				$parameter = new EndpointParameter(
					$param['name'],
					$param['type']
				);
				$parameter->setDescription($param['description']);
				$parameter->setIn($param['in']);
				$parameter->setRequired($param['required']);
				$parameter->setDeprecated($param['deprecated']);
				$parameter->setAllowEmpty($param['allowEmpty']);

				$endpoint->addParameter($parameter);
			}
		}

		if (isset($data['negotiations'])) {
			foreach ($data['negotiations'] as $nego) {
				$negotiation = new EndpointNegotiation($nego['suffix']);
				$negotiation->setDefault($nego['default']);
				$negotiation->setRenderer($nego['renderer']);

				$endpoint->addNegotiation($negotiation);
			}
		}

		if (isset($data['requestMapper'])) {
			$requestMapper = new EndpointRequestMapper(
				$data['requestMapper']['entity'],
				$data['requestMapper']['validation']
			);
			$endpoint->setRequestMapper($requestMapper);
		}

		if (isset($data['responseMapper'])) {
			$responseMapper = new EndpointResponseMapper(
				$data['responseMapper']['entity']
			);
			$endpoint->setResponseMapper($responseMapper);
		}

		return $endpoint;
	}

}
