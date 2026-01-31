import { ref, computed } from 'vue';
import { leagueApi } from '../services/leagueApi';
import { ANIMATION_DELAYS } from '../utils/constants';

/**
 * Composable for managing simulation logic
 */
export function useSimulation(leagueData) {
    const { currentWeek, sortedGroupKeys, groupFixtures, fetchAllData } = leagueData;

    const isSimulating = ref(false);
    const simulatingMatches = ref([]);

    /**
     * Check if all matches are revealed
     */
    const allRevealed = computed(() => {
        return simulatingMatches.value.length > 0 &&
            simulatingMatches.value.every(m => m.revealed);
    });

    /**
     * Get matches filtered by group name
     */
    const getMatchesByGroup = (groupName) => {
        return simulatingMatches.value.filter(m => m.groupLabel === groupName);
    };

    /**
     * Play the next week with animation
     */
    const playNextWeek = async () => {
        if (currentWeek.value >= 6) return;

        try {
            const nextWeekNum = currentWeek.value + 1;
            let weekMatches = [];

            // Collect all matches for the next week across all groups
            sortedGroupKeys.value.forEach(group => {
                const matches = groupFixtures.value[group][nextWeekNum] || [];
                matches.forEach(m => {
                    weekMatches.push({ ...m, revealed: false, groupLabel: group });
                });
            });

            simulatingMatches.value = weekMatches;
            isSimulating.value = true;

            // Wait before calling API
            await new Promise(resolve => setTimeout(resolve, ANIMATION_DELAYS.SIMULATION_START));
            await leagueApi.playNextWeek();

            // Fetch updated data
            await fetchAllData(false);

            // Get updated matches with results
            let updatedMatches = [];
            sortedGroupKeys.value.forEach(group => {
                const matches = groupFixtures.value[group][currentWeek.value] || [];
                matches.forEach(m => {
                    updatedMatches.push({ ...m, revealed: false, groupLabel: group });
                });
            });
            simulatingMatches.value = updatedMatches;

            // Reveal matches one by one
            for (let i = 0; i < simulatingMatches.value.length; i++) {
                await new Promise(resolve => setTimeout(resolve, ANIMATION_DELAYS.MATCH_REVEAL));
                simulatingMatches.value[i].revealed = true;
            }

            // Wait before closing
            await new Promise(resolve => setTimeout(resolve, ANIMATION_DELAYS.SIMULATION_END));
            isSimulating.value = false;

        } catch (err) {
            isSimulating.value = false;
            console.error('Error playing next week:', err);
        }
    };

    return {
        isSimulating,
        simulatingMatches,
        allRevealed,
        getMatchesByGroup,
        playNextWeek
    };
}
