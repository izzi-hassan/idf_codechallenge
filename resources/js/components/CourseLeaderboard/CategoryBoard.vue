<template>
    <ul style="padding: 0px;">
        <li v-for="slot in slots" :key="slot.id"
        class="courseRanking__rankItem"
        style="display: flex; flex-direction: row; padding: 10px;">
            <hr v-if="slot.isNonSequential" />
            <div class="position"
                style="font-size: 28px; color: rgb(132, 132, 132); text-align: right; width: 80px; padding-right: 10px;">
                {{ slot.rank }}
            </div>
            <div class="info">
                <div style="font-size: 16px;"
                    :class="{'isLoggedInUser': slot.isLoggedInUser}"
                >
                    {{ slot.name }}
                </div>
                <div class="score" style="font-size: 10px; color: rgb(132, 132, 132);">
                    {{ slot.courseScore }} PTS (+{{ slot.pointsDifference }})
                </div>
            </div>
            <hr v-if="slot.isNonSequential" />
        </li>
    </ul>
</template>

<style scoped>
    .isLoggedInUser {
        font-weight: bold;
    }
</style>

<script>
    export default {
        name: 'CategoryBoard',
        props: ['slots']
    }
</script>
