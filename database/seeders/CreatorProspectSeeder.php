<?php

namespace Database\Seeders;

use App\Models\CreatorProspect;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreatorProspectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/creator_prospects.json');

        if (! File::exists($jsonPath)) {
            $this->command?->error("Data file not found at: {$jsonPath}");
            return;
        }

        $items = json_decode(File::get($jsonPath), true);

        if (! is_array($items)) {
            $this->command?->error("Failed to decode JSON data.");
            return;
        }

        $this->command?->info("Seeding " . count($items) . " creator prospects...");

        $now = now();
        $batch = [];

        foreach ($items as $item) {
            $batch[] = [
                'id' => (string) Str::uuid(),
                'prospect_number' => (int) $item['prospect_number'],
                'creator' => (string) $item['creator'],
                'speciality' => $item['speciality'] ?? null,
                'primary_outreach_angle' => $item['primary_outreach_angle'] ?? null,
                'contact_type' => $item['contact_type'] ?? null,
                'public_email' => $item['public_email'] ?? null,
                'email_status' => $item['email_status'] ?? null,
                'email_type' => $item['email_type'] ?? null,
                'email_source' => $item['email_source'] ?? null,
                'outreach_readiness' => $item['outreach_readiness'] ?? null,
                'contact_url' => $item['contact_url'] ?? null,
                'recommended_priority' => $item['recommended_priority'] ?? 'A',
                'email_subject' => $item['email_subject'] ?? null,
                'personalised_email' => $item['personalised_email'] ?? null,
                'status' => $item['status'] ?? 'Not contacted',
                'personalisation_note' => $item['personalisation_note'] ?? null,
                'email' => $item['email'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 100) {
                CreatorProspect::upsert(
                    $batch,
                    ['prospect_number'],
                    [
                        'creator', 'speciality', 'primary_outreach_angle', 'contact_type',
                        'public_email', 'email_status', 'email_type', 'email_source', 'outreach_readiness',
                        'contact_url', 'recommended_priority', 'email_subject', 'personalised_email',
                        'personalisation_note', 'email', 'updated_at'
                    ]
                );
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            CreatorProspect::upsert(
                $batch,
                ['prospect_number'],
                [
                    'creator', 'speciality', 'primary_outreach_angle', 'contact_type',
                    'public_email', 'email_status', 'email_type', 'email_source', 'outreach_readiness',
                    'contact_url', 'recommended_priority', 'email_subject', 'personalised_email',
                    'personalisation_note', 'email', 'updated_at'
                ]
            );
        }

        $count = CreatorProspect::count();
        $this->command?->info("Successfully seeded {$count} creator prospects.");
    }
}
