<?php

namespace Ukrposhta;

use Ukrposhta\Data\Storage;

class Shipment extends Api
{
	const REQUEST_URL = 'shipments';

	public function get(string $shipmentUuidOrBarcode): array
	{
		$url = $this->getUrl(function (string $url) use ($shipmentUuidOrBarcode) {
			return $url . "/{$shipmentUuidOrBarcode}";
		});

		return $this->send($url);
	}

	public function save(Storage $params, string $shipmentUUID = null): array
	{
		$url = $this->getUrl(function (string $url) use ($shipmentUUID) {
			if ($shipmentUUID !== null) {
				$url .= '/' . $shipmentUUID;
			}

			return $url;
		});

		if ($shipmentUUID === null) {
			$method = 'POST';
		} else {
			$method = 'PUT';
		}

		return $this->send($url, $params, $method);
	}

	public function delete(string $shipmentUUID)
	{
		$url = $this->getUrl(function (string $url) use ($shipmentUUID) {
			return $url . "/{$shipmentUUID}";
		});

		return $this->send($url, null, 'DELETE');
	}

	public function addParcel(Storage $parcelData, string $uuidOrBarcode)
	{
		$url = $this->getUrl(function (string $url) use ($uuidOrBarcode) {
			return $url . "/{$uuidOrBarcode}/parcels";
		});

		return $this->send($url, $parcelData, 'POST');
	}

	public function isPriceChanged($shipmentBarcode): bool
	{
		$url = $this->getUrl(function (string $url) use ($shipmentBarcode) {
			return $url . "/barcode/{$shipmentBarcode}/isPriceChangedInPostOffice";
		});

		return $this->send($url)['isPriceChangedInPostOffice'];
	}

    /**
     * @deprecated
     * @see https://dev.ukrposhta.ua/uploads/Status-tracking-API-27052021.pdf
     *
     * @param string $barcodeOrUuid
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
	public function getStatuses(string $barcodeOrUuid)
	{
		$url = $this->getUrl(function (string $url) use ($barcodeOrUuid) {
			return $url . "/{$barcodeOrUuid}/lifecycle";
		});

		return $this->send($url);
	}
	/**
	 * Створення бонусного відправлення за програмою лояльності.
	 * 
	 * @param Storage $params Параметри відправлення та посилок
	 * @param string $token Токен програми лояльності
	 * @return array
	 */
	public function createLoyaltyBonus(Storage $params, string $token): array
	{
		$url = $this->getUrl(function (string $url) use ($token) {
			return $url . "/loyalty-program/free?token=" . urlencode($token);
		});

		return $this->send($url, $params, 'POST');
	}
	
}
