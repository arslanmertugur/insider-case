<template>
  <div class="min-h-screen bg-[#020617] p-4 md:p-10 font-sans text-slate-200">
    
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
            <button @click="drawAndGenerate" class="bg-[#fbbf24] hover:bg-[#f59e0b] text-[#020617] px-8 py-4 rounded-2xl font-black shadow-lg transition-all active:scale-95 text-xs tracking-widest uppercase">🎲 Draw Groups & Fixtures</button>
            <button @click="resetLeague" class="bg-white/5 hover:bg-white/10 text-white px-6 py-4 rounded-2xl font-bold transition-all border border-white/10 text-xs uppercase tracking-widest">Reset</button>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto">
      <div v-if="isLoading && sortedGroupKeys.length === 0" class="flex justify-center gap-3 mb-10 overflow-hidden">
        <div v-for="i in 8" :key="i" class="w-24 h-12 bg-white/5 rounded-xl animate-pulse"></div>
      </div>
      <div v-else class="flex flex-nowrap overflow-x-auto pb-4 justify-start md:justify-center gap-3 mb-10 no-scrollbar">
        <button 
          v-for="groupName in sortedGroupKeys" :key="groupName"
          @click="activeGroup = groupName"
          :class="activeGroup === groupName ? 'bg-[#fbbf24] text-[#020617]' : 'bg-[#1e293b]/40 text-slate-400'"
          class="px-6 py-3 rounded-xl font-black transition-all border-2 border-transparent text-sm min-w-[100px] flex-shrink-0 uppercase"
        >
          Group {{ groupName }}
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-8 space-y-10">
          <section class="bg-[#1e293b]/30 backdrop-blur-md rounded-[2.5rem] border border-white/10 overflow-hidden shadow-2xl">
            <div class="px-8 py-6 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
              <h2 class="text-xl font-black text-white italic uppercase">Standings (Group {{ activeGroup }})</h2>
              <span v-if="!isLoading" class="text-[10px] font-black text-[#fbbf24] uppercase tracking-widest">Group Phase</span>
              <div v-else class="w-16 h-2 bg-[#fbbf24]/20 animate-pulse rounded-full"></div>
            </div>
            <div class="overflow-x-auto overflow-y-hidden">
              <table class="w-full table-fixed min-w-[600px]"> 
                <thead>
                  <tr class="text-slate-500 text-[10px] font-black uppercase tracking-[0.2em] bg-white/[0.01]">
                    <th class="w-[30%] px-8 py-5 text-left">Club</th>
                    <th class="w-[10%] py-5 text-center">P</th>
                    <th class="w-[10%] py-5 text-center">W</th>
                    <th class="w-[10%] py-5 text-center text-red-400">L</th>
                    <th class="w-[10%] py-5 text-center">GD</th>
                    <th class="w-[10%] py-5 text-center text-[#fbbf24]">Pts</th>
                    <th class="w-[20%] py-5 text-center text-[#fbbf24] italic tracking-widest">Prediction</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <template v-if="isLoading">
                    <tr v-for="i in 4" :key="i" class="animate-pulse">
                      <td class="px-8 py-5"><div class="h-4 bg-white/5 rounded w-3/4"></div></td>
                      <td v-for="j in 6" :key="j" class="py-5"><div class="h-4 bg-white/5 rounded w-1/2 mx-auto"></div></td>
                    </tr>
                  </template>
                  <template v-else>
                    <tr v-for="(team, index) in allGroups[activeGroup]" :key="team.team_name" class="group hover:bg-white/[0.03] transition-all">
                      <td class="px-8 py-5">
                        <div class="flex items-center gap-3">
                          <span class="text-[10px] font-black text-slate-600 w-4">{{ index + 1 }}</span>
                          <span class="font-bold text-slate-200 truncate group-hover:text-[#fbbf24] transition-colors">{{ team.team_name }}</span>
                        </div>
                      </td>
                      <td class="py-5 text-center text-slate-400 font-bold">{{ team.played }}</td>
                      <td class="py-5 text-center text-slate-500 text-sm">{{ team.won }}</td>
                      <td class="py-5 text-center text-slate-500 text-sm">{{ team.lost }}</td>
                      <td class="py-5 text-center">
                        <span :class="team.goal_difference >= 0 ? 'text-green-400' : 'text-red-400'" class="font-black text-xs">
                          {{ team.goal_difference > 0 ? '+' : '' }}{{ team.goal_difference }}
                        </span>
                      </td>
                      <td class="py-5 text-center font-black text-[#fbbf24] text-xl">{{ team.points }}</td>
                      
                      <td class="py-5 px-4">
                        <div v-if="team.guess > 0" class="flex flex-col items-center gap-1.5 animate-in fade-in zoom-in duration-700">
                          <div class="flex items-center gap-1.5">
                            <span v-if="team.guess >= 50" class="text-[10px] animate-pulse">🏆</span>
                            <span class="font-black text-white text-sm tracking-tighter">{{ team.guess }}%</span>
                          </div>
                          <div class="w-20 h-1 bg-white/10 rounded-full overflow-hidden">
                            <div 
                              class="h-full bg-gradient-to-r from-[#fbbf24]/40 to-[#fbbf24] transition-all duration-1000"
                              :style="{ width: team.guess + '%' }"
                            ></div>
                          </div>
                        </div>
                        <div v-else class="flex flex-col items-center opacity-20">
                          <span class="text-[8px] font-bold uppercase tracking-widest text-slate-500 italic">Calculating</span>
                          <div class="w-12 h-0.5 bg-white/10 rounded-full mt-1"></div>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </section>

          <section class="bg-[#1e293b]/30 backdrop-blur-md rounded-[2.5rem] border border-white/10 p-8 shadow-2xl">
            <h3 class="text-xl font-black text-white mb-10 italic uppercase px-2 tracking-tight">Match Schedules (Group {{ activeGroup }})</h3>
            
            <div v-if="isLoading" class="space-y-12">
               <div v-for="i in 2" :key="i">
                  <div class="h-4 bg-white/5 w-24 mb-6 rounded animate-pulse"></div>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="j in 2" :key="j" class="h-[70px] bg-white/[0.02] border border-white/5 rounded-2xl animate-pulse"></div>
                  </div>
               </div>
            </div>

            <div v-else-if="groupFixtures[activeGroup]" class="space-y-12">
              <div v-for="(matches, weekNum) in groupFixtures[activeGroup]" :key="weekNum">
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
        </div>

        <aside class="lg:col-span-4">
          <div class="bg-[#1e293b]/50 backdrop-blur-xl p-8 rounded-[2.5rem] border border-white/10 sticky top-10 shadow-2xl">
              <button @click="playNextWeek" :disabled="isSimulating || isLoading" class="w-full bg-[#fbbf24] hover:bg-[#f59e0b] disabled:opacity-50 text-[#020617] py-5 rounded-2xl font-black transition-all mb-4 uppercase text-xs tracking-widest">⚽ Play Next Week</button>
              <button @click="playAllWeeks" :disabled="isLoading" class="w-full bg-white/5 hover:bg-white/10 text-slate-300 py-5 rounded-2xl font-black border border-white/10 transition-all uppercase text-xs tracking-widest italic">Simulate All</button>
          </div>
        </aside>
      </div>
    </main>
  </div>
</template>
<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const allGroups = ref({});
const groupFixtures = ref({});
const activeGroup = ref('A');
const currentWeek = ref(0);
const isSimulating = ref(false);
const isLoading = ref(true); 
const simulatingMatches = ref([]);

// --- Tasarım Konfigürasyonu ---
const swalConfig = {
  background: '#1e293b',
  color: '#f1f5f9',
  confirmButtonColor: '#fbbf24',
  cancelButtonColor: 'transparent',
  customClass: {
    popup: 'rounded-[2.5rem] border border-white/10 shadow-2xl font-sans',
    title: 'text-xl font-black uppercase italic tracking-tight text-white',
    htmlContainer: 'text-slate-400 font-medium',
    confirmButton: 'bg-[#fbbf24] hover:bg-[#f59e0b] text-[#020617] px-8 py-3 rounded-xl font-black uppercase text-xs tracking-widest transition-all mx-2',
    cancelButton: 'text-slate-400 hover:text-white font-bold uppercase text-xs tracking-widest transition-all mx-2',
    loader: 'border-[#fbbf24]'
  }
};

const sortedGroupKeys = computed(() => {
  return Object.keys(allGroups.value).sort();
});

const getMatchesByGroup = (groupName) => {
  return simulatingMatches.value.filter(m => m.groupLabel === groupName);
};

const allRevealed = computed(() => {
  return simulatingMatches.value.length > 0 && simulatingMatches.value.every(m => m.revealed);
});

const fetchAllData = async (showLoading = true) => {
  if(showLoading) isLoading.value = true;
  try {
    const res = await axios.get('/api/standings');
    allGroups.value = res.data;
    const fixRes = await axios.get('/api/fixtures/all'); 
    groupFixtures.value = fixRes.data;

    const sortedKeys = Object.keys(allGroups.value).sort();
    if (sortedKeys.length > 0) {
        const firstGroup = sortedKeys[0];
        if (allGroups.value[firstGroup]?.length > 0) {
            currentWeek.value = Math.max(...allGroups.value[firstGroup].map(t => t.played));
        }
    }
  } catch (err) { 
    console.error(err); 
  } finally {
    isLoading.value = false;
  }
};

const playNextWeek = async () => {
  if (currentWeek.value >= 6) return;

  try {
    const nextWeekNum = currentWeek.value + 1;
    let weekMatches = [];

    sortedGroupKeys.value.forEach(group => {
      const matches = groupFixtures.value[group][nextWeekNum] || [];
      matches.forEach(m => {
        weekMatches.push({ ...m, revealed: false, groupLabel: group });
      });
    });

    simulatingMatches.value = weekMatches;
    isSimulating.value = true;

    await new Promise(resolve => setTimeout(resolve, 1500));
    await axios.post('/api/play-next-week');
    
    await fetchAllData(false);

    let updatedMatches = [];
    sortedGroupKeys.value.forEach(group => {
      const matches = groupFixtures.value[group][currentWeek.value] || [];
      matches.forEach(m => {
        updatedMatches.push({ ...m, revealed: false, groupLabel: group });
      });
    });
    simulatingMatches.value = updatedMatches;

    for (let i = 0; i < simulatingMatches.value.length; i++) {
      await new Promise(resolve => setTimeout(resolve, 300));
      simulatingMatches.value[i].revealed = true;
    }

    await new Promise(resolve => setTimeout(resolve, 2000));
    isSimulating.value = false;

  } catch (err) {
    isSimulating.value = false;
    console.error(err);
  }
};

const drawAndGenerate = async () => {
    const result = await Swal.fire({ 
      ...swalConfig,
      title: '🏆 Draw Groups?', 
      text: 'This will reset current standings and create new fixtures.',
      icon: 'question', 
      iconColor: '#fbbf24',
      showCancelButton: true,
      confirmButtonText: 'Start Draw',
      cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        isLoading.value = true;
        try {
            await axios.post('/api/draw-groups');
            await axios.post('/api/fixtures');
            await fetchAllData();
            Swal.fire({
              ...swalConfig,
              title: 'Success!',
              text: 'Groups and fixtures generated.',
              icon: 'success',
              iconColor: '#fbbf24',
              timer: 1500,
              showConfirmButton: false
            });
        } catch (e) { 
          console.error(e); 
          isLoading.value = false;
        }
    }
};

const playAllWeeks = async () => {
    Swal.fire({ 
      ...swalConfig,
      title: '⚽ Simulating All', 
      text: 'Processing all group stage matches...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading() 
    });

    try {
        await axios.post('/api/play-all');
        await fetchAllData();
        Swal.close();
    } catch (e) { 
      console.error(e); 
      Swal.fire({ ...swalConfig, title: 'Error', text: 'Simulation failed.', icon: 'error' });
    }
};

const resetLeague = async () => {
    const result = await Swal.fire({ 
      ...swalConfig,
      title: '⚠️ Reset League?', 
      text: 'All data will be permanently cleared.',
      icon: 'warning', 
      iconColor: '#ef4444',
      showCancelButton: true,
      confirmButtonText: 'Yes, Reset',
      confirmButtonColor: '#ef4444',
      cancelButtonText: 'Go Back'
    });

    if (result.isConfirmed) {
        isLoading.value = true;
        try {
            await axios.post('/api/reset');
            await fetchAllData();
            Swal.fire({
              ...swalConfig,
              title: 'Reset Done',
              icon: 'success',
              iconColor: '#fbbf24',
              timer: 1500,
              showConfirmButton: false
            });
        } catch (e) { 
          console.error(e); 
          isLoading.value = false;
        }
    }
};

onMounted(fetchAllData);
</script>