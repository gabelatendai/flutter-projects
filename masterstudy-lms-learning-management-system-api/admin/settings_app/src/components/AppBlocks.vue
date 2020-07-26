<template>
    <div class="stm_lms_app_blocks">

        <div class="inner" v-bind:class="{'loading' : loading}">

            <h3>App Home Page Layout</h3>

            <div class="list-group">
                <draggable v-model="blocks"
                           v-bind="dragOptions"
                           @start="dragging = true"
                           @end="dragging = false">

                    <div v-for="element in blocks" :key="element.id" class="list-group-item"
                         v-bind:class="{'enabled' : element.enabled}">
                        <div>
                            <label>{{element.name}}</label>
                            <span @click="element.enabled = !element.enabled" class="enableBlock">enabled</span>
                        </div>
                    </div>
                </draggable>
            </div>

            <button type="button" class="btn btn-success" @click="saveBlocks">Save order</button>
        </div>

    </div>
</template>

<script>

    import draggable from 'vuedraggable'

    export default {
        name: 'AppBlocks',
        components: {
            draggable,
        },
        data: function () {
            return {
                loading: true,
                endpoints: stm_lms_api_vars,
                dragging: false,
                blocks: []
            }
        },
        methods: {
            getBlocks: function () {
                let _this = this;
                _this.axios.get(_this.endpoints.get_blocks).then(resp => {
                    _this.$set(_this, 'blocks', resp.data);
                    _this.loading = false;
                });
            },
            saveBlocks: function () {
                let _this = this;
                _this.loading = true;
                _this.axios.post(_this.endpoints.save_blocks, _this.blocks).then(resp => {
                    _this.loading = false;
                });
            }
        },
        mounted: function () {
            this.getBlocks();
        },
        computed: {
            dragOptions() {
                return {
                    animation: 200,
                    group: "description",
                    disabled: false,
                    ghostClass: "ghost"
                };
            }
        },
    }
</script>