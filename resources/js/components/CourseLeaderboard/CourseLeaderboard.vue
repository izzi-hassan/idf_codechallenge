<template>
    <div class="card mt-4">
        <h2 class="card-header">Course Leaderboard</h2>
        <div class="card-body">
            <p>
                Your rankings improve every time you answer a question correctly.
                Keep learning and earning course points to become one of our top learners!
            </p>
            <div class="row">
                <div class="col-md-6">
                    <h4>You are ranked <b>{{ userCountryRank }}</b> in {{ user.country.name }}</h4>
                    <category-board :slots="countryRanks"></category-board>
                </div>
                <div class="col-md-6">
                    <h4>You are ranked <b>{{ userWorldRank }}</b> Worldwide</h4>
                    <category-board :slots="worldRanks"></category-board>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import CategoryBoard from './CategoryBoard';
    import { clearTimeout } from 'timers';

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
                poller: null,
                status: {
                    loading: true,
                    loaded: false,
                    error: false,
                    info: null
                }
            };
        },
        methods: {
            refreshBoards() {
                console.log('Refreshing Leaderboard');

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
                        info: 'Error fetching leaderboard data: ' + error
                    };
                    console.log(this.status.info);
                })
                .finally(() => {
                    this.status.loading = false;
                });
            },
            filterRanks(rankings) {
                /*  Check for case where the logged in user has a score equal to someone else
                    and give the current user precedence.
                */
                let loggedInUserIndex = _.findIndex(rankings, {id: this.user.id});
                const loggedInUser = _.find(rankings, {id: this.user.id});
                let firstUserWithSameScoreIndex = _.findIndex(rankings, {courseScore: loggedInUser.courseScore});
                [rankings[loggedInUserIndex], rankings[firstUserWithSameScoreIndex]] = [rankings[firstUserWithSameScoreIndex], rankings[loggedInUserIndex]];

                /* Filter the rankings by country and get interesting users */
                const countryRanks =  getRanks(_.filter(_.cloneDeep(rankings), {'country_id': this.user.country_id}), this.user.id);
                this.countryRanks = countryRanks.ranks;

                /* Get interesting users globally */
                const worldRanks = getRanks(_.cloneDeep(rankings), this.user.id);
                this.worldRanks = worldRanks.ranks;

                /* User's ranks */
                this.userCountryRank = countryRanks.loggedInUser.rank;
                this.userWorldRank = worldRanks.loggedInUser.rank;
            },
            startPoll() {
                this.poller = setTimeout(this.refreshBoards, 10000)
            }
        },
        mounted() {
            this.refreshBoards();
        },
        beforeDestroy() {
            if (this.poller !== null) {
                clearTimeout(this.poller);
            }
        }
    }

    /**
     * Figures out which ranks to display
     * Returns the ranks for the board along with the loggedIn User's rank object
     * 
     * @param {Array} rankings
     * @param {Number} loggedInUserId
     * 
     * @return {Object}
     */
    function getRanks(rankings, loggedInUserId) {
        const loggedInUser = _.find(rankings, {id: loggedInUserId});

        rankings = _.forEach(rankings, (user, key) => {
            user.rank = key + 1;
            user.pointsDifference = user.courseScore - loggedInUser.courseScore;

            user.pointsDifference = (user.pointsDifference > 0) ? user.pointsDifference : false;

            if (user.id == loggedInUserId) {
                user.isLoggedInUser = true;
            }
        });
        
        /* Get the groups we are interested in */
        const topThree = _.take(rankings, 3);
        const bottomThree = _.takeRight(rankings, 3);

        const loggedInUserThree = (loggedInUser.rank == 1 || loggedInUser.rank == 2) ? topThree : _.slice(rankings, loggedInUser.rank - 2, loggedInUser.rank + 1);
        
        let topTier = [];
        let middleTier = [];
        let bottomTier = [];

        /* Manipulate results to show the rankings we are interested in */
        if (loggedInUser.rank <= 4) {
            // Top Tier
            topTier = [
                ...topThree,
                ...loggedInUserThree
            ];

            bottomTier = bottomThree;
        } else if (bottomThree[0].rank - loggedInUser.rank < 2 ) {
            bottomTier = [
                ...loggedInUserThree,
                ...loggedInUserThree
            ];

            topTier = topThree;
        } else {
            // Middle Tier
            topTier = topThree;
            bottomTier = bottomThree;
            middleTier = loggedInUserThree;
        }

        if (middleTier.length == 0) {
            // How many middle tier users needed
            const middleTierLength = 9 - topTier.length - bottomTier.length;

            // Get Median User
            const medianRank = topTier[topTier.length - 1].rank + Math.ceil((bottomTier[0].rank - topTier[topTier.length - 1].rank) / 2);
            middleTier.push(rankings[medianRank - 1]);

            // Fill out middle tier
            let i = 1;
            while (middleTierLength > 0) {
                middleTier = [
                    ...middleTier,
                    rankings[medianRank + i + 1]
                ];

                if (--medianTierLength == 0) {
                    break;
                }
                
                middleTier = [
                    rankings[medianRank - i + 1],
                    ...middleTier
                ]

                medianTierLength--;
                i++;
            }
        }

        middleTier[0].nonSequentialStart = true;
        middleTier[middleTier.length - 1].nonSequentialEnd = true;

        const ranks = [
            ...topTier,
            ...middleTier,
            ...bottomTier
        ];

        return {
            loggedInUser: loggedInUser,
            ranks: ranks
        };
    }
</script>
