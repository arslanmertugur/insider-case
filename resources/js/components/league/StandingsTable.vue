<template>
  <section class="bg-[#1e293b]/30 backdrop-blur-md rounded-[2.5rem] border border-white/10 overflow-hidden shadow-2xl">
    <div class="px-8 py-6 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
      <h2 class="text-xl font-black text-white italic uppercase">Standings (Group {{ groupName }})</h2>
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
            <th v-if="hasAnyPredictions" class="w-[20%] py-5 text-center text-[#fbbf24] italic tracking-widest">Prediction</th>
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
            <tr v-for="(team, index) in teams" :key="team.team_name" class="group hover:bg-white/[0.03] transition-all">
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
              
              <td v-if="hasAnyPredictions" class="py-5 px-4">
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
</template>

<script setup>
defineProps({
  teams: {
    type: Array,
    required: true
  },
  groupName: {
    type: String,
    required: true
  },
  isLoading: {
    type: Boolean,
    default: false
  },
  hasAnyPredictions: {
    type: Boolean,
    default: false
  }
});
</script>
