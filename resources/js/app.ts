import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import 'primeicons/primeicons.css'

import Button from "primevue/button"
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';   // optional
import Row from 'primevue/row';
import Tag from 'primevue/tag';
import Rating from 'primevue/rating';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import ToastService from 'primevue/toastservice';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import RadioButton from 'primevue/radiobutton';
import RadioButtonGroup from 'primevue/radiobuttongroup';
import FileUpload from 'primevue/fileupload';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import DatePicker from 'primevue/datepicker';
import ToastService from 'primevue/toastservice';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: Aura
                }
            })
            .use(ToastService)
            // Register all PrimeVue components used in DataTable.vue
            .component('Toast',            import('primevue/toast'))
            .component('Button',           Button)
            .component('DataTable',        DataTable)
            .component('Column',           Column)
            .component('ColumnGroup',      ColumnGroup)
            .component('Row',              Row)
            .component('Tag',              Tag)
            .component('Rating',              Rating)
            .component('Toolbar',              Toolbar)
            .component('Dialog',              Dialog)
            .component('InputText',              InputText)
            .component('InputNumber',              InputNumber)
            .component('Textarea',              Textarea)
            .component('Select',              Select)
            .component('RadioButton',              RadioButton)
            .component('RadioButtonGroup',              RadioButtonGroup)
            .component('FileUpload',              FileUpload)
            .component('IconField',              IconField)
            .component('InputIcon',              InputIcon)
            .component('DatePicker',              DatePicker)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
