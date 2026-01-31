import './bootstrap';
import { createApp } from 'vue/dist/vue.esm-bundler'; // Bu import şekli daha garantidir
import LeagueSimulation from './components/league/LeagueSimulation.vue';
import '../css/app.css'; // Bu satırın olduğundan emin ol
const app = createApp({});

app.component('league-simulation', LeagueSimulation);
app.mount('#app');