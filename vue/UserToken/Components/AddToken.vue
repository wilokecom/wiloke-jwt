<template>
  <a-form layout="horizontal" :model="formState">
    <a-form-item label="Application Name">
      <a-input v-model="formState.app_name" placeholder="input placeholder" />
    </a-form-item>
    <a-form-item>
      <a-button v-if="!!formState.app_name" type="primary" @click.prevent="handleCreateToken">Submit</a-button>
    </a-form-item>
  </a-form>
</template>
<script>
export default {
    name: 'add-token',
    data() {
        return {
            formState: {
                app_name: '',
                capabilities: []
            }
        }
    },
    methods: {
        handleCreateToken() {
          this.$emit('create-token', this.formState);
        }
    }
};
</script>