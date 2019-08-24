<template>
    <ul>
        <div v-for="slot in slots" 
            :key="slot.index"
        >
            <hr v-if="slot.nonSequentialStart" />
            <li class="courseRanking__rankItem">
                <div class="position">
                    {{ slot.rank }}
                </div>
                <div class="info">
                    <div :class="{'isLoggedInUser': slot.isLoggedInUser}">
                        {{ slot.name }}
                    </div>
                    <div class="score">
                        {{ slot.courseScore }} PTS
                        <span v-if="slot.pointsDifference">(+{{ slot.pointsDifference }})</span>
                    </div>
                </div>
            </li>
            <hr v-if="slot.nonSequentialEnd" />
        </div>
    </ul>
</template>

<style scoped>
    ul {
        padding: 0px;
    }
    
    .courseRanking__rankItem {
        display: flex;
        flex-direction: row;
        padding: 10px;
    }

    .position {
        font-size: 28px;
        color: rgb(132, 132, 132);
        text-align: right;
        width: 80px;
        padding-right: 10px;
    }
    .isLoggedInUser {
        font-weight: bold;
    }

    .info {
        font-size: 16px;
    }

    .score {
        font-size: 10px;
        color: rgb(132, 132, 132);
    }
</style>

<script>
    export default {
        name: 'CategoryBoard',
        props: {
            slots: {
                type: Array,
                required: true
            }
        }
    }
</script>
