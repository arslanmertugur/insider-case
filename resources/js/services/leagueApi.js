import axios from 'axios';



const API_BASE = '/api';

export const leagueApi = {
    
    async fetchStandings() {
        const response = await axios.get(`${API_BASE}/standings`);
        return response.data;
    },

    
    async fetchFixtures() {
        const response = await axios.get(`${API_BASE}/fixtures/all`);
        return response.data;
    },

    
    async drawGroups() {
        const response = await axios.post(`${API_BASE}/draw-groups`);
        return response.data;
    },

    
    async generateFixtures() {
        const response = await axios.post(`${API_BASE}/fixtures`);
        return response.data;
    },

    
    async playNextWeek() {
        const response = await axios.post(`${API_BASE}/play-next-week`);
        return response.data;
    },

    
    async playNextMatch() {
        const response = await axios.post(`${API_BASE}/play-next-match`);
        return response.data;
    },

    
    async playAllWeeks() {
        const response = await axios.post(`${API_BASE}/play-all`);
        return response.data;
    },

    
    async resetLeague() {
        const response = await axios.post(`${API_BASE}/reset`);
        return response.data;
    }
};
