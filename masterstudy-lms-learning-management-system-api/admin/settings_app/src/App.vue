<template>
    <div id="app">
        <AppBlocks />
    </div>
</template>

<script>
    import AppBlocks from './components/AppBlocks.vue'

    export default {
        name: 'App',
        components: {
            AppBlocks
        }
    }
</script>

<style lang="scss">
    @import "assets/scss/main";
</style>