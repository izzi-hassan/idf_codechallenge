<template>
    <div class="card mt-4">
        <h2 class="card-header">Course Leaderboard</h2>
        <div class="card-body">
            <p>
                Your rankings improve every time you answer a question correctly.
                Keep learning and earning course points to become one of our top learners!
            </p>
            <div class="row">
                <div class="col-md-12">
                    <span v-if="! status.hasRanks">This course has not been completed by any Users</span>
                </div>
                <div class="col-md-6" v-if="status.hasRanks">
                    <h4>You are ranked <b>{{ userCountryRank }}</b> in {{ user.country.name }}</h4>
                    <category-board :slots="countryRanks"></category-board>
                </div>
                <div class="col-md-6" v-if="status.hasRanks">
                    <h4>You are ranked <b>{{ userWorldRank }}</b> Worldwide</h4>
                    <category-board :slots="worldRanks"></category-board>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import CategoryBoard from './CategoryBoard';

    export default {
        name: 'CourseLeaderBoard',
        components: {
            categoryBoard: CategoryBoard
        },
        props: {
            user: {
                type: Object,
                required: true
            },
            courseId: {
                type: Number,
                required: true
            }
        },
        data() {
            return {
                userCountryRank: '--',
                userWorldRank: '--',
                countryRanks: [],
                worldRanks: [],
                poller: {
                    id: null,
                    interval: 10000
                },
                status: {
                    loading: true,
                    loaded: false,
                    error: false,
                    info: null,
                    hasRanks: false
                }
            };
        },
        methods: {
            refreshBoards() {
                // Fetch leaderboard data for this course
                axios.get('/api/course/' + this.courseId + '/leaderboard')
                .then(response => {
                    this.status = {
                        ...this.status,
                        error: false,
                        info: 'Leaderboard loaded: ' + response
                    }

                    this.filterRanks(response.data);
                    this.status.loaded = true;
                    this.startPoll();
                })
                .catch(error => {
                    this.status = {
                        ...this.status,
                        error: true,
                        loaded: false,
                        info: 'Error loading leaderboard data: ' + error,
                    };

                    console.log(this.status.info);
                })
                .finally(() => {
                    this.status.loading = false;
                });
            },
            filterRanks(rankings) {
                /*  Handle the case where no user has completed the course */
                this.status.hasRanks = (rankings.length > 0);

                /*  Check for case where the logged in user has a score equal to someone else
                    and give the current user precedence.
                */
                const loggedInUserInRanks = _.find(rankings, {id: this.user.id});

                if (loggedInUserInRanks !== undefined) {
                    let loggedInUserRankIndex = _.findIndex(rankings, {id: this.user.id});
                    let firstUserWithSameScoreIndex = _.findIndex(rankings, {courseScore: loggedInUserInRanks.courseScore});
                    [rankings[loggedInUserRankIndex], rankings[firstUserWithSameScoreIndex]] = [rankings[firstUserWithSameScoreIndex], rankings[loggedInUserRankIndex]];
                }

                /* Filter the rankings by country and get interesting users */
                const countryRanks =  getRanks(_.filter(_.cloneDeep(rankings), {'country_id': this.user.country_id}), loggedInUserInRanks);
                this.countryRanks = countryRanks.ranks;

                /* Get interesting users globally */
                const worldRanks = getRanks(_.cloneDeep(rankings), loggedInUserInRanks);
                this.worldRanks = worldRanks.ranks;

                /* User's ranks */
                if (loggedInUserInRanks !== undefined) {
                    this.userCountryRank = countryRanks.loggedInUserRank;
                    this.userWorldRank = worldRanks.loggedInUserRank;
                }
            },
            startPoll() {
                this.poller.id = setTimeout(this.refreshBoards, this.poller.interval)
            }
        },
        mounted() {
            this.refreshBoards();
        },
        beforeDestroy() {
            if (this.poller !== null) {
                clearTimeout(this.poller.id);
            }
        }
    }

    /**
     * Figures out which ranks to display
     * Returns the ranks for the board along with the loggedIn User's rank object
     * 
     * @param {Array} rankings
     * @param {Object} loggedInUser
     * 
     * @return {Object}
     */
    function getRanks(rankings, loggedInUser) {
        rankings = _.forEach(rankings, (user, key) => {
            user.rank = key + 1;
            user.pointsDifference = (loggedInUser !== undefined) ? user.courseScore - loggedInUser.courseScore : false;

            if (user.pointsDifference && user.pointsDifference <= 0) {
                user.pointsDifference = false;
            }

            if (loggedInUser !== undefined && user.id == loggedInUser.id) {
                user.isLoggedInUser = true;
                loggedInUser.rank = user.rank;
            }
        });

        /* Handle the case where there are fewer than 10 Users who have completed the course  */
        if (rankings.length <= 9) {
            return {
                loggedInUserRank: (loggedInUser !== undefined) ? loggedInUser.rank : false,
                ranks: rankings
            }
        }
        
        /* Get the groups we are interested in */
        const topThree = _.take(rankings, 3);
        const bottomThree = _.takeRight(rankings, 3);

        let topTier = [], middleTier = [], bottomTier = [];
        /* Manipulate results to show the rankings we are interested in */
        if (loggedInUser === undefined) {
            topTier = topThree;
            bottomTier = bottomThree;
        } else {
            const loggedInUserThree = (loggedInUser.rank == 1 || loggedInUser.rank == 2) ? topThree : _.slice(rankings, loggedInUser.rank - 2, loggedInUser.rank + 1);
            
            if (loggedInUser.rank <= 4) {
                // User in Top Tier
                topTier = _.union(topThree, loggedInUserThree);
                
                bottomTier = bottomThree;
            } else if (bottomThree[0].rank - loggedInUser.rank < 2 ) {
                // User in Bottom Tier
                bottomTier = _.union(loggedInUserThree, bottomThree);

                topTier = topThree;
            } else {
                // User in Middle Tier
                topTier = topThree;
                bottomTier = bottomThree;
                middleTier = loggedInUserThree;
            }
        }

        if (middleTier.length == 0) {
            // How many Middle Tier users needed
            let middleTierLength = 9 - (topTier.length + bottomTier.length);

            // Get Median User
            const medianRankIndex = topTier[topTier.length - 1].rank + Math.ceil((bottomTier[0].rank - topTier[topTier.length - 1].rank) / 2) - 1;
            middleTier.push(rankings[medianRankIndex]);

            // Fill out Middle Tier
            let i = 1;
            while (middleTierLength > 1) {
                middleTier.push(rankings[medianRankIndex + i]);

                if (--middleTierLength == 1) {
                    break;
                }
                
                middleTier.unshift(rankings[medianRankIndex - i]);

                middleTierLength--;
                i++;
            }
        }

        middleTier[0].nonSequentialStart = true;
        middleTier[middleTier.length - 1].nonSequentialEnd = true;

        return {
            loggedInUserRank: (loggedInUser !== undefined) ? loggedInUser.rank : false,
            ranks: [
                ...topTier,
                ...middleTier,
                ...bottomTier
            ]
        };
    }
</script>