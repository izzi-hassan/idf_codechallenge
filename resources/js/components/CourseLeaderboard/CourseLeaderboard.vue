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

    export default {
        name: 'CourseLeaderBoard',
        components: {
            categoryBoard: CategoryBoard
        },
        props: ['loggedInUser', 'courseId'],
        data() {
            return {
                user: {
                    country: {
                        name: ''
                    }
                },
                userCountryRank: 0,
                userWorldRank: 0,
                countryRanks: {},
                worldRanks: {}
            };
        },
        methods: {
            getUser() {
                axios.get('/user')
                .then(response => {
                    this.user = response.data;
                });
            },
            refreshBoards() {
                axios.get('/api/course/' + this.courseId + '/leaderboard')
                .then(response => {
                    this.filterRanks(response.data);
                })
                .catch(error => {
                    console.log(error);
                });
            },
            filterRanks(rankings) {
                /* Filter the rankings by country and get interesting users */
                let countryRanks =  getRanks(_.filter(_.cloneDeep(rankings), {'country_id': this.user.country_id}), this.user.id);
                
                /* Get interesting users globally */
                let worldRanks = getRanks(_.cloneDeep(rankings), this.user.id);

                this.countryRanks = [
                    ...countryRanks.topTier,
                    ...countryRanks.middleTier,
                    ...countryRanks.bottomTier
                ];
                
                this.worldRanks = [
                    ...worldRanks.topTier,
                    ...worldRanks.middleTier,
                    ...worldRanks.bottomTier
                ];

                /* User's ranks */
                this.userCountryRank = countryRanks.loggedInUser.rank;
                this.userWorldRank = worldRanks.loggedInUser.rank;
            }
        },
        mounted() {
            this.getUser();
            this.refreshBoards();
        }
    }

    function getRanks(rankings, loggedInUserId) {
        rankings = _.forEach(rankings, function(item, key) {
            item.rank = key + 1;
            item.pointsDifference = (key == 0) ? 0 : item.pointsDifference = rankings[key - 1].courseScore - item.courseScore;
        });
        
        /* Get the groups we are interested in */
        const topThree = _.take(rankings, 3);
        const bottomThree = _.takeRight(rankings, 3);
        const loggedInUser = _.find(rankings, {id: loggedInUserId});

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
            // Need Median
            const middleTierLength = 9 - topTier.length - bottomTier.length;

            const medianRank = Math.ceil(bottomTier[0].rank - topTier[topTier.length - 1].rank / 2);
            middleTier.push(rankings[--medianRank]);

            // Fill out middle tier
            let i = 1;
            while (middleTierLength > 0) {
                middleTier = [
                    ...middleTier,
                    rankings[medianRank + i]
                ];

                if (--medianTierLength == 0) {
                    break;
                }
                
                middleTier = [
                    rankings[medianRank - i],
                    ...middleTier
                ]

                medianTierLength--;
                i++;
            }
        }

        console.log('Logged In Three', loggedInUserThree);
        console.log('Top Tier', topTier);
        console.log('Middle Tier', middleTier);
        console.log('Bottom Tier', bottomTier);

        return {
            loggedInUser: loggedInUser,
            topTier: topTier,
            middleTier: middleTier,
            bottomTier: bottomTier
        };
    }
</script>
