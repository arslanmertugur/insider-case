
export const ANIMATION_DELAYS = {
    SIMULATION_START: 300,    // 1500ms → 300ms (5x faster)
    MATCH_REVEAL: 100,        // 300ms → 100ms (3x faster)
    SIMULATION_END: 400,      // 2000ms → 400ms (5x faster)
    SUCCESS_TOAST: 1500,
};

export const SWAL_THEME = {
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
