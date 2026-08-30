<?php

namespace Ukrposhta;

use Ukrposhta\Client\Telephone;
use Ukrposhta\Data\Storage;

class Client extends Api
{
	const REQUEST_URL = 'clients';
	const REQUEST_URL_EMAIL = 'client-emails';

	protected $telephone = null;
	protected $address = null;

	public function save(Storage $params, string $customerUUID = null): array
	{
		$url = $this->getUrl(function (string $url) use ($customerUUID) {
			if ($customerUUID !== null) {
				$url .= '/' . $customerUUID;
			}

			return $url;
		});

		if ($customerUUID === null) {
			$method = 'POST';
		} else {
			$method = 'PUT';
		}

		return $this->send($url, $params, $method);
	}

	public function get($customerId, $externalId = false): array
	{
		$url = $this->getUrl(function (string $url) use ($externalId, $customerId) {
			if ($externalId) {
				$url .= '/external-id';
			}

			return $url . "/{$customerId}";
		});

		return $this->send($url);
	}

	public function getByTelephone($telephone): array
	{
		$params = ['phoneNumber' => $telephone, 'countryISO3166' => 'UA'];
		$url = $this->getUrl(function (string $url) {
			return $url . '/phone';
		});

		return $this->send($url, new Storage($params));
	}

	public function setMainAddressId(string $customerUUID, int $addressId)
	{
		$params = [
			'addresses' => [
				'addressId' => $addressId,
				'main' => true
			]
		];
		$url = $this->getUrl(function (string $url) use ($customerUUID) {
			return $url . "/{$customerUUID}";
		});

		return $this->send($url, new Storage($params), 'PUT');
	}

	public function isTelephoneCorrect(string $phoneNumber): bool
	{
		return $this->getTelephone()->isAvailable($phoneNumber);
	}

	public function deleteTelephone(string $uuid)
	{
		return $this->getTelephone()->delete($uuid);
	}

	public function getAllTelephones(string $clientUUID): array
	{
		return $this->getTelephone()->getAll($clientUUID);
	}

	public function getAllAddresses(string $clientUUID): array
	{
		return $this->getAddress()->getClientAddresses($clientUUID);
	}

	public function deleteEmail(string $emailUUID)
	{
		$url = $this->getUrl(function (string $url) use ($emailUUID) {
			$url = str_replace(self::REQUEST_URL, self::REQUEST_URL_EMAIL, $url);
			$url .= "/{$emailUUID}";

			return $url;
		});

		return $this->send($url, null, 'DELETE');
	}

	public function getAllEmails(string $clientUUID): array
	{
		$url = $this->getUrl(function (string $url) {
			return str_replace(self::REQUEST_URL, self::REQUEST_URL_EMAIL, $url);
		});

		return $this->send($url, new Storage(['clientUuid' => $clientUUID]));
	}

	protected function getTelephone(): Telephone
	{
		if ($this->telephone === null) {
			$this->telephone = new Telephone($this->configuration);
		}

		return $this->telephone;
	}

	protected function getAddress(): Address
	{
		if ($this->address === null) {
			$this->address = new Address($this->configuration);
		}

		return $this->address;
	}

/**
 * Получение количества доступных бонусных отправлений по программе лояльности
 *
 * @param string $clientUuidOrPostId UUID или PostId клиента
 * @param string $programType        Тип программы ('LOYALTY_PROGRAM' или 'PROMO_ACTION')
 * @return array
 */
public function getFreeShipments(string $clientUuidOrPostId, string $programType = 'LOYALTY_PROGRAM'): array
{
    // 1. Формируем URL
    $url = $this->getUrl(function (string $baseUrl) use ($clientUuidOrPostId, $programType) {
        return "{$baseUrl}/{$clientUuidOrPostId}/free-shipments/{$programType}";
    });

    // 2. Токен из конфигурации
    $token = $this->configuration->getToken();

    // 3. Передаем query-параметр token через Storage
    $params = new Storage([
        'token' => $token
    ]);

    // 4. Отправляем GET-запрос
    $response = $this->send($url, $params, 'GET');

    // Структура по умолчанию
    $result = [
        'EXPRESS' => [
            'limit' => 0,
            'used'  => 0,
            'free'  => 0,
            'fromDate' => null,
            'toDate'   => null,
        ],
        'STANDARD' => [
            'limit' => 0,
            'used'  => 0,
            'free'  => 0,
            'fromDate' => null,
            'toDate'   => null,
        ]
    ];

    if (is_array($response)) {
        foreach ($response as $item) {
            $type = strtoupper($item['type'] ?? '');
            
            if (isset($result[$type])) {
                $limit = (int)($item['limit'] ?? 0);
                $used  = is_array($item['usedShipments'] ?? null) ? count($item['usedShipments']) : 0;
                $free  = max(0, $limit - $used);

                $result[$type] = [
                    'limit'    => $limit,
                    'used'     => $used,
                    'free'     => $free,
                    'fromDate' => $item['fromDate'] ?? null,
                    'toDate'   => $item['toDate'] ?? null,
                ];
            }
        }
    }

    return $result;
}




	
}
