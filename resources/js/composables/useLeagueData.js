import { ref, computed } from 'vue';
import { leagueApi } from '../services/leagueApi';


export function useLeagueData() {
    const allGroups = ref({});
    const groupFixtures = ref({});
    const activeGroup = ref('A');
    const currentWeek = ref(0);
    const isLoading = ref(true);

    
    const sortedGroupKeys = computed(() => {
        return Object.keys(allGroups.value).sort();
    });

    
    const hasAnyPredictions = computed(() => {
        const teams = allGroups.value[activeGroup.value] || [];
        return teams.some(team => team.guess > 0);
    });

    
    const fetchAllData = async (showLoading = true) => {
        if (showLoading) isLoading.value = true;

        try {
            const [standings, fixtures] = await Promise.all([
                leagueApi.fetchStandings(),
                leagueApi.fetchFixtures()
            ]);

            allGroups.value = standings;
            groupFixtures.value = fixtures;

            
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
