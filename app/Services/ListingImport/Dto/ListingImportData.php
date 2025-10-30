<?php

namespace App\Services\ListingImport\Dto;

use App\Services\ListingImport\Support\Normalizer;
use App\Services\ListingImport\ValueObjects\GeoPoint;
use InvalidArgumentException;

class ListingImportData
{
    /**
     * @param  GeoPoint[]  $geoPoints
     * @param  string[]  $photoLinks
     * @param  string[]  $categories
     * @param  array<string, int|float|null>  $featureMetrics
     */
    private function __construct(
        public readonly string $title,
        public readonly ?string $status,
        public readonly ?string $description,
        public readonly ?float $price,
        public readonly ?GeoPoint $primaryLocation,
        public readonly array $geoPoints,
        public readonly array $photoLinks,
        public readonly ?string $externalId,
        public readonly ?string $externalAgentId,
        public readonly ?string $agentId,
        public readonly ?string $sourceUrl,
        public readonly ?string $propertyStatus,
        public readonly array $categories,
        public readonly ?string $developer,
        public readonly ?string $community,
        public readonly ?string $date,
        public readonly array $featureMetrics
    ) {
    }

    public static function fromArray(array $payload): self
    {
        $title = Normalizer::sanitizeString(
            $payload['ufCrm13TitleWebsite']
            ?? $payload['ufCrm13TitleEn']
            ?? $payload['title']
            ?? null
        );

        if ($title === null) {
            throw new InvalidArgumentException('Listings require a title.');
        }

        $status = Normalizer::sanitizeStatus(
            $payload['ufCrm13Status']
            ?? $payload['status']
            ?? null
        );
        $description = Normalizer::sanitizeMultiline(
            $payload['ufCrm13DescriptionWebsite']
            ?? $payload['ufCrm13DescriptionEn']
            ?? $payload['description']
            ?? null
        );
        $price = Normalizer::sanitizeFloat($payload['ufCrm13Price'] ?? $payload['price'] ?? null);

        $geoPoints = self::resolveGeoPoints($payload);
        $primaryLocation = self::resolvePrimaryLocation($payload, $geoPoints);
        $photoLinks = self::resolvePhotoLinks($payload['ufCrm13PhotoLinks'] ?? $payload['photos'] ?? []);

        $externalId = Normalizer::sanitizeString($payload['external_id'] ?? $payload['id'] ?? null);
        $sourceUrl = Normalizer::sanitizeUrl($payload['source_url'] ?? $payload['ufCrm13SourceUrl'] ?? null);

        $agentBlock = is_array($payload['agent'] ?? null) ? $payload['agent'] : [];
        $externalAgentId = Normalizer::sanitizeString($agentBlock['external_id'] ?? $payload['external_agent_id'] ?? null);
        $agentId = Normalizer::sanitizeString($agentBlock['statamic_id'] ?? $payload['agent_id'] ?? null);

        $propertyStatus = Normalizer::mapOfferingType($payload['ufCrm13OfferingType'] ?? null);

        if ($propertyStatus === null) {
            $fallbackStatus = Normalizer::sanitizeString($payload['property_status'] ?? null);
            $propertyStatus = $fallbackStatus !== null ? strtolower($fallbackStatus) : null;

            if ($propertyStatus !== null && ! in_array($propertyStatus, ['sale', 'rent'], true)) {
                $propertyStatus = null;
            }
        }

        $categories = self::resolveCategories($payload['ufCrm13PropertyType'] ?? $payload['categories'] ?? null);
        $developer = Normalizer::sanitizeString($payload['ufCrm13Developers'] ?? $payload['developer'] ?? null);
        $community = Normalizer::sanitizeString($payload['ufCrm13Community'] ?? $payload['community'] ?? null);
        $date = Normalizer::sanitizeDate($payload['updatedTime'] ?? $payload['date'] ?? null);
        $featureMetrics = self::resolveFeatureMetrics($payload);

        return new self(
            $title,
            $status,
            $description,
            $price,
            $primaryLocation,
            $geoPoints,
            $photoLinks,
            $externalId,
            $externalAgentId,
            $agentId,
            $sourceUrl,
            $propertyStatus,
            $categories,
            $developer,
            $community,
            $date,
            $featureMetrics
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toStatamicAttributes(): array
    {
        $attributes = [
            'title' => $this->title,
            'listing_status' => $this->status,
            'listing_description' => $this->description,
            'price' => $this->price,
            'photo_links' => $this->photoLinks,
            'external_id' => $this->externalId,
            'external_agent_id' => $this->externalAgentId,
            'source_url' => $this->sourceUrl,
            'property_status' => $this->propertyStatus,
            'categories' => $this->categories,
            'developer' => $this->developer,
            'community' => $this->community,
            'date' => $this->date,
        ];

        if (array_key_exists('bedrooms', $this->featureMetrics)) {
            $attributes['bedrooms_count'] = $this->featureMetrics['bedrooms'];
        }

        if (array_key_exists('bathrooms', $this->featureMetrics)) {
            $attributes['bathrooms_count'] = $this->featureMetrics['bathrooms'];
        }

        if (array_key_exists('parking', $this->featureMetrics)) {
            $attributes['parking_count'] = $this->featureMetrics['parking'];
        }

        if (array_key_exists('property_size', $this->featureMetrics)) {
            $attributes['property_size'] = Normalizer::formatFloat($this->featureMetrics['property_size']);
        }

        if ($this->agentId !== null) {
            $attributes['agent'] = $this->agentId;
        }

        if ($this->primaryLocation instanceof GeoPoint) {
            $attributes['latitude'] = Normalizer::formatFloat($this->primaryLocation->latitude());
            $attributes['longitude'] = Normalizer::formatFloat($this->primaryLocation->longitude());
        }

        if ($this->geoPoints !== []) {
            $attributes['geo_points'] = array_map(static fn (GeoPoint $point) => $point->toArray(), $this->geoPoints);
        }

        $propertyFeatures = self::buildPropertyFeatures($this->featureMetrics);

        if ($propertyFeatures !== []) {
            $attributes['property_features'] = $propertyFeatures;
        }

        return array_filter(
            $attributes,
            static fn ($value) => $value !== null && $value !== []
        );
    }

    /**
     * @param  GeoPoint[]  $geoPoints
     */
    private static function resolvePrimaryLocation(array $payload, array $geoPoints): ?GeoPoint
    {
        $coordinatePayload = $payload['coordinates'] ?? null;

        if (is_array($coordinatePayload)) {
            return GeoPoint::fromArray($coordinatePayload);
        }

        $lat = $payload['latitude'] ?? $payload['ufCrm13Latitude'] ?? null;
        $lng = $payload['longitude'] ?? $payload['ufCrm13Longitude'] ?? null;

        if ($lat === null && $lng === null) {
            return $geoPoints[0] ?? null;
        }

        try {
            return GeoPoint::fromScalars($lat, $lng, null);
        } catch (InvalidArgumentException) {
            return $geoPoints[0] ?? null;
        }
    }

    /**
     * @return GeoPoint[]
     */
    private static function resolveGeoPoints(array $payload): array
    {
        $geoPoints = [];

        $appendPoint = static function (mixed $latitude, mixed $longitude, ?string $label = null) use (&$geoPoints): void {
            try {
                $geoPoints[] = GeoPoint::fromScalars($latitude, $longitude, $label);
            } catch (InvalidArgumentException) {
                // Ignore invalid coordinates.
            }
        };

        $explicitPoints = $payload['geo_points'] ?? null;

        if (is_array($explicitPoints)) {
            foreach ($explicitPoints as $point) {
                if (! is_array($point)) {
                    continue;
                }

                try {
                    $geoPoints[] = GeoPoint::fromArray($point);
                } catch (InvalidArgumentException) {
                    continue;
                }
            }
        }

        $rawFeedPoints = $payload['ufCrm13GeoPoints'] ?? $payload['ufCrm13Geopoints'] ?? null;

        if (is_string($rawFeedPoints)) {
            $rawFeedPoints = preg_split('/\s*(?:\||;|\n)\s*/', $rawFeedPoints) ?: [];
        }

        if (is_array($rawFeedPoints)) {
            foreach ($rawFeedPoints as $point) {
                if (is_array($point)) {
                    $lat = $point['lat'] ?? $point['latitude'] ?? null;
                    $lng = $point['lng'] ?? $point['longitude'] ?? null;
                    $label = $point['label'] ?? null;
                    $appendPoint($lat, $lng, $label);

                    continue;
                }

                if (! is_string($point)) {
                    continue;
                }

                $parts = array_values(array_filter(array_map('trim', explode(',', $point)), static fn ($value) => $value !== ''));

                if (count($parts) < 2) {
                    continue;
                }

                $appendPoint($parts[0], $parts[1]);
            }
        }

        if ($payload['ufCrm13Latitude'] ?? $payload['ufCrm13Longitude'] ?? null) {
            $appendPoint($payload['ufCrm13Latitude'] ?? null, $payload['ufCrm13Longitude'] ?? null);
        }

        if ($payload['latitude'] ?? $payload['longitude'] ?? null) {
            $appendPoint($payload['latitude'] ?? null, $payload['longitude'] ?? null);
        }

        return $geoPoints;
    }

    /**
     * @param  mixed  $photos
     * @return string[]
     */
    private static function resolvePhotoLinks(mixed $photos): array
    {
        $links = [];

        if (is_string($photos)) {
            $photos = preg_split('/\s*[,;\n]\s*/', trim($photos)) ?: [];
        }

        if (! is_array($photos)) {
            $photos = [];
        }

        foreach ($photos as $photo) {
            $candidate = null;

            if (is_string($photo)) {
                $candidate = $photo;
            } elseif (is_array($photo)) {
                $candidate = $photo['url'] ?? $photo['href'] ?? null;
            }

            $url = Normalizer::sanitizeUrl($candidate);

            if ($url === null) {
                continue;
            }

            $links[$url] = $url;
        }

        return array_values($links);
    }

    private static function resolveCategories(mixed $rawCategory): array
    {
        $candidates = [];

        if (is_array($rawCategory)) {
            $candidates = $rawCategory;
        } elseif ($rawCategory !== null) {
            $candidates = [$rawCategory];
        }

        $slugs = [];

        foreach ($candidates as $candidate) {
            $slug = Normalizer::mapPropertyType($candidate) ?? Normalizer::sanitizeString($candidate);

            if ($slug === null) {
                continue;
            }

            $slugs[$slug] = strtolower($slug);
        }

        return array_values($slugs);
    }

    private static function resolveFeatureMetrics(array $payload): array
    {
        $metrics = [];

        foreach ([
            'bedrooms' => $payload['ufCrm13Bedroom'] ?? null,
            'bathrooms' => $payload['ufCrm13Bathroom'] ?? null,
            'parking' => $payload['ufCrm13Parking'] ?? null,
        ] as $key => $value) {
            try {
                $metrics[$key] = Normalizer::sanitizeInteger($value);
            } catch (InvalidArgumentException) {
                $metrics[$key] = null;
            }
        }

        $sizeCandidate = $payload['ufCrm13PropertySize'] ?? $payload['ufCrm13Size'] ?? $payload['size'] ?? null;

        try {
            $metrics['property_size'] = Normalizer::sanitizeFloat($sizeCandidate);
        } catch (InvalidArgumentException) {
            $metrics['property_size'] = null;
        }

        return $metrics;
    }

    /**
     * @param  array<string, int|float|null>  $metrics
     * @return array<int, array<string, mixed>>
     */
    private static function buildPropertyFeatures(array $metrics): array
    {
        $features = [];

        $iconMap = [
            'bedrooms' => 'bedrooms',
            'bathrooms' => 'bathrooms',
            'parking' => 'parking',
            'property_size' => 'size',
        ];

        $labelMap = [
            'bedrooms' => 'Bedrooms',
            'bathrooms' => 'Bathrooms',
            'parking' => 'Parking',
            'property_size' => 'Property Size',
        ];

        foreach ($metrics as $key => $value) {
            if ($value === null || ! isset($iconMap[$key])) {
                continue;
            }

            $description = is_float($value)
                ? Normalizer::formatFloat($value)
                : (string) $value;

            if ($description === null || $description === '') {
                continue;
            }

            $feature = [
                'type' => $key,
                'icon' => $iconMap[$key],
                'description' => $description,
            ];

            if (isset($labelMap[$key])) {
                $feature['title'] = $labelMap[$key];
            }

            $features[] = $feature;
        }

        return $features;
    }
}
