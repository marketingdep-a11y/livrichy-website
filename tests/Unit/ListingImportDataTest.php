<?php

namespace Tests\Unit;

use App\Services\ListingImport\Dto\ListingImportData;
use App\Services\ListingImport\ValueObjects\GeoPoint;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ListingImportDataTest extends TestCase
{
    public function test_it_normalizes_listing_payload(): void
    {
        $payload = [
            'id' => 1911,
            'ufCrm13TitleWebsite' => ' 1 BEDROOM FOR SALE IN BINGHATTI ROYALE, JVC ',
            'ufCrm13Status' => 'PUBLISHED',
            'ufCrm13DescriptionWebsite' => "  Skyline views and contemporary finishes.  ",
            'ufCrm13Price' => '1 649 000',
            'ufCrm13Geopoints' => '25.187017,55.296132',
            'ufCrm13PhotoLinks' => [
                ' https://example.com/photos/a.jpg ',
                'https://example.com/photos/a.jpg',
                'invalid-url',
                ['url' => 'https://example.com/photos/b.jpg'],
            ],
            'ufCrm13OfferingType' => 'RS',
            'ufCrm13PropertyType' => 'AP',
            'ufCrm13Developers' => ' Binghatti ',
            'ufCrm13Community' => ' Dubai Marina ',
            'ufCrm13Bedroom' => '4',
            'ufCrm13Bathroom' => 5,
            'ufCrm13Parking' => '2',
            'ufCrm13PropertySize' => '4552',
            'updatedTime' => '2025-10-29T15:10:41+04:00',
            'agent' => [
                'external_id' => ' AG-55 ',
                'statamic_id' => ' agent-uuid ',
            ],
            'ufCrm13SourceUrl' => 'https://example.com/listing',
        ];

        $dto = ListingImportData::fromArray($payload);

        $this->assertSame('1 BEDROOM FOR SALE IN BINGHATTI ROYALE, JVC', $dto->title);
        $this->assertSame('published', $dto->status);
        $this->assertSame('Skyline views and contemporary finishes.', $dto->description);
        $this->assertSame(1649000.0, $dto->price);
        $this->assertInstanceOf(GeoPoint::class, $dto->primaryLocation);
        $this->assertSame(25.187017, $dto->primaryLocation?->latitude());
        $this->assertSame(55.296132, $dto->primaryLocation?->longitude());
        $this->assertCount(1, $dto->geoPoints);
        $this->assertSame(['https://example.com/photos/a.jpg', 'https://example.com/photos/b.jpg'], $dto->photoLinks);
        $this->assertSame('1911', $dto->externalId);
        $this->assertSame('AG-55', $dto->externalAgentId);
        $this->assertSame('agent-uuid', $dto->agentId);
        $this->assertSame('https://example.com/listing', $dto->sourceUrl);
        $this->assertSame('sale', $dto->propertyStatus);
        $this->assertSame(['apartment'], $dto->categories);
        $this->assertSame('Binghatti', $dto->developer);
        $this->assertSame('Dubai Marina', $dto->community);
        $this->assertSame('2025-10-29', $dto->date);

        $attributes = $dto->toStatamicAttributes();

        $this->assertSame('1 BEDROOM FOR SALE IN BINGHATTI ROYALE, JVC', $attributes['title']);
        $this->assertSame('published', $attributes['listing_status']);
        $this->assertSame('Skyline views and contemporary finishes.', $attributes['listing_description']);
        $this->assertSame(1649000.0, $attributes['price']);
        $this->assertSame('agent-uuid', $attributes['agent']);
        $this->assertSame('25.187017', $attributes['latitude']);
        $this->assertCount(2, $attributes['forms']);
        $this->assertSame('home_tour', $attributes['forms'][0]['form']);
        $this->assertSame('55.296132', $attributes['longitude']);
        $this->assertCount(1, $attributes['geo_points']);
        $this->assertSame(['https://example.com/photos/a.jpg', 'https://example.com/photos/b.jpg'], $attributes['photo_links']);
        $this->assertSame('1911', $attributes['external_id']);
        $this->assertSame('AG-55', $attributes['external_agent_id']);
        $this->assertSame('https://example.com/listing', $attributes['source_url']);
        $this->assertSame('sale', $attributes['property_status']);
        $this->assertSame(['apartment'], $attributes['categories']);
        $this->assertSame('Binghatti', $attributes['developer']);
        $this->assertSame('Dubai Marina', $attributes['community']);
        $this->assertSame('2025-10-29', $attributes['date']);
        $this->assertSame(4, $attributes['bedrooms_count']);
        $this->assertSame(5, $attributes['bathrooms_count']);
        $this->assertSame(2, $attributes['parking_count']);
        $this->assertSame('4552', $attributes['property_size']);

        $this->assertArrayHasKey('property_features', $attributes);
        $this->assertCount(4, $attributes['property_features']);
        $this->assertSame('bedrooms', $attributes['property_features'][0]['type']);
        $this->assertSame('4', $attributes['property_features'][0]['description']);
        $this->assertSame('parking', $attributes['property_features'][2]['type']);
        $this->assertSame('2', $attributes['property_features'][2]['description']);
    }

    public function test_it_requires_a_title(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ListingImportData::fromArray([
            'ufCrm13TitleWebsite' => '   ',
        ]);
    }

    public function test_it_rejects_out_of_range_coordinates(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ListingImportData::fromArray([
            'ufCrm13TitleWebsite' => 'Test listing',
            'coordinates' => [
                'lat' => 101,
                'lng' => 200,
            ],
        ]);
    }

    public function test_it_falls_back_to_alternative_title_and_description_fields(): void
    {
        $dto = ListingImportData::fromArray([
            'ufCrm13TitleWebsite' => null,
            'ufCrm13TitleEn' => '  Skyline apartment  ',
            'ufCrm13Status' => 'published',
            'ufCrm13DescriptionWebsite' => null,
            'ufCrm13DescriptionEn' => "  Freshly renovated unit. \nReady to move in. ",
            'ufCrm13Geopoints' => '25.000000,55.000000',
        ]);

        $this->assertSame('Skyline apartment', $dto->title);
        $this->assertSame("Freshly renovated unit.\nReady to move in.", $dto->description);
    }

    public function test_it_uses_alternative_size_key_for_property_features(): void
    {
        $dto = ListingImportData::fromArray([
            'ufCrm13TitleWebsite' => 'Size test listing',
            'ufCrm13Status' => 'published',
            'ufCrm13Geopoints' => '25.000000,55.000000',
            'ufCrm13Size' => '761',
        ]);

        $attributes = $dto->toStatamicAttributes();

        $this->assertArrayHasKey('property_features', $attributes);
        $feature = collect($attributes['property_features'])
            ->firstWhere('type', 'property_size');

        $this->assertNotNull($feature);
        $this->assertSame('Property Size', $feature['title']);
        $this->assertSame('761', $feature['description']);
    }
}
