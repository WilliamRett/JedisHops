import AllPatient from './components/AllPatient.vue';
import CreatePatient from './components/CreatePatient.vue';
import EditPatient from './components/EditPatient.vue';
 
export const routes = [
    {
        name: 'home',
        path: '/',
        component: AllPatient
    },
    {
        name: 'create',
        path: '/create',
        component: CreatePatient
    },
    {
        name: 'edit',
        path: '/edit/:id',
        component: EditPatient
    }
];