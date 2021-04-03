<template>
    <div id="wiloke-jwt-management">
        <add-token v-if="!myTokens.length" v-on:create-token="handleCreateToken" />
        <tokens :my-tokens="myTokens" :is-loading="isLoading" v-on:revoke-token="handleRevokeToken" />
    </div>
</template>
<script>
import AddToken from "./AddToken.vue";
import Tokens from "./Tokens.vue";

export default {
    name: "tokens-management",
    data() {
        return {
            myTokens: [],
            isLoading: true
        }
    },
    components: {
        AddToken,
        Tokens
    },
    mounted() {
        this.fetchMyTokens();
    },
    methods: {
        handleRevokeToken(record) {
            this.isLoading = true;
            jQuery.ajax({
                url: `${WILOKE_JWT.ajaxurl}`,
                data: {
                    action: 'revoke_my_token',
                    userId: WILOKE_JWT.currentUserId
                },
                success: response => {
                    if (response.success) {
                        this.myTokens = [...response.data.items];
                    } else {
                        alert(response.data.msg);
                    }
                    this.isLoading = false;
                }
            })
        },
        fetchMyTokens() {
            this.isLoading = true;
            const response = fetch(`${WILOKE_JWT.ajaxurl}?action=fetch_my_tokens&userId=${WILOKE_JWT.currentUserId}`, {
                method: 'GET', // *GET, POST, PUT, DELETE, etc.
                credentials: "same-origin", // include, *same-origin, omit
                headers: {
                    'Content-Type': 'application/json'
                    // 'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(items => {
                this.myTokens = !!items.items ? [...items.items]: [];
                this.isLoading = false;
            }).catch(() => this.isLoading = false);
        },
        handleCreateToken() {
            this.isLoading = true;
            jQuery.ajax({
                url: `${WILOKE_JWT.ajaxurl}`,
                data: {
                    action: 'create_token',
                    userId: WILOKE_JWT.currentUserId
                },
                success: response => {
                    if (response.success) {
                        this.myTokens = [...response.data.items];
                    } else {
                        alert(response.data.msg);
                    }
                    this.isLoading = false;
                }
            })
        }
    }
}
</script>