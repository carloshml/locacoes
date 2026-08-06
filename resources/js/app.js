import './bootstrap';
import { createApp } from 'vue';

// Importar componentes
import ClientesList from './components/ClientesList.vue';
import ClientesCreate from './components/ClientesCreate.vue';
import ClienteRead from './components/ClienteRead.vue';
import UltimosClientes from './components/UltimosClientes.vue';
import UsersList from './components/UsersList.vue';
import UsersCreateUpdate from './components/UsersCreateUpdate.vue';
import UserRead from './components/UserRead.vue';
import UserProfile from './components/UserProfile.vue';
import ActivitiesLog from './components/ActivitiesLog.vue';
import AuthLogin from './components/AuthLogin.vue';
import AuthRegister from './components/AuthRegister.vue';
import ItemsList from './components/ItemsList.vue';
import ItemsCreate from './components/ItemsCreate.vue';
import ItemsRead from './components/ItemsRead.vue';
import LocacoesList from './components/LocacoesList.vue';
import LocacoesCreate from './components/LocacoesCreate.vue';
import LocacaoRead from './components/LocacaoRead.vue';
import FaturamentoLocacoes from './components/FaturamentoLocacoes.vue';

// Função helper para fazer fetch com CSRF
window.apiFetch = function(url, options = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const apiToken = localStorage.getItem('api_token');
    
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...options.headers
    };
    
    if (token && !url.includes('/api/')) {
        headers['X-CSRF-TOKEN'] = token;
    }
    
    if (apiToken) {
        headers['Authorization'] = `Bearer ${apiToken}`;
    }
    
    return fetch(url, {
        ...options,
        headers
    });
};

const app = createApp({});

// Registrar componentes
app.component('clientes-list', ClientesList);
app.component('clientes-create-update', ClientesCreate);
app.component('cliente-read', ClienteRead);
app.component('ultimas-pessoas', UltimosClientes);
app.component('users-list', UsersList);
app.component('users-create-update', UsersCreateUpdate);
app.component('user-read', UserRead);
app.component('user-profile', UserProfile);
app.component('activities-log', ActivitiesLog);
app.component('auth-login', AuthLogin);
app.component('auth-register', AuthRegister);
app.component('items-list', ItemsList);
app.component('items-create', ItemsCreate);
app.component('items-read', ItemsRead);
app.component('locacoes-list', LocacoesList);
app.component('locacoes-create', LocacoesCreate);
app.component('locacao-read', LocacaoRead);
app.component('faturamento-locacoes', FaturamentoLocacoes);

app.mount('#app');