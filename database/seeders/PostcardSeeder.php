<?php

namespace Database\Seeders;

use App\Models\Creator;
use App\Models\Envelope;
use App\Models\Postcard;
use App\Models\Referral;
use App\Models\Stat;
use App\Support\Capsule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostcardSeeder extends Seeder
{
    public function run(): void
    {
        $seeds = [
            // Original 18
            ['Amara Okafor', 'Lagos, Nigeria', '2050-01-01', 'I hope you are reading this from a kinder century.', 'I wrote this the year the rains came late. If the air is easier to breathe, we did something right. Tell the children we were trying.', 'future-dispatch', ['mars' => 'no', 'jobs' => 'ai_copilot', 'hundred' => 'yes']],
            ['James Whitaker', 'Manchester, UK', '2047-03-14', 'I hope you are reading this from Mars.', 'It is a wet Tuesday and I am 31. I do not know if you made it off the planet. If you did, send something back. We were lonely in a crowded way.', null, ['mars' => 'yes', 'jobs' => 'ai_dominant', 'hundred' => 'no']],
            ['Yuki Tanaka', 'Kyoto, Japan', '2031-11-02', 'We still thought we had more time.', 'Use yours better than we did. I kept waiting for a quieter year. It never came. I hope you are not waiting too.', 'maya-in-tokyo', ['mars' => 'no', 'jobs' => 'balanced', 'hundred' => 'yes']],
            ['Sofia Herrera', 'Mexico City, Mexico', '2050-01-01', 'If the air is clean enough to walk in, we did something right.', 'I walked home through traffic that would not move. I wanted you to know the city was still loud and still mine. Keep a street for walking.', null, []],
            ['Noah Berg', 'Bergen, Norway', '2028-09-21', 'The rain still sounded like this.', 'I hope it still does. I am leaving you the sound more than the words. Play it if the world got too smooth.', 'nostalgia-vault', []],
            ['Amina Diallo', 'Dakar, Senegal', '2044-04-08', "Tell my daughter's daughter we were trying.", "Her name is not in this line because the wall is public. The rest of this is for her, and for you if you are kind with other people's names.", null, []],
            ['Luca Moretti', 'Lisbon, Portugal', '2027-06-18', 'I left this on a Saturday night.', 'I was afraid of being forgotten. If you are reading the sealed part, I lasted. That is enough.', 'wanderers2050', []],
            ['Priya Shah', 'Mumbai, India', '2041-09-30', 'We argued about machines becoming smarter than us.', 'I hope they became kinder too. I used one to write the first draft of this and then I rewrote it by hand so you would know a person was here.', null, []],
            ['Elena Popov', 'Prague, Czechia', '2035-05-01', 'I was 29 and still learning how to stay.', 'If you found this looking for your family, I hope staying got easier. I did not know how to say that on the wall.', null, []],
            ['Kwame Mensah', 'Accra, Ghana', '2033-12-31', 'If you still dance in the street when the lights go out, we won.', 'Do not let them move the party indoors. I am sealing the rest so the wall does not spoil the joke.', 'future-dispatch', []],
            ['Hannah Cole', 'New Orleans, USA', '2030-08-29', 'Keep the music louder than the fear.', 'The year I wrote this, the water kept coming. I wanted 2050 to know we sang anyway. The verses are in the envelope.', null, []],
            ['Min-jun Park', 'Seoul, South Korea', '2049-12-31', 'I do not know if you will still call this the internet.', 'Whatever you call it, I hope it still lets ordinary people leave a mark without asking permission from a company that forgot us.', 'maya-in-tokyo', []],
            ['Clara Alves', 'São Paulo, Brazil', '2038-02-14', 'I hope my city is still too alive to explain.', 'If they flattened it, I am sorry. There was a corner shop that knew my order. That is the part I would not put on the wall.', null, []],
            ['Omar Farouk', 'Cairo, Egypt', '2042-07-07', 'The river was low the year I wrote this.', 'I hope it rose. I am sealing a prayer I did not want strangers to treat as content.', null, []],
            ['Ingrid Nilsen', 'Reykjavík, Iceland', '2029-12-21', 'We could still see the sky.', 'Do not lose that. I have more to say about the dark months. It can wait until you open this.', 'nostalgia-vault', []],
            ['Daniel Okoye', 'Toronto, Canada', '2041-06-12', 'I think my daughter will be 31 when this opens.', 'Tell her I was proud already. Her name and the rest of the letter are not for the wall. They are for 2050, and for her.', null, []],
            ['Mei Chen', 'Melbourne, Australia', '2026-12-25', 'I was tired and hopeful at the same time.', 'That was 2026. If you are less tired, good. If not, you are not the first. The longer note is for whoever still reads slowly.', null, []],
            ['Rosa Bennett', 'Edinburgh, UK', '2050-01-01', 'If you find this, you already know we were here.', 'The wall is the wave. This sealed part is the letter I would have put in a drawer. Hello, then. We were here.', 'nostalgia-vault', []],

            // Extended collection (56 new emotional, diverse entries)
            ['Mateo Morales', 'Buenos Aires, Argentina', '2048-05-25', 'Did you keep the tango in the plazas?', 'Every Sunday we met in San Telmo under the yellow lamplight. Promise me you did not trade cobblestones for sterile glass towers.', 'wanderers2050', ['mars' => 'no', 'jobs' => 'human_centric', 'hundred' => 'yes']],
            ['Fatima Al-Mansoor', 'Dubai, UAE', '2036-10-14', 'The glass towers were tall, but the desert was patient.', 'We built islands in the water and cooled the air with engines. I hope you found peace with the sun instead of fighting it.', null, []],
            ['Liam O’Connor', 'Dublin, Ireland', '2032-03-17', 'Tell me people still sing the old choruses in pubs.', 'A pint was six euros and the world felt like it was spinning too fast. If you are reading this, raise one for the class of 2026.', 'nostalgia-vault', []],
            ['Siti Nurhaliza', 'Kuala Lumpur, Malaysia', '2040-08-31', 'The night market smelled of roasted chili and rain.', 'You could buy skewers for a dollar and talk until 3 a.m. with people you met ten minutes earlier. I hope warmth is still free.', null, []],
            ['Antoine Dupont', 'Lyon, France', '2034-09-22', 'Bread was still baked before dawn by tired hands.', 'Do not let machines convince you that yeast and waiting can be optimized. The secret ingredient was always human impatience.', 'future-dispatch', []],
            ['Aisha Bello', 'Kano, Nigeria', '2045-11-19', 'My grandmother knew the medicinal names of seventy trees.', 'I wrote them all down in this letter so they would survive whatever database migration your generation is busy with.', null, []],
            ['Jonas Lindqvist', 'Stockholm, Sweden', '2037-06-21', 'On Midsummer, the sun refused to sleep.', 'We put wildflowers under our pillows and believed in magic for thirty-six hours. I hope summer still feels like that.', 'wanderers2050', []],
            ['Leila Karimi', 'Isfahan, Iran', '2043-04-10', 'The turquoise tiles kept the midday heat away.', 'Under the bridges, boys sang poetry to the echo of the stone arches. Please tell me poetry survived the algorithms.', null, []],
            ['Tomasz Wójcik', 'Kraków, Poland', '2029-04-12', 'The pigeons in the Rynek still owned the city.', 'We walked across the square shivering in April coats. I was in love with someone whose name is hidden in the envelope.', 'nostalgia-vault', []],
            ['Chloe Evans', 'Auckland, New Zealand', '2046-02-06', 'We looked south toward the ice and worried.', 'The sea was a blue so deep it hurt your eyes. I wrote this while sitting on black volcanic sand, hoping the tide would be kind to you.', null, []],
            ['Hassan Touré', 'Bamako, Mali', '2039-12-05', 'The kora strings were nylon, but the songs were centuries old.', 'When Toumani played, the whole room went quiet. May your century understand what silence is for.', 'future-dispatch', []],
            ['Camila Vargas', 'Bogotá, Colombia', '2035-08-07', 'The mountain clouds descended onto the avenue every afternoon.', 'It smelled of wet eucalyptus and cheap black coffee. We were young, broke, and convinced we would solve everything.', null, []],
            ['Kenji Sato', 'Sapporo, Japan', '2050-01-01', 'Did the snow keep coming in January?', 'We built statues out of ice that melted by March. It taught us to love things that could not stay. Hello from the winter of 2026.', 'maya-in-tokyo', []],
            ['Keziah Mwangi', 'Nairobi, Kenya', '2041-07-28', 'The jacaranda trees turned the pavement purple every October.', 'You could hear matatu conductors shouting fares over the morning horns. The city had a pulse no spreadsheet could capture.', 'future-dispatch', []],
            ['Nikolaos Petridis', 'Thessaloniki, Greece', '2030-05-15', 'We sat by the Thermaic Gulf until the fishing boats returned.', 'Five friends, a plate of grilled sardines, and endless arguments about philosophy. If you have that, you are wealthy.', null, []],
            ['Zhenya Volkova', 'Almaty, Kazakhstan', '2042-03-21', 'The apple orchards were blooming when I wrote this.', 'The snow was still on the Tian Shan peaks above us. I am sealing the recipe for my mother’s baursak for when you open this.', null, []],
            ['Samira Abboud', 'Beirut, Lebanon', '2047-08-04', 'We rebuilt the same balcony four times.', 'Do not ever let anyone tell you people get tired of living. We were stubborn in the most beautiful way imaginable.', 'nostalgia-vault', []],
            ['Julian Mercer', 'Seattle, USA', '2028-11-14', 'I spent eight hours watching trees through rain-streaked glass.', 'Everything online told me I was wasting my life. It was the best afternoon of my twenty-fourth year.', null, []],
            ['Thabo Ndlovu', 'Johannesburg, South Africa', '2033-04-27', 'The jacarandas and the gold dust made the air shimmer.', 'We were still healing, still figuring out how to share the same sky. Tell me you finally figured it out.', 'future-dispatch', []],
            ['Evelyn Vance', 'Oxford, UK', '2049-10-31', 'Libraries still smelled of paper, leather, and quiet longing.', 'I wondered if you would read this on paper or through some glowing lens in your eye. Either way: cherish dusty rooms.', 'nostalgia-vault', []],
            ['Arjun Nair', 'Kochi, India', '2038-08-15', 'The backwaters were silent at dawn except for kingfishers.', 'I turned off my phone for three days to write this. The silence was louder than anything I had heard all year.', null, []],
            ['Linnea Holst', 'Copenhagen, Denmark', '2027-09-08', 'We biked through sleet because the coffee shop had cinnamon rolls.', 'That was the entirety of our rebellion. If happiness in 2050 is complicated, remember it used to be butter and flour.', 'wanderers2050', []],
            ['Santiago Cruz', 'Valparaíso, Chile', '2044-01-19', 'The funicular squeaked on its cables between the hills.', 'The ocean was vast and cold. I left a message under a blue tile on Calle Templeman. If it survived, it is yours.', null, []],
            ['Nadia Cherkas', 'Kyiv, Ukraine', '2050-01-01', 'We planted chestnut trees where the shells had fallen.', 'I want 2050 to remember that light was not something we waited for; it was something we kept in our pockets and shared.', 'nostalgia-vault', []],
            ['Tariq Mansoor', 'Muscat, Oman', '2036-02-28', 'The frankincense smoke curled into the courtyard at dusk.', 'My grandfather told me the desert gives back whatever you give it. We tried to give it kindness.', null, []],
            ['Zoe Katsaros', 'Sydney, Australia', '2045-12-31', 'The fireworks over the harbor were pink and gold.', 'I kissed a stranger at midnight and forgot to ask their name. That was New Year 2026. The rest of the story is sealed.', 'wanderers2050', []],
            ['Babatunde Adeleke', 'Ibadan, Nigeria', '2031-06-05', 'The rusty roofs under heavy clouds looked like amber.', 'The radio was playing King Sunny Ade. I wrote this while waiting for my exams to come out. I hope I made us proud.', 'future-dispatch', []],
            ['Hanna Lind', 'Helsinki, Finland', '2028-02-02', 'Minus eighteen degrees outside, ninety inside the sauna.', 'We jumped into the ice hole and felt our blood shout. Do people still remember how to shock themselves awake?', 'nostalgia-vault', []],
            ['Gabriel Souza', 'Salvador, Brazil', '2039-02-20', 'The drums in the Pelourinho vibrated inside my chest.', 'A thousand people dancing with white paint on their arms. We were alive and we knew it while it was happening.', null, []],
            ['Ananya Sen', 'Kolkata, India', '2043-10-18', 'The trams still rattled past College Street book stalls.', 'I bought a battered copy of Tagore for thirty rupees. If paper is obsolete, I mourn for you.', null, []],
            ['Miloš Jovanović', 'Belgrade, Serbia', '2034-07-11', 'The Danube and the Sava met under the fortress.', 'We drank plum brandy and talked about empires that thought they would last forever. Take care of the rivers.', null, []],
            ['Maya Al-Husseini', 'Amman, Jordan', '2048-09-01', 'The Roman theater looked out over seven hills of limestone.', 'White laundry fluttered on rooftops in the afternoon breeze. I hope you still look at laundry like it is a flag of peace.', 'future-dispatch', []],
            ['Finnian Burke', 'Galway, Ireland', '2029-08-14', 'The Atlantic wind smelled of brine and peat smoke.', 'A fiddler was playing on Shop Street in a yellow raincoat. I promised myself I would not forget the rhythm.', 'nostalgia-vault', []],
            ['Sara Lindemann', 'Vienna, Austria', '2037-11-20', 'We had Melange and Sachertorte in a cafe with velvet booths.', 'Nobody looked at their watches. The waiters were wonderfully grumpy. Guard your slow afternoons fiercely.', null, []],
            ['Tenzin Norbu', 'Lhasa, Tibet', '2047-05-04', 'The butter lamps flickered against thousand-year-old murals.', 'Pilgrims were circumambulating the Jokhang. Some things do not care about centuries. May your peace be like stone.', null, []],
            ['Rami Khoury', 'Jerusalem', '2050-01-01', 'The olive trees were old when Rome was young.', 'I touched the bark of one in Gethsemane and whispered a prayer for the century that comes after ours.', 'nostalgia-vault', []],
            ['Harper Campbell', 'Austin, USA', '2032-10-30', 'Barton Springs was cold enough to take your breath away.', 'We floated on our backs watching cedar waxwings in the trees. Remember that free water is the only real wealth.', null, []],
            ['Sora Kim', 'Busan, South Korea', '2044-09-12', 'The fish market was noisy before the gulls woke up.', 'Ajummas were scaling mackerel with knives worn thin as paper. Every morning was earned. Don’t take yours for granted.', 'maya-in-tokyo', []],
            ['Idris Diallo', 'Conakry, Guinea', '2035-03-25', 'The red dust stained the hems of everyone’s trousers.', 'When the rain arrived, the smell of earth was intoxicating. We danced in the downpour until our shirts stuck.', null, []],
            ['Elise Moreau', 'Montréal, Canada', '2026-10-15', 'Mount Royal was on fire with orange maple leaves.', 'We ate warm bagels out of brown paper bags on a wooden park bench. If this reached you, I hope you smiled.', 'wanderers2050', []],
            ['Dawid Van Der Merwe', 'Cape Town, South Africa', '2046-12-16', 'Table Mountain had its white tablecloth spread across.', 'The penguins at Boulders Beach smelled terrible and looked magnificent. I hope both are still true.', null, []],
            ['Aylin Demir', 'Istanbul, Turkey', '2041-04-23', 'The ferry from Kadıköy to Eminönü crossed between continents.', 'A boy threw simit to the gulls. Ten cents for fifteen minutes of open sky. Keep the ferries running.', 'future-dispatch', []],
            ['Leo Castiglione', 'Florence, Italy', '2033-06-24', 'The Arno was murky green under the Ponte Vecchio.', 'We bought two scoops of pistachio gelato and leaned against the stone balustrade. The world was ancient and brand new.', 'nostalgia-vault', []],
            ['Nia Williams', 'Kingston, Jamaica', '2038-08-06', 'The bassline from the street corner shook our windows.', 'Independence Day in Trench Town. We had little, but our pride had wings. Walk tall in 2050.', null, []],
            ['Magnus Einarsson', 'Tórshavn, Faroe Islands', '2029-06-01', 'The grass on the roofs was greener than emeralds.', 'Only fog, sheep, and the roar of the North Atlantic. I wrote this so you would know solitude can be a sanctuary.', null, []],
            ['Luz Elena Gomez', 'Medellín, Colombia', '2048-12-24', 'The cable car floated over the brick houses on the hill.', 'Every rooftop had festive lights strung from chimney to chimney. We were proud of how far we had climbed.', 'wanderers2050', []],
            ['Rory MacLeod', 'Isle of Skye, Scotland', '2036-05-18', 'The Cuillins were wrapped in purple heather and mist.', 'My dog chased rabbits he could never catch. I buried a glass marble near the Fairy Pools. It’s for you.', 'nostalgia-vault', []],
            ['Amina Zeroual', 'Marrakech, Morocco', '2042-10-09', 'The Jemaa el-Fnaa smelled of cumin, mint tea, and charcoal.', 'Storytellers were surrounded by circles of quiet listeners. Protect the stories that are told with human breath.', 'future-dispatch', []],
            ['Victor Vance', 'Chicago, USA', '2030-01-20', 'The wind off Lake Michigan froze tears on your cheeks.', 'We took the Brown Line across the river as the sun set behind the towers. It looked like a city built of copper.', null, []],
            ['Dmitri Petrov', 'Tbilisi, Georgia', '2045-09-29', 'The wine had been fermenting in clay qvevri underground.', 'We toasted our ancestors, our children, and the strangers who would read our letters. Gaumarjos to 2050.', null, []],
            ['Salma Qureshi', 'Lahore, Pakistan', '2037-03-23', 'The Badshahi Mosque in the rain was red sandstone and grace.', 'Pigeons roosted in the arches. My mother adjusted my dupatta and told me time is a river that only moves forward.', null, []],
            ['Felix Bauer', 'Berlin, Germany', '2027-08-03', 'We danced in an abandoned power plant until 9 a.m.', 'When we stepped out, the Sunday air smelled of linden trees. Nobody judged anyone. Please don’t lose that.', 'wanderers2050', []],
            ['Abeni Osei', 'Kumasi, Ghana', '2049-07-15', 'The kente weavers’ shuttles clicked like woodblock clocks.', 'Gold, black, red, and green threads weaving history. Remember that what you wear carries who you came from.', 'future-dispatch', []],
            ['Einar Thorvaldsson', 'Akureyri, Iceland', '2031-01-01', 'The northern lights danced green across the fjord.', 'We turned off all the house lights and stood on the porch in wool socks. Some beauty cannot be captured, only endured.', 'nostalgia-vault', []],
            ['Clara Wei', 'Taipei, Taiwan', '2043-05-10', 'The night market stinky tofu smelled awful and tasted heavenly.', 'We took scooters up Elephant Mountain to look down at the 101 tower. The city was glowing like an ember.', 'maya-in-tokyo', []],
            ['Zackary Adams', 'San Francisco, USA', '2050-01-01', 'We were obsessed with building minds out of silicon.', 'I hope they taught you to value the minds you were born with. I sealed this letter the day our daughter was born.', 'future-dispatch', ['mars' => 'yes', 'jobs' => 'ai_dominant', 'hundred' => 'yes']],
        ];

        DB::transaction(function () use ($seeds) {
            $creatorsBySlug = Creator::query()->pluck('id', 'slug');

            foreach ($seeds as $i => $row) {
                $n = $i + 1;
                $name = $row[0];
                $location = $row[1];
                $day = $row[2];
                $teaser = $row[3];
                $letter = $row[4];
                $creatorSlug = $row[5] ?? null;
                $predictions = $row[6] ?? [];

                $creatorId = ($creatorSlug && isset($creatorsBySlug[$creatorSlug]))
                    ? $creatorsBySlug[$creatorSlug]
                    : null;

                $milestoneId = null;
                if ($creatorId) {
                    $milestoneId = \App\Models\Milestone::where('creator_id', $creatorId)->value('id');
                }

                $existing = Postcard::query()->where('number', $n)->first();
                if ($existing) {
                    $existing->update([
                        'name' => $name,
                        'location' => $location,
                        'teaser' => $teaser,
                        'addressed_to' => Capsule::clampAddressDate($day),
                        'creator_id' => $creatorId,
                        'milestone_id' => $milestoneId,
                    ]);
                    $existing->envelope()->updateOrCreate(
                        ['postcard_id' => $existing->id],
                        [
                            'letter' => $letter,
                            'predictions' => $predictions,
                        ]
                    );

                    if ($creatorId) {
                        Referral::query()->firstOrCreate(
                            ['postcard_id' => $existing->id],
                            [
                                'creator_id' => $creatorId,
                                'cut_cents' => Capsule::CREATOR_CUT_CENTS,
                                'status' => ($n % 2 === 0) ? 'paid' : 'pending',
                            ]
                        );
                    }
                    continue;
                }

                $id = (string) Str::uuid();
                $postcard = Postcard::query()->create([
                    'id' => $id,
                    'number' => $n,
                    'name' => $name,
                    'location' => $location,
                    'teaser' => $teaser,
                    'addressed_to' => Capsule::clampAddressDate($day),
                    'sealed_at' => now()->subDays(max(1, count($seeds) - $i)),
                    'founding' => true,
                    'creator_id' => $creatorId,
                    'milestone_id' => $milestoneId,
                    'seeded' => true,
                ]);

                Envelope::query()->create([
                    'postcard_id' => $id,
                    'letter' => $letter,
                    'email' => 'author' . $n . '@example.test',
                    'predictions' => $predictions,
                ]);

                if ($creatorId) {
                    Referral::query()->create([
                        'postcard_id' => $id,
                        'creator_id' => $creatorId,
                        'cut_cents' => Capsule::CREATOR_CUT_CENTS,
                        'status' => ($n % 2 === 0) ? 'paid' : 'pending',
                    ]);
                }
            }

            Stat::query()->updateOrCreate(['id' => 1], [
                'sealed_count' => Postcard::query()->count(),
                'founding_count' => Postcard::query()->where('founding', true)->count(),
            ]);
        });
    }
}
