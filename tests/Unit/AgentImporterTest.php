<?php

namespace Tests\Unit;

use App\Services\AgentImport\AgentImporter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Tests\TestCase;

class AgentImporterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--database' => config('database.default')]);

        Collection::make('agents')->title('Agents')->save();
    }

    public function test_it_creates_agents_from_crm_response(): void
    {
        Storage::fake('assets');

        Http::fake([
            'https://crm.test/user.get.json' => Http::response([
                'result' => [
                    [
                        'ID' => '1',
                        'ACTIVE' => true,
                        'NAME' => 'John',
                        'LAST_NAME' => 'Doe',
                        'WORK_POSITION' => 'Broker',
                        'EMAIL' => 'john@example.com',
                        'PERSONAL_MOBILE' => '+123456789',
                        'PERSONAL_PHOTO' => 'https://crm.test/uploads/john.jpg',
                        'UF_DEPARTMENT' => [52],
                    ],
                    [
                        'ID' => '2',
                        'ACTIVE' => false,
                        'NAME' => 'Inactive',
                        'LAST_NAME' => 'User',
                        'UF_DEPARTMENT' => [52],
                    ],
                ],
                'total' => 2,
                'next' => null,
            ], 200),
            'https://crm.test/uploads/john.jpg' => Http::response('fake-image', 200),
        ]);

        $report = (new AgentImporter())->sync('https://crm.test/user.get.json');

        $this->assertSame(2, $report['fetched']);
        $this->assertSame(1, $report['eligible']);
        $this->assertSame(1, $report['created']);
        $this->assertSame(0, $report['updated']);

        $entry = Entry::query()->where('collection', 'agents')->where('data->external_id', '1')->first();

        $this->assertNotNull($entry);
        $this->assertSame('John Doe', $entry->value('title'));
        $this->assertSame('Broker', $entry->value('position'));
        $this->assertSame('agents/john-doe.jpg', $entry->value('image'));

        $social = $entry->value('social_media');

        $this->assertCount(2, $social);
        $this->assertSame('email', $social[0]['name']);
        $this->assertSame('telephone', $social[1]['name']);
    }
}
