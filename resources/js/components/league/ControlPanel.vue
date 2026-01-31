<template>
  <div class="bg-[#1e293b]/50 backdrop-blur-xl p-8 rounded-[2.5rem] border border-white/10 sticky top-10 shadow-2xl">
    <button 
      @click="$emit('play-next-week')" 
      :disabled="isSimulating || isLoading" 
      class="w-full bg-[#fbbf24] hover:bg-[#f59e0b] disabled:opacity-50 text-[#020617] py-5 rounded-2xl font-black transition-all mb-4 uppercase text-xs tracking-widest"
    >
      ⚽ Play Next Week
    </button>
    <button 
      @click="$emit('play-all-weeks')" 
      :disabled="isLoading" 
      class="w-full bg-white/5 hover:bg-white/10 text-slate-300 py-5 rounded-2xl font-black border border-white/10 transition-all uppercase text-xs tracking-widest italic"
    >
      Simulate All
    </button>
  </div>
</template>

<script setup>
defineProps({
  isSimulating: {
    type: Boolean,
    default: false
  },
  isLoading: {
    type: Boolean,
    default: false
  }
});

defineEmits(['play-next-week', 'play-all-weeks']);
</script>
