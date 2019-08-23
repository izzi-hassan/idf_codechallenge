require('./bootstrap');
import CourseLeaderboard from './components/CourseLeaderBoard/CourseLeaderboard.vue';

window.Vue = require('vue');

Vue.component('course-leaderboard', CourseLeaderboard);

const app = new Vue({
    el: '#vue-course-leaderboard'
});