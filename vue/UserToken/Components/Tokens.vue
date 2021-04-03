<template>
  <a-table id="wiloke-jwt-tokens-management" :loading="isLoading" :columns="columns" :data-source="myTokens" :row-key="record=>record.app_id" bordered>
    <span slot="roles" slot-scope="{roles, record}">
        <a-tag v-for="role in roles" :key="`${record.app_name}${role}`" color="blue">{{ role }}</a-tag>
    </span>
    <span slot="action">
        <a-button type="danger" href="#" @click.prevent="handleRevokeToken">Revoke</a-button>
    </span>
  </a-table>
</template>
<style>
  #wiloke-jwt-tokens-management .ant-table-tbody td {
    max-width: 300px  !important;
  }
</style>
<script>
const columns = [
  {
    dataIndex: 'app_id',
    key: 'app_id',
    title: 'ID'
  },
  {
    dataIndex: 'app_name',
    key: 'app_name',
    title: 'App Name'
  },
  {
    title: 'Access Token',
    dataIndex: 'access_token',
    key: 'access_token',
  },
  {
    title: 'Refresh Token',
    dataIndex: 'refresh_token',
    key: 'refresh_token',
  },
  {
    title: 'Roles',
    dataIndex: 'roles',
    key: 'roles',
    scopedSlots: { customRender: 'roles' },
  },
  {
    title: 'Action',
    dataIndex: 'action',
    key: 'action',
    scopedSlots: { customRender: 'action' },
  },
];

export default {
    data() {
        return {
            columns
        };
    },
    props: {
      isLoading: {
        type: Boolean,
        default: false
      },
      myTokens: {
        type: Array,
        default: function(){
          return [];
        }
      }
    },
    computed: {
        permissionOptions: () => {
            return this.fetchPermissions();
        }
    },
    methods: {
        handleRevokeToken() {
          this.$emit('revoke-token');
        },
        async fetchPermissions() {
            const response = await fetch(`${WILOKE_JWT.restAPI}/roles`);
            if (!response.ok) {
                return [];
            }

            const permissions = await response.json();

            return [...permissions.items];
        }
    }
}
</script>