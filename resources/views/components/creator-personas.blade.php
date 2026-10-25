<section id="who-its-for" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8" x-data="{ 
    activeTab: 'all',
    personas: [
      {
        id: 'streamers',
        category: 'gaming',
        image: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=600&q=80',
        title: 'Streamers & Gamers',
        tag: 'Dual PC & OBS Studio',
        platforms: ['Twitch', 'Kick', 'YouTube Gaming'],
      },
      {
        id: 'vtubers',
        category: 'vtuber',
        image: 'https://images.unsplash.com/photo-1578632767115-351597cf2477?auto=format&fit=crop&w=600&q=80',
        title: 'VTubers & Virtual',
        tag: '3D Rigging & Lore Debuts',
        platforms: ['Twitch', 'YouTube', 'Discord'],
      },
      {
        id: 'cosplayers',
        category: 'craft',
        image: 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=600&q=80',
        title: 'Cosplayers & Crafters',
        tag: 'Armor Builds & Photo Sets',
        platforms: ['Instagram', 'TikTok', 'Patreon'],
      },
      {
        id: 'artists',
        category: 'craft',
        image: 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=600&q=80',
        title: 'Digital Artists',
        tag: 'Tablets & Sketch Prompts',
        platforms: ['Twitter / X', 'ArtStation', 'Bluesky'],
      },
      {
        id: 'musicians',
        category: 'audio',
        image: 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?auto=format&fit=crop&w=600&q=80',
        title: 'Musicians & Producers',
        tag: 'Studio Mics & EP Sessions',
        platforms: ['Spotify', 'YouTube', 'SoundCloud'],
      },
      {
        id: 'vloggers',
        category: 'video',
        image: 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=600&q=80',
        title: 'Video & Vloggers',
        tag: 'Cinema Lenses & Travel Gear',
        platforms: ['YouTube', 'TikTok', 'Reels'],
      },
      {
        id: 'fitness',
        category: 'lifestyle',
        image: 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=600&q=80',
        title: 'Fitness & Wellness',
        tag: 'Training Gear & Milestones',
        platforms: ['Instagram', 'YouTube', 'TikTok'],
      },
      {
        id: 'writers',
        category: 'words',
        image: 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=600&q=80',
        title: 'Writers & Educators',
        tag: 'Research Books & Coffee',
        platforms: ['Substack', 'Medium', 'Ghost'],
      }
    ]
}">
  <!-- Header with GiftPose-style pill and crisp typography -->
  <div class="text-center max-w-2xl mx-auto space-y-3">
    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-[#ecfdf5] border border-[#a7f3d0] text-[#064e3b] font-mono text-xs font-medium">
      <span class="w-1.5 h-1.5 rounded-full bg-[#047857]"></span>
      <span>WHO FANVAULT IS FOR</span>
    </div>
    
    <h2 class="font-serif text-2xl sm:text-4xl font-bold text-[#0f172a] tracking-tight">
      Built for Every Kind of Creator
    </h2>
    
    <p class="text-xs sm:text-sm md:text-base text-[#64748b] leading-relaxed">
      A 100% address-private space to collect milestone gifts, gear upgrades, and community letters without physical doxxing risks.
    </p>

    <!-- Category Filter Tabs -->
    <div class="flex flex-wrap items-center justify-center gap-1.5 sm:gap-2 pt-2">
      <button 
        type="button" 
        @click="activeTab = 'all'" 
        :class="activeTab === 'all' ? 'bg-[#03261d] text-white border-[#03261d]' : 'bg-white text-[#475569] border-[#e7e5df] hover:border-[#047857] hover:text-[#0f172a]'"
        class="px-3.5 py-1.5 rounded-lg text-xs font-medium border transition-colors cursor-pointer"
      >
        All Creators (8)
      </button>

      <button 
        type="button" 
        @click="activeTab = 'gaming'" 
        :class="activeTab === 'gaming' ? 'bg-[#03261d] text-white border-[#03261d]' : 'bg-white text-[#475569] border-[#e7e5df] hover:border-[#047857] hover:text-[#0f172a]'"
        class="px-3.5 py-1.5 rounded-lg text-xs font-medium border transition-colors cursor-pointer inline-flex items-center gap-1.5"
      >
        <span>🎮</span> Streamers & Gamers
      </button>

      <button 
        type="button" 
        @click="activeTab = 'vtuber'" 
        :class="activeTab === 'vtuber' ? 'bg-[#03261d] text-white border-[#03261d]' : 'bg-white text-[#475569] border-[#e7e5df] hover:border-[#047857] hover:text-[#0f172a]'"
        class="px-3.5 py-1.5 rounded-lg text-xs font-medium border transition-colors cursor-pointer inline-flex items-center gap-1.5"
      >
        <span>✨</span> VTubers & Virtual
      </button>

      <button 
        type="button" 
        @click="activeTab = 'craft'" 
        :class="activeTab === 'craft' ? 'bg-[#03261d] text-white border-[#03261d]' : 'bg-white text-[#475569] border-[#e7e5df] hover:border-[#047857] hover:text-[#0f172a]'"
        class="px-3.5 py-1.5 rounded-lg text-xs font-medium border transition-colors cursor-pointer inline-flex items-center gap-1.5"
      >
        <span>🎨</span> Cosplay & Artists
      </button>

      <button 
        type="button" 
        @click="activeTab = 'audio'" 
        :class="activeTab === 'audio' ? 'bg-[#03261d] text-white border-[#03261d]' : 'bg-white text-[#475569] border-[#e7e5df] hover:border-[#047857] hover:text-[#0f172a]'"
        class="px-3.5 py-1.5 rounded-lg text-xs font-medium border transition-colors cursor-pointer inline-flex items-center gap-1.5"
      >
        <span>🎵</span> Musicians & Audio
      </button>

      <button 
        type="button" 
        @click="activeTab = 'video'" 
        :class="activeTab === 'video' ? 'bg-[#03261d] text-white border-[#03261d]' : 'bg-white text-[#475569] border-[#e7e5df] hover:border-[#047857] hover:text-[#0f172a]'"
        class="px-3.5 py-1.5 rounded-lg text-xs font-medium border transition-colors cursor-pointer inline-flex items-center gap-1.5"
      >
        <span>📹</span> Video & Vloggers
      </button>

      <button 
        type="button" 
        @click="activeTab = 'words'" 
        :class="activeTab === 'words' ? 'bg-[#03261d] text-white border-[#03261d]' : 'bg-white text-[#475569] border-[#e7e5df] hover:border-[#047857] hover:text-[#0f172a]'"
        class="px-3.5 py-1.5 rounded-lg text-xs font-medium border transition-colors cursor-pointer inline-flex items-center gap-1.5"
      >
        <span>✍️</span> Writers & Educators
      </button>
    </div>
  </div>

  <!-- Persona Cards Grid with Images -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <template x-for="p in personas" :key="p.id">
      <div 
        x-show="activeTab === 'all' || activeTab === p.category"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="bg-white border border-[#e7e5df] hover:border-[#047857] rounded-xl overflow-hidden transition-all duration-300 flex flex-col justify-between group hover:shadow-md"
      >
        <!-- Real Corresponding Image with Zoom & Badge -->
        <div class="relative w-full aspect-[4/3] bg-[#f1f5f9] overflow-hidden">
          <img 
            :src="p.image" 
            :alt="p.title" 
            loading="lazy"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
          
          <!-- Category Tag Pill Overlay -->
          <div class="absolute bottom-2.5 left-2.5 right-2.5 flex items-center justify-between">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#03261d]/90 backdrop-blur-xs text-white text-[11px] font-mono font-medium tracking-wide shadow-xs border border-white/10">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
              <span x-text="p.tag"></span>
            </span>
          </div>
        </div>

        <!-- Card Body: Clean, Elegant, Non-Wordy -->
        <div class="p-4 sm:p-5 space-y-2.5">
          <div class="flex items-center justify-between gap-2">
            <h3 class="font-serif text-base sm:text-lg font-bold text-[#0f172a] group-hover:text-[#047857] transition-colors" x-text="p.title"></h3>
            <a href="{{ route('creators.join') }}" class="w-7 h-7 rounded-full bg-[#ecfdf5] border border-[#a7f3d0] flex items-center justify-center text-[#047857] group-hover:bg-[#047857] group-hover:text-white transition-colors" title="Create Capsule">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </a>
          </div>

          <!-- Platform Tags -->
          <div class="flex flex-wrap gap-1.5">
            <template x-for="plat in p.platforms" :key="plat">
              <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-[#f7f6f0] border border-[#e7e5df] text-[#475569] font-medium" x-text="plat"></span>
            </template>
          </div>
        </div>
      </div>
    </template>
  </div>

  <!-- Bottom Conversion Callout -->
  <div class="bg-[#f7f6f0] border border-[#e7e5df] rounded-xl p-6 sm:p-8 flex flex-col md:flex-row items-center justify-between gap-6">
    <div class="space-y-1 text-center md:text-left">
      <div class="text-xs font-mono font-semibold uppercase text-[#047857] tracking-wider">Universal Creator Protection</div>
      <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a]">Don't see your specific niche above?</h3>
      <p class="text-xs sm:text-sm text-[#64748b] max-w-xl">
        If you have an audience that wants to show support, celebrate a milestone, or fund a setup upgrade without privacy risks, FanVault was built for you.
      </p>
    </div>
    <div class="flex items-center gap-3 shrink-0">
      <a href="{{ route('creators.join') }}" class="px-5 py-2.5 rounded-lg bg-[#03261d] hover:bg-[#064e3b] text-white font-bold text-xs sm:text-sm transition-colors shadow-2xs">
        Create Your Time Capsule (Free) →
      </a>
      <a href="{{ route('creators') }}" class="px-4 py-2.5 rounded-lg bg-white hover:bg-[#faf9f5] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm transition-colors">
        Browse Creators Directory
      </a>
    </div>
  </div>
</section>
