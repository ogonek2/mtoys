<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class NovaPoshtaService
{
    /**
     * @param  array<string, mixed>  $methodProperties
     * @return array<int, array<string, mixed>>
     */
    public function call(string $modelName, string $calledMethod, array $methodProperties = []): array
    {
        $apiKey = (string) config('services.novaposhta.api_key', '');
        if ($apiKey === '') {
            Log::warning('NOVA_POSHTA_API_KEY is empty');

            return [];
        }

        try {
            $http = Http::timeout(20)->asJson();
            if (! config('services.novaposhta.verify_ssl', true)) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post((string) config('services.novaposhta.base_url'), [
                'apiKey' => $apiKey,
                'modelName' => $modelName,
                'calledMethod' => $calledMethod,
                'methodProperties' => $methodProperties ?: new \stdClass,
            ]);

            $json = $response->json();
            if (! $response->ok() || ! data_get($json, 'success')) {
                Log::warning('Nova Poshta API error', [
                    'method' => $calledMethod,
                    'errors' => data_get($json, 'errors'),
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = data_get($json, 'data', []);

            return is_array($data) ? $data : [];
        } catch (Throwable $e) {
            Log::error('Nova Poshta request failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * @return array<int, array{Ref: string, Description: string}>
     */
    public function searchCities(string $query, int $limit = 30): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $rows = $this->call('Address', 'getCities', [
            'FindByString' => $query,
            'Limit' => $limit,
        ]);

        return collect($rows)
            ->map(function (array $city) {
                $area = trim((string) ($city['AreaDescription'] ?? ''));
                $type = trim((string) ($city['SettlementTypeDescription'] ?? ''));
                $name = (string) ($city['Description'] ?? '');
                $suffixParts = array_values(array_filter([$type, $area !== '' ? $area.' обл.' : null]));
                $label = $suffixParts !== [] ? $name.' ('.implode(', ', $suffixParts).')' : $name;

                return [
                    'Ref' => (string) ($city['Ref'] ?? ''),
                    'Description' => $label,
                ];
            })
            ->filter(fn (array $city) => $city['Ref'] !== '' && $city['Description'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{Ref: string, Description: string}>
     */
    public function warehousesForCity(string $cityRef, string $query = '', int $limit = 100): array
    {
        $cityRef = trim($cityRef);
        if ($cityRef === '') {
            return [];
        }

        $props = [
            'CityRef' => $cityRef,
            'Limit' => $limit,
        ];

        $query = trim($query);
        if ($query !== '') {
            $props['FindByString'] = $query;
        }

        $rows = $this->call('Address', 'getWarehouses', $props);

        return collect($rows)
            ->map(fn (array $wh) => [
                'Ref' => (string) ($wh['Ref'] ?? ''),
                'Description' => (string) ($wh['Description'] ?? $wh['DescriptionRu'] ?? ''),
            ])
            ->filter(fn (array $wh) => $wh['Ref'] !== '' && $wh['Description'] !== '')
            ->values()
            ->all();
    }
}
