import { createApp } from "vue";
import axios from 'axios';
import lodash from 'lodash-es';

import moment from 'moment/locale/nl';

import DeliveryDateComponent from './DeliveryDateComponent.vue';
import YoutubeWrapper from './YoutubeWrapper';


window.axios = axios;
window.lodash = lodash;
window.moment = moment;

const app = createApp({});
app.component('delivery-date', DeliveryDateComponent);
app.component('ef2-youtube-wrapper', YoutubeWrapper);
app.mount('#page-wrapper');
