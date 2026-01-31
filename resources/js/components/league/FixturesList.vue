<template>
  <section class="bg-[#1e293b]/30 backdrop-blur-md rounded-[2.5rem] border border-white/10 p-8 shadow-2xl">
    <h3 class="text-xl font-black text-white mb-10 italic uppercase px-2 tracking-tight">Match Schedules (Group {{ groupName }})</h3>
    
    <div v-if="isLoading" class="space-y-12">
      <div v-for="i in 2" :key="i">
        <div class="h-4 bg-white/5 w-24 mb-6 rounded animate-pulse"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="j in 2" :key="j" class="h-[70px] bg-white/[0.02] border border-white/5 rounded-2xl animate-pulse"></div>
        </div>
      </div>
    </div>

    <div v-else-if="fixtures && Object.keys(fixtures).length > 0" class="space-y-12">
      <div v-for="(matches, weekNum) in fixtures" :key="weekNum">
        <div class="flex items-center gap-4 mb-6 px-2">
          <h4 class="text-[10px] font-black text-[#fbbf24] uppercase tracking-widest whitespace-nowrap">Week {{ weekNum }}</h4>
          <div class="h-[1px] bg-white/10 w-full"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="match in matches" :key="match.id" 
               class="bg-[#0f172a] border border-white/5 p-4 rounded-2xl flex items-center justify-between min-h-[70px] hover:border-[#fbbf24]/30 transition-all">
            <div class="w-[38%] text-right font-black text-slate-300 text-xs truncate uppercase">{{ match.home_team_name }}</div>
            <div class="w-[24%] flex justify-center">
              <div class="w-[65px] py-2 bg-[#fbbf24]/10 border border-[#fbbf24]/20 rounded-lg font-black text-[#fbbf24] text-xs text-center">
                {{ match.played ? `${match.home_goals} - ${match.away_goals}` : 'vs' }}
              </div>
            </div>
            <div class="w-[38%] text-left font-black text-slate-300 text-xs truncate uppercase">{{ match.away_team_name }}</div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-10">
      <p class="text-slate-500 font-bold uppercase text-xs tracking-widest">No fixtures generated yet.</p>
    </div>
  </section>
</template>

<script setup>
defineProps({
  fixtures: {
    type: Object,
    default: () => ({})
  },
  groupName: {
    type: String,
    required: true
  },
  isLoading: {
    type: Boolean,
    default: false
  }
});
</script>
