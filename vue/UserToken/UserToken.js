import Vue from "vue";
import VueCompositionAPI from '@vue/composition-api';
import TokensManagement from "./Components/TokensManagement.vue";
import 'ant-design-vue/dist/antd.css';
Vue.use(VueCompositionAPI);
Vue.use(Antd);
import Antd from 'ant-design-vue';
const App = new Vue({
  render: h => h(TokensManagement)
}).$mount('#wiloke-jwt-user-token');
