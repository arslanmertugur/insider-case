<template>
  <transition name="fade">
    <div v-if="isSimulating" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-[#020617]/90 backdrop-blur-md"></div>
      <div class="relative bg-[#1e293b] border border-[#fbbf24]/30 w-full max-w-2xl rounded-[2.5rem] p-8 shadow-2xl max-h-[90vh] flex flex-col">
        <div class="flex flex-col items-center mb-8 flex-shrink-0">
          <div class="w-16 h-16 bg-[#fbbf24] rounded-full flex items-center justify-center animate-bounce shadow-[0_0_20px_rgba(251,191,36,0.4)]">
            <span class="text-2xl">⚽</span>
          </div>
          <h3 class="mt-4 text-[#fbbf24] font-black uppercase tracking-[0.3em] text-sm italic text-center">
            {{ allRevealed ? 'All Match Results' : 'Matches Simulating...' }}
          </h3>
          <p class="text-slate-500 text-[10px] uppercase mt-1 tracking-widest font-bold text-center">Week {{ currentWeek + 1 }} - All Groups</p>
        </div>

        <div class="space-y-6 overflow-y-auto pr-2 custom-scrollbar">
          <div v-for="groupName in sortedGroupKeys" :key="groupName" class="space-y-2">
            <div class="flex items-center gap-2 px-2">
              <span class="text-[10px] font-black text-[#fbbf24]/50 uppercase tracking-widest">Group {{ groupName }}</span>
              <div class="h-[1px] bg-white/5 w-full"></div>
            </div>
            <div v-for="(match, index) in getMatchesByGroup(groupName)" :key="index" 
                 class="bg-[#0f172a] border border-white/5 p-4 rounded-2xl flex items-center justify-between transition-all duration-500"
                 :class="match.revealed ? 'border-[#fbbf24]/20 bg-[#fbbf24]/5' : 'opacity-40'">
              <div class="w-[38%] text-right font-black text-slate-300 text-xs truncate uppercase">{{ match.home_team_name }}</div>
              <div class="w-[24%] flex justify-center">
                <div v-if="!match.revealed" class="flex items-center justify-center gap-1">
                  <div class="w-1.5 h-1.5 bg-slate-600 rounded-full animate-pulse"></div>
                  <div class="w-1.5 h-1.5 bg-slate-600 rounded-full animate-pulse [animation-delay:0.2s]"></div>
                </div>
                <div v-else class="bg-[#fbbf24] text-[#020617] px-3 py-1 rounded-lg font-black text-sm animate-in zoom-in slide-in-from-top-2 duration-300">
                  {{ match.home_goals }} - {{ match.away_goals }}
                </div>
              </div>
              <div class="w-[40%] text-left font-black text-slate-300 text-xs truncate uppercase">{{ match.away_team_name }}</div>
            </div>
          </div>
        </div>
        <div class="mt-8 h-1 w-full bg-white/5 rounded-full overflow-hidden flex-shrink-0">
          <div :class="['h-full bg-[#fbbf24] transition-all duration-[2000ms]', isSimulating ? 'w-full' : 'w-0']"></div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
defineProps({
  isSimulating: {
    type: Boolean,
    default: false
  },
  matches: {
    type: Array,
    default: () => []
  },
  currentWeek: {
    type: Number,
    default: 0
  },
  allRevealed: {
    type: Boolean,
    default: false
  },
  sortedGroupKeys: {
    type: Array,
    default: () => []
  },
  getMatchesByGroup: {
    type: Function,
    required: true
  }
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(251, 191, 36, 0.3);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(251, 191, 36, 0.5);
}
</style>
