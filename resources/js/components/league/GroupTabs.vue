<template>
  <div class="flex flex-nowrap overflow-x-auto pb-4 justify-start md:justify-center gap-3 mb-10 no-scrollbar">
    <button 
      v-for="groupName in groups" 
      :key="groupName"
      @click="$emit('update:activeGroup', groupName)"
      :class="activeGroup === groupName ? 'bg-[#fbbf24] text-[#020617]' : 'bg-[#1e293b]/40 text-slate-400'"
      class="px-6 py-3 rounded-xl font-black transition-all border-2 border-transparent text-sm min-w-[100px] flex-shrink-0 uppercase"
    >
      Group {{ groupName }}
    </button>
  </div>
</template>

<script setup>
defineProps({
  groups: {
    type: Array,
    required: true
  },
  activeGroup: {
    type: String,
    required: true
  },
  isLoading: {
    type: Boolean,
    default: false
  }
});

defineEmits(['update:activeGroup']);
</script>
