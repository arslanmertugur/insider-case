import './bootstrap';
import { createApp } from 'vue/dist/vue.esm-bundler'; 
import LeagueSimulation from './components/league/LeagueSimulation.vue';
import '../css/app.css'; 
const app = createApp({});

app.component('league-simulation', LeagueSimulation);
app.mount('#app');