import { ref, computed } from 'vue';
import { leagueApi } from '../services/leagueApi';
import { ANIMATION_DELAYS } from '../utils/constants';


export function useSimulation(leagueData) {
    const { currentWeek, sortedGroupKeys, groupFixtures, fetchAllData } = leagueData;

    const isSimulating = ref(false);
    const simulatingMatches = ref([]);

    
    const allRevealed = computed(() => {
        return simulatingMatches.value.length > 0 &&
            simulatingMatches.value.every(m => m.revealed);
    });

    
    const getMatchesByGroup = (groupName) => {
        return simulatingMatches.value.filter(m => m.groupLabel === groupName);
    };

    
    const playNextWeek = async () => {
        if (currentWeek.value >= 6) return;

        try {
            isSimulating.value = true;
            const nextWeekNum = currentWeek.value + 1;

            
            let weekMatches = [];
            sortedGroupKeys.value.forEach(group => {
                
                
                const matches = groupFixtures.value[group][nextWeekNum] || [];
                matches.forEach(m => {
                    
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

            
            await new Promise(resolve => setTimeout(resolve, ANIMATION_DELAYS.SIMULATION_START));

            let isWeekComplete = false;

            
            while (!isWeekComplete) {
                
                const result = await leagueApi.playNextMatch();

                
                const matchIndex = simulatingMatches.value.findIndex(m => m.id === result.match.id);

                if (matchIndex !== -1) {
                    
                    simulatingMatches.value[matchIndex] = {
                        ...simulatingMatches.value[matchIndex],
                        ...result.match, 
                        revealed: true
                    };
                } else {
                    
                    simulatingMatches.value.push({
                        ...result.match,
                        revealed: true,
                        groupLabel: result.match.group
                    });
                }

                
                isWeekComplete = result.is_last_match;

                
                if (!isWeekComplete) {
                    await new Promise(resolve => setTimeout(resolve, ANIMATION_DELAYS.MATCH_REVEAL));
                }
            }

            
            await fetchAllData(false);

            
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
