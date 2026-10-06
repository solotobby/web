<?php

namespace Database\Seeders;

use App\Models\Creator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CreatorSeeder extends Seeder
{
    public function run(): void
    {
        $creators = [
            [
                'name' => 'Maya Lin',
                'handle' => '@mayaintokyo',
                'slug' => 'maya-in-tokyo',
                'platform' => 'Twitch',
                'email' => 'maya@tokyodreams.test',
                'bio' => 'Late night gaming and cozy chats in Tokyo. Opening our community time vault live on stream!',
                'milestone_title' => '100k Community Milestone Stream',
                'unlock_date' => Carbon::parse('2027-10-10'),
            ],
            [
                'name' => 'Marcus Thorne',
                'handle' => '@futuredispatch',
                'slug' => 'future-dispatch',
                'platform' => 'Podcast',
                'email' => 'marcus@futuredispatch.test',
                'bio' => 'Weekly discussions on technology, artificial intelligence, and what human society will look like in 2050.',
                'milestone_title' => 'Episode 500 Live Vault Reveal',
                'unlock_date' => Carbon::parse('2029-01-01'),
            ],
            [
                'name' => 'Nostalgia Vault',
                'handle' => '@nostalgiavault',
                'slug' => 'nostalgia-vault',
                'platform' => 'YouTube',
                'email' => 'curator@nostalgiavault.test',
                'bio' => 'Preserving early 2000s internet memories, retro gaming treasures, and forgotten culture.',
                'milestone_title' => '5-Year Channel Anniversary Special',
                'unlock_date' => Carbon::parse('2028-06-15'),
            ],
            [
                'name' => 'Elena & Kai',
                'handle' => '@wanderers2050',
                'slug' => 'wanderers2050',
                'platform' => 'TikTok',
                'email' => 'hello@wanderers2050.test',
                'bio' => 'Documenting our overland travels across 60+ countries and building a time capsule with our wandering community.',
                'milestone_title' => 'Continent 7 Expedition Finale',
                'unlock_date' => Carbon::parse('2028-09-01'),
            ],
            [
                'name' => 'MKBHD (Community)',
                'handle' => '@mkbhd',
                'slug' => 'mkbhd',
                'platform' => 'YouTube',
                'email' => 'mkbhd.vault@example.test',
                'bio' => '20 Million Subscribers incoming. Sealing 10-year tech predictions and studio memories with the community.',
                'milestone_title' => '20 Million Subscribers Time Capsule',
                'unlock_date' => Carbon::parse('2028-12-31'),
            ],
            [
                'name' => 'Oluwatobi Solomon',
                'handle' => '@solotob3',
                'slug' => 'solotob3',
                'platform' => 'YouTube',
                'email' => 'solotob3@gmail.com',
                'bio' => 'Building software in public, tech breakdowns, and community milestones.',
                'milestone_title' => '100k Community Milestone',
                'unlock_date' => Carbon::parse('2027-12-31'),
            ],
        ];

        foreach ($creators as $data) {
            $existing = Creator::where('slug', $data['slug'])->first();
            $creator = Creator::query()->updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['id' => $existing?->id ?? (string) Str::uuid()])
            );

            // Ensure active milestone exists
            if ($creator->milestones()->count() === 0 && ! empty($data['milestone_title'])) {
                $creator->milestones()->create([
                    'id' => (string) Str::uuid(),
                    'title' => $data['milestone_title'],
                    'goal' => '100K Community Milestone',
                    'unlock_date' => $data['unlock_date'] ?? now()->addYear(),
                    'description' => $data['bio'] ?? null,
                    'is_active' => true,
                ]);
            }

            // Ensure starter followers exist
            if ($creator->followers()->count() === 0) {
                $followers = [
                    'fan.anticipation.' . $creator->slug . '1@example.com',
                    'fan.anticipation.' . $creator->slug . '2@example.com',
                    'fan.anticipation.' . $creator->slug . '3@example.com',
                ];
                foreach ($followers as $email) {
                    \App\Models\CapsuleFollower::query()->firstOrCreate([
                        'creator_id' => $creator->id,
                        'email' => $email,
                    ]);
                }
            }
        }
    }
}
