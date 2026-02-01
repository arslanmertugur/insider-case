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
            isSimulating.value = true;
            const nextWeekNum = currentWeek.value + 1;

            // 1. Pre-fill simulatingMatches with unplayed matches
            let weekMatches = [];
            sortedGroupKeys.value.forEach(group => {
                // Get matches for next week
                // Note: groupFixtures structure is { GroupName: { WeekNum: [matches] } }
                const matches = groupFixtures.value[group][nextWeekNum] || [];
                matches.forEach(m => {
                    // Start as unrevealed
                    weekMatches.push({
                        ...m,
                        revealed: false,
                        groupLabel: group,
                        home_goals: '?',
                        away_goals: '?'
                    });
                });
            });

            simulatingMatches.value = weekMatches;
            isSimulating.value = true;

            // Wait before starting
            await new Promise(resolve => setTimeout(resolve, ANIMATION_DELAYS.SIMULATION_START));

            let isWeekComplete = false;

            // Keep calling playNextMatch until the week is complete
            while (!isWeekComplete) {
                // Play match in backend
                const result = await leagueApi.playNextMatch();

                // Find this match in our local state and update it
                const matchIndex = simulatingMatches.value.findIndex(m => m.id === result.match.id);

                if (matchIndex !== -1) {
                    // Update with results and visual flair
                    simulatingMatches.value[matchIndex] = {
                        ...simulatingMatches.value[matchIndex],
                        ...result.match, // This contains home_goals, away_goals
                        revealed: true
                    };
                } else {
                    // Fallback if not found (shouldn't happen if pre-fill works)
                    simulatingMatches.value.push({
                        ...result.match,
                        revealed: true,
                        groupLabel: result.match.group
                    });
                }

                // Check if this was the last match of the week
                isWeekComplete = result.is_last_match;

                // Wait before showing next match (unless it's the last one)
                if (!isWeekComplete) {
                    await new Promise(resolve => setTimeout(resolve, ANIMATION_DELAYS.MATCH_REVEAL));
                }
            }

            // Fetch updated data after all matches are complete
            await fetchAllData(false);

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
