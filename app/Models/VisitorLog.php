<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'country_code',
        'country_name',
        'city',
        'device_type',
        'browser',
        'os',
        'path',
        'referer',
        'user_agent',
        'created_at',
    ];

    /**
     * Seed realistic sample visitor traffic for high-fidelity analytics.
     */
    public static function seedRealisticData(int $count = 120): void
    {
        $locations = [
            ['code' => 'US', 'name' => 'United States', 'cities' => ['New York', 'San Francisco', 'Los Angeles', 'Chicago', 'Austin', 'Seattle']],
            ['code' => 'GB', 'name' => 'United Kingdom', 'cities' => ['London', 'Manchester', 'Edinburgh', 'Birmingham', 'Bristol']],
            ['code' => 'CA', 'name' => 'Canada', 'cities' => ['Toronto', 'Vancouver', 'Montreal', 'Calgary']],
            ['code' => 'NG', 'name' => 'Nigeria', 'cities' => ['Lagos', 'Abuja', 'Port Harcourt', 'Ibadan']],
            ['code' => 'DE', 'name' => 'Germany', 'cities' => ['Berlin', 'Munich', 'Frankfurt', 'Hamburg']],
            ['code' => 'JP', 'name' => 'Japan', 'cities' => ['Tokyo', 'Osaka', 'Kyoto']],
            ['code' => 'FR', 'name' => 'France', 'cities' => ['Paris', 'Lyon', 'Marseille']],
            ['code' => 'AU', 'name' => 'Australia', 'cities' => ['Sydney', 'Melbourne', 'Brisbane']],
            ['code' => 'NL', 'name' => 'Netherlands', 'cities' => ['Amsterdam', 'Rotterdam']],
            ['code' => 'BR', 'name' => 'Brazil', 'cities' => ['São Paulo', 'Rio de Janeiro']],
        ];

        $weights = [
            0 => 38, // US
            1 => 22, // GB
            2 => 12, // CA
            3 => 10, // NG
            4 => 6,  // DE
            5 => 4,  // JP
            6 => 3,  // FR
            7 => 3,  // AU
            8 => 1,  // NL
            9 => 1,  // BR
        ];

        $deviceConfigs = [
            ['device' => 'Desktop', 'os' => 'macOS', 'browser' => 'Chrome', 'share' => 35],
            ['device' => 'Desktop', 'os' => 'Windows', 'browser' => 'Chrome', 'share' => 25],
            ['device' => 'Mobile', 'os' => 'iOS', 'browser' => 'Safari', 'share' => 20],
            ['device' => 'Mobile', 'os' => 'Android', 'browser' => 'Chrome', 'share' => 10],
            ['device' => 'Desktop', 'os' => 'macOS', 'browser' => 'Safari', 'share' => 5],
            ['device' => 'Tablet', 'os' => 'iOS', 'browser' => 'Safari', 'share' => 3],
            ['device' => 'Desktop', 'os' => 'Linux', 'browser' => 'Firefox', 'share' => 2],
        ];

        $paths = [
            '/' => 50,
            '/creator/jacksepticeye' => 18,
            '/creator/pewdiepie' => 12,
            '/creator/mrbeast' => 10,
            '/vault' => 6,
            '/pricing' => 4,
        ];

        $referers = [
            'Direct' => 40,
            'https://youtube.com' => 25,
            'https://twitter.com' => 18,
            'https://google.com' => 12,
            'https://instagram.com' => 5,
        ];

        $records = [];
        $now = now();

        for ($i = 0; $i < $count; $i++) {
            // Pick location based on weights
            $randLoc = rand(1, 100);
            $cum = 0;
            $selectedLoc = $locations[0];
            foreach ($weights as $idx => $w) {
                $cum += $w;
                if ($randLoc <= $cum) {
                    $selectedLoc = $locations[$idx];
                    break;
                }
            }
            $city = $selectedLoc['cities'][array_rand($selectedLoc['cities'])];

            // Pick device config
            $randDev = rand(1, 100);
            $cumDev = 0;
            $selectedDev = $deviceConfigs[0];
            foreach ($deviceConfigs as $dev) {
                $cumDev += $dev['share'];
                if ($randDev <= $cumDev) {
                    $selectedDev = $dev;
                    break;
                }
            }

            // Pick path
            $randPath = rand(1, 100);
            $cumPath = 0;
            $selectedPath = '/';
            foreach ($paths as $p => $weight) {
                $cumPath += $weight;
                if ($randPath <= $cumPath) {
                    $selectedPath = $p;
                    break;
                }
            }

            // Pick referer
            $randRef = rand(1, 100);
            $cumRef = 0;
            $selectedRef = 'Direct';
            foreach ($referers as $r => $weight) {
                $cumRef += $weight;
                if ($randRef <= $cumRef) {
                    $selectedRef = $r;
                    break;
                }
            }

            // Realistic IP generation based on country
            $ipSubnet = match ($selectedLoc['code']) {
                'US' => rand(64, 76) . '.' . rand(10, 250) . '.' . rand(1, 254),
                'GB' => '82.' . rand(10, 160) . '.' . rand(1, 254),
                'CA' => '142.' . rand(100, 250) . '.' . rand(1, 254),
                'NG' => '102.' . rand(88, 92) . '.' . rand(1, 254),
                'DE' => '188.' . rand(10, 120) . '.' . rand(1, 254),
                'JP' => '133.' . rand(10, 200) . '.' . rand(1, 254),
                'FR' => '90.' . rand(10, 100) . '.' . rand(1, 254),
                'AU' => '139.' . rand(130, 210) . '.' . rand(1, 254),
                default => '192.0.2.' . rand(1, 254),
            };
            $ip = $ipSubnet . '.' . rand(2, 250);

            // Stagger timestamp over last 48 hours, with higher concentration in last 3 hours
            $minutesAgo = $i < 20 
                ? rand(1, 45) 
                : ($i < 60 ? rand(46, 360) : rand(361, 2880));

            $time = (clone $now)->subMinutes($minutesAgo);

            $records[] = [
                'ip_address' => $ip,
                'country_code' => $selectedLoc['code'],
                'country_name' => $selectedLoc['name'],
                'city' => $city,
                'device_type' => $selectedDev['device'],
                'browser' => $selectedDev['browser'],
                'os' => $selectedDev['os'],
                'path' => $selectedPath,
                'referer' => $selectedRef,
                'user_agent' => "Mozilla/5.0 ({$selectedDev['os']}) AppleWebKit/537.36 {$selectedDev['browser']}",
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }

        static::insert($records);
    }
}
