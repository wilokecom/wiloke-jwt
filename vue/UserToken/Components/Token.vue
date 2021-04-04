<template>
    <div>This is my test</div>
</template>
<script>
export default {
    name: "Token",
    props: {
        created_at: '',
        name: '',
        token: ''
    },
    setup() {
        return {
            columns: [
                {
                    title: 'Name',
                    created_at: 'Created at',
                    token: 'Token',
                    action: 'Action'
                }
            ]
        };
    }
};
</script>