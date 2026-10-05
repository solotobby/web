<?php

namespace App\Support;

use App\Models\Creator;
use App\Models\Postcard;

class Seo
{
    /**
     * WebSite & Organization structured data.
     */
    public static function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => url('/') . '#website',
                    'url' => url('/'),
                    'name' => 'FanVault',
                    'description' => 'The milestone time capsule and digital fan mail platform for creators.',
                    'publisher' => [
                        '@id' => url('/') . '#organization',
                    ],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => url('/creators') . '?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@type' => 'Organization',
                    '@id' => url('/') . '#organization',
                    'name' => 'FanVault',
                    'url' => url('/'),
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => asset('assets/favicon.svg'),
                    ],
                    'sameAs' => [
                        'https://getfanvault.com',
                    ],
                ],
            ],
        ];
    }

    /**
     * Creators Directory ItemList structured data.
     */
    public static function creatorsDirectorySchema($creators): array
    {
        $items = [];
        $position = 1;

        foreach ($creators as $creator) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'item' => [
                    '@type' => 'ProfilePage',
                    'name' => "{$creator->name}’s Milestone Vault",
                    'url' => route('with', $creator->slug),
                    'description' => $creator->bio ?: "Milestone time capsule for {$creator->name}.",
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Creator Door ProfilePage, Person & Event structured data.
     */
    public static function creatorSchema(Creator $creator): array
    {
        $active = $creator->activeMilestone();
        $unlockDate = $active?->unlock_date ?? $creator->unlock_date;
        $milestoneTitle = $active?->title ?? $creator->milestone_title ?? 'Community Milestone';

        $graph = [
            [
                '@type' => 'ProfilePage',
                '@id' => route('with', $creator->slug) . '#webpage',
                'url' => route('with', $creator->slug),
                'name' => "{$creator->name}’s Community Milestone Vault — FanVault",
                'description' => "Leave a sealed letter in {$creator->name}’s {$milestoneTitle} time vault. Unlocks on " . ($active?->formattedUnlockDate() ?? $creator->formattedUnlockDate()) . '.',
                'mainEntity' => [
                    '@type' => 'Person',
                    '@id' => route('with', $creator->slug) . '#creator',
                    'name' => $creator->name,
                    'alternateName' => $creator->handle ? "@{$creator->handle}" : null,
                    'description' => $creator->bio,
                    'url' => route('with', $creator->slug),
                ],
            ],
        ];

        if ($unlockDate) {
            $graph[] = [
                '@type' => 'Event',
                'name' => "{$creator->name}: {$milestoneTitle} Stream Reveal",
                'description' => "Live stream celebration unsealing community letters and milestone predictions for {$creator->name}.",
                'startDate' => $unlockDate->toIso8601String(),
                'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
                'eventStatus' => 'https://schema.org/EventScheduled',
                'location' => [
                    '@type' => 'VirtualLocation',
                    'url' => route('with', $creator->slug),
                ],
                'organizer' => [
                    '@type' => 'Person',
                    'name' => $creator->name,
                    'url' => route('with', $creator->slug),
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    /**
     * Sealed letter commemorative pass schema.
     */
    public static function messageSchema(Postcard $postcard): array
    {
        $creator = $postcard->creator;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => "Fan Pass No. " . Capsule::formatNumber($postcard->number) . ($creator ? " — {$creator->name} Vault" : ' — FanVault'),
            'headline' => $postcard->teaser ?: 'Community milestone letter',
            'author' => [
                '@type' => 'Person',
                'name' => $postcard->name,
            ],
            'dateCreated' => ($postcard->sealed_at ?? $postcard->created_at)->toIso8601String(),
            'url' => route('message', $postcard->id),
        ];
    }

    /**
     * FAQ schema helper for rich snippets.
     */
    public static function faqSchema(array $faqs): array
    {
        $entities = [];

        foreach ($faqs as $q => $a) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $a,
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * Render structured data array as JSON-LD script tag.
     */
    public static function toJson(array $data): string
    {
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
