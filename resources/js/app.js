import './bootstrap';
import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';

console.log('App.js è stato caricato');



// Definisci funzioni/componenti Alpine globali
window.flashMessages = () => ({
    successMessage: '',
    errorMessage: '',
});

// Definisci Alpine globalmente
window.Alpine = Alpine;

// Definisci i componenti base di Alpine
document.addEventListener('alpine:init', () => {
    Alpine.data('sidebarState', () => ({
        sidebarOpen: false
    }));
    
    // Altri componenti Alpine che potrebbero servirti
    Alpine.data('formState', () => ({
        isSubmitting: false
    }));
});

// Inizializza Alpine
document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
    
    // Inizializza le icone Lucide
    createIcons({
        icons: icons
    });
});

// Debug Logger
const debugLogger = (message, type = 'info') => {
    if (process.env.NODE_ENV === 'development') {
        const styles = {
            info: 'color: #4d78ff; font-weight: bold;',
            error: 'color: #ff4d4d; font-weight: bold;',
            warning: 'color: #ffc107; font-weight: bold;'
        };
        console.log(`%c[Debug ${type.toUpperCase()}]:`, styles[type], message);
    }
};

window.debugLogger = debugLogger;

// Log di conferma
debugLogger('App initialized', 'info');