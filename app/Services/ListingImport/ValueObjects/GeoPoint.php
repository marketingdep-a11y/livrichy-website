<?php

namespace App\Services\ListingImport\ValueObjects;

use App\Services\ListingImport\Support\Normalizer;
use InvalidArgumentException;

class GeoPoint
{
    public function __construct(
        private readonly float $latitude,
        private readonly float $longitude,
        private readonly ?string $label = null
    ) {
        $this->guardRange();
    }

    public static function fromArray(array $payload): self
    {
        $lat = $payload['lat'] ?? $payload['latitude'] ?? null;
        $lng = $payload['lng'] ?? $payload['longitude'] ?? null;

        $latitude = Normalizer::sanitizeFloat($lat);
        $longitude = Normalizer::sanitizeFloat($lng);

        if ($latitude === null || $longitude === null) {
            throw new InvalidArgumentException('GeoPoint requires both latitude and longitude values.');
        }

        $label = isset($payload['label']) ? Normalizer::sanitizeString($payload['label']) : null;

        return new self($latitude, $longitude, $label);
    }

    public static function fromScalars(mixed $latitude, mixed $longitude, ?string $label = null): self
    {
        return self::fromArray([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'label' => $label,
        ]);
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }

    public function label(): ?string
    {
        return $this->label;
    }

    public function toArray(): array
    {
        $data = [
            'latitude' => Normalizer::formatFloat($this->latitude),
            'longitude' => Normalizer::formatFloat($this->longitude),
        ];

        if ($this->label !== null) {
            $data['label'] = $this->label;
        }

        return $data;
    }

    private function guardRange(): void
    {
        if ($this->latitude < -90.0 || $this->latitude > 90.0) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90 degrees.');
        }

        if ($this->longitude < -180.0 || $this->longitude > 180.0) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180 degrees.');
        }
    }
}
