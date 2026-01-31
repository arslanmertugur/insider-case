import { ref, computed } from 'vue';
import { leagueApi } from '../services/leagueApi';

/**
 * Composable for managing league data state
 */
export function useLeagueData() {
    const allGroups = ref({});
    const groupFixtures = ref({});
    const activeGroup = ref('A');
    const currentWeek = ref(0);
    const isLoading = ref(true);

    /**
     * Sorted group keys (A, B, C, D...)
     */
    const sortedGroupKeys = computed(() => {
        return Object.keys(allGroups.value).sort();
    });

    /**
     * Check if active group has any predictions
     */
    const hasAnyPredictions = computed(() => {
        const teams = allGroups.value[activeGroup.value] || [];
        return teams.some(team => team.guess > 0);
    });

    /**
     * Fetch all league data (standings and fixtures)
     */
    const fetchAllData = async (showLoading = true) => {
        if (showLoading) isLoading.value = true;

        try {
            const [standings, fixtures] = await Promise.all([
                leagueApi.fetchStandings(),
                leagueApi.fetchFixtures()
            ]);

            allGroups.value = standings;
            groupFixtures.value = fixtures;

            // Calculate current week from first group
            const sortedKeys = Object.keys(allGroups.value).sort();
            if (sortedKeys.length > 0) {
                const firstGroup = sortedKeys[0];
                if (allGroups.value[firstGroup]?.length > 0) {
                    currentWeek.value = Math.max(...allGroups.value[firstGroup].map(t => t.played));
                }
            }
        } catch (err) {
            console.error('Error fetching league data:', err);
        } finally {
            isLoading.value = false;
        }
    };

    return {
        allGroups,
        groupFixtures,
        activeGroup,
        currentWeek,
        isLoading,
        sortedGroupKeys,
        hasAnyPredictions,
        fetchAllData
    };
}
