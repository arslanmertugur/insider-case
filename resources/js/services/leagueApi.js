import axios from 'axios';

/**
 * League API Service
 * Centralized API calls for league operations
 */

const API_BASE = '/api';

export const leagueApi = {
    /**
     * Fetch all group standings
     */
    async fetchStandings() {
        const response = await axios.get(`${API_BASE}/standings`);
        return response.data;
    },

    /**
     * Fetch all fixtures grouped by group and week
     */
    async fetchFixtures() {
        const response = await axios.get(`${API_BASE}/fixtures/all`);
        return response.data;
    },

    /**
     * Draw groups and assign teams
     */
    async drawGroups() {
        const response = await axios.post(`${API_BASE}/draw-groups`);
        return response.data;
    },

    /**
     * Generate fixtures for all groups
     */
    async generateFixtures() {
        const response = await axios.post(`${API_BASE}/fixtures`);
        return response.data;
    },

    /**
     * Play the next week of matches
     */
    async playNextWeek() {
        const response = await axios.post(`${API_BASE}/play-next-week`);
        return response.data;
    },

    /**
     * Play the next single match
     */
    async playNextMatch() {
        const response = await axios.post(`${API_BASE}/play-next-match`);
        return response.data;
    },

    /**
     * Simulate all remaining weeks
     */
    async playAllWeeks() {
        const response = await axios.post(`${API_BASE}/play-all`);
        return response.data;
    },

    /**
     * Reset the entire league
     */
    async resetLeague() {
        const response = await axios.post(`${API_BASE}/reset`);
        return response.data;
    }
};
