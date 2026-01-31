<template>
  <div class="min-h-screen bg-[#020617] p-4 md:p-10 font-sans text-slate-200">
    
    <SimulationModal 
      :isSimulating="isSimulating"
      :matches="simulatingMatches"
      :currentWeek="currentWeek"
      :allRevealed="allRevealed"
      :sortedGroupKeys="sortedGroupKeys"
      :getMatchesByGroup="getMatchesByGroup"
    />

    <header class="max-w-7xl mx-auto mb-12">
      <div class="relative overflow-hidden bg-[#1e293b]/50 backdrop-blur-xl border border-white/10 rounded-[2.5rem] p-8 shadow-2xl">
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-8 text-center md:text-left">
          <div>
            <h1 class="text-4xl md:text-5xl font-black tracking-tighter italic text-white leading-none uppercase">
              CHAMPIONS <span class="text-[#fbbf24] drop-shadow-[0_0_15px_rgba(251,191,36,0.4)]">LEAGUE</span>
            </h1>
            <p class="text-slate-400 font-bold tracking-[0.4em] text-[10px] mt-3 uppercase">Elite Tournament Simulator</p>
          </div>
          <div class="flex flex-wrap justify-center gap-4">
            <button @click="handleDrawAndGenerate" class="bg-[#fbbf24] hover:bg-[#f59e0b] text-[#020617] px-8 py-4 rounded-2xl font-black shadow-lg transition-all active:scale-95 text-xs tracking-widest uppercase">🎲 Draw Groups & Fixtures</button>
            <button @click="handleReset" class="bg-white/5 hover:bg-white/10 text-white px-6 py-4 rounded-2xl font-bold transition-all border border-white/10 text-xs uppercase tracking-widest">Reset</button>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto">
      <div v-if="isLoading && sortedGroupKeys.length === 0" class="flex justify-center gap-3 mb-10 overflow-hidden">
        <div v-for="i in 8" :key="i" class="w-24 h-12 bg-white/5 rounded-xl animate-pulse"></div>
      </div>
      <GroupTabs 
        v-else
        :groups="sortedGroupKeys"
        v-model:activeGroup="activeGroup"
        :isLoading="isLoading"
      />

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-8 space-y-10">
          <StandingsTable 
            :teams="allGroups[activeGroup] || []"
            :groupName="activeGroup"
            :isLoading="isLoading"
            :hasAnyPredictions="hasAnyPredictions"
          />

          <FixturesList 
            :fixtures="groupFixtures[activeGroup]"
            :groupName="activeGroup"
            :isLoading="isLoading"
          />
        </div>

        <aside class="lg:col-span-4">
          <ControlPanel 
            :isSimulating="isSimulating"
            :isLoading="isLoading"
            @play-next-week="playNextWeek"
            @play-all-weeks="handlePlayAll"
          />
        </aside>
      </div>
    </main>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useLeagueData } from '../../composables/useLeagueData';
import { useSimulation } from '../../composables/useSimulation';
import { useNotifications } from '../../composables/useNotifications';
import { leagueApi } from '../../services/leagueApi';
import SimulationModal from './SimulationModal.vue';
import GroupTabs from './GroupTabs.vue';
import StandingsTable from './StandingsTable.vue';
import FixturesList from './FixturesList.vue';
import ControlPanel from './ControlPanel.vue';

// Composables
const leagueData = useLeagueData();
const { 
  allGroups, 
  groupFixtures, 
  activeGroup, 
  currentWeek, 
  isLoading, 
  sortedGroupKeys, 
  hasAnyPredictions, 
  fetchAllData 
} = leagueData;

const simulation = useSimulation(leagueData);
const { 
  isSimulating, 
  simulatingMatches, 
  allRevealed, 
  getMatchesByGroup, 
  playNextWeek 
} = simulation;

const { showConfirmDialog, showSuccessToast, showLoadingDialog, closeDialog } = useNotifications();

// Actions
const handleDrawAndGenerate = async () => {
  const result = await showConfirmDialog({
    title: '🏆 Draw Groups?',
    text: 'This will reset current standings and create new fixtures.',
    confirmButtonText: 'Start Draw',
    cancelButtonText: 'Cancel'
  });

  if (result.isConfirmed) {
    isLoading.value = true;
    try {
      await leagueApi.drawGroups();
      await leagueApi.generateFixtures();
      await fetchAllData();
      showSuccessToast({
        title: 'Success!',
        text: 'Groups and fixtures generated.'
      });
    } catch (e) {
      console.error('Error drawing groups:', e);
      isLoading.value = false;
    }
  }
};

const handlePlayAll = async () => {
  showLoadingDialog({
    title: '⚽ Simulating All',
    text: 'Processing all group stage matches...'
  });

  try {
    await leagueApi.playAllWeeks();
    await fetchAllData();
    closeDialog();
  } catch (e) {
    console.error('Error simulating all weeks:', e);
    showConfirmDialog({
      title: 'Error',
      text: 'Simulation failed.',
      icon: 'error'
    });
  }
};

const handleReset = async () => {
  const result = await showConfirmDialog({
    title: '⚠️ Reset League?',
    text: 'All data will be permanently cleared.',
    icon: 'warning',
    confirmButtonText: 'Yes, Reset',
    cancelButtonText: 'Go Back'
  });

  if (result.isConfirmed) {
    isLoading.value = true;
    try {
      await leagueApi.resetLeague();
      await fetchAllData();
      showSuccessToast({
        title: 'Reset Done',
        timer: 1500
      });
    } catch (e) {
      console.error('Error resetting league:', e);
      isLoading.value = false;
    }
  }
};

onMounted(fetchAllData);
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
