/**
 * Publishers Index JavaScript Module
 * Enhanced version with improved search and debugging capabilities
 */

export function publishersIndex(initialData = {}) {
    return {
        // State Management
        loading: false,
        searchQuery: initialData.search || '',
        searchTimeout: null,
        showFilters: false,
        searchExpanded: false,
        
        // Filters state
        filters: {
            search: initialData.search || '',
            status: initialData.status || '',
            sort: initialData.sort || 'company_name',
            direction: initialData.direction || 'asc',
            date_from: initialData.date_from || '',
            date_to: initialData.date_to || ''
        },

        // Configuration
        config: {
            debugMode: true,
            minSearchLength: 2,
            searchDelay: 300, // ms
            errorDuration: 3000, // ms
        },

        /**
         * Component initialization
         */
        init() {
            this.debugLog('Initializing component with data:', initialData);
            this.attachEventListeners();
            this.initializeSearchState();
        },

        /**
         * Initialize search state based on URL parameters
         */
        initializeSearchState() {
            const urlParams = new URLSearchParams(window.location.search);
            this.searchExpanded = !!urlParams.get('search');
            this.debugLog('Search state initialized:', { expanded: this.searchExpanded });
        },

        /**
         * Event listeners attachment
         */
        attachEventListeners() {
            this.debugLog('Attaching event listeners');
            
            // Keyboard events
            window.addEventListener('keydown', (e) => {
                // Global search shortcut (Ctrl/Cmd + K)
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.focusSearch();
                }
                
                // Enter key in search field
                if (e.key === 'Enter' && document.activeElement.id === 'search') {
                    e.preventDefault();
                    this.handleSearch();
                }
                
                // Escape key to clear search
                if (e.key === 'Escape' && this.searchQuery) {
                    e.preventDefault();
                    this.clearSearch();
                }
            });
        },

        /**
         * Focus search input
         */
        focusSearch() {
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.focus();
                this.searchExpanded = true;
            }
        },

        /**
         * Handle search input changes with debounce
         * @param {Event} event - Input event
         */
        handleSearchInput(event) {
            clearTimeout(this.searchTimeout);
            const value = event.target.value;
            
            this.searchTimeout = setTimeout(() => {
                this.searchQuery = value;
                if (value.length >= this.config.minSearchLength || value.length === 0) {
                    this.handleSearch();
                }
            }, this.config.searchDelay);
        },

        /**
         * Handle search form submission
         */
        handleSearch() {
            this.debugLog('Search initiated:', { query: this.searchQuery });
            
            // Validate search query
            if (this.searchQuery.length < this.config.minSearchLength && this.searchQuery.length > 0) {
                this.showError(`La ricerca deve contenere almeno ${this.config.minSearchLength} caratteri`);
                return;
            }

            this.filters.search = this.searchQuery;
            this.debugLog('Filters updated:', this.filters);
            this.applyFilters();
        },

        /**
         * Clear search and reset filters
         */
        clearSearch() {
            this.debugLog('Clearing search');
            this.searchQuery = '';
            this.searchExpanded = false;
            this.resetFilters();
        },

        /**
         * Reset all filters to default values
         */
        resetFilters() {
            this.debugLog('Resetting filters');
            
            this.filters = {
                search: '',
                status: '',
                sort: 'company_name',
                direction: 'asc',
                date_from: '',
                date_to: ''
            };
            
            this.applyFilters();
        },

        /**
         * Apply current filters and update URL
         */
        applyFilters() {
            this.debugLog('Applying filters');
            this.loading = true;

            try {
                const queryParams = new URLSearchParams();
                
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value) queryParams.set(key, value);
                });

                const queryString = queryParams.toString();
                this.debugLog('Generated query:', queryString);

                // Update URL and reload
                window.location.search = queryString;
            } catch (error) {
                this.debugLog('Filter error:', error);
                this.showError('Errore nell\'applicazione dei filtri');
            }
        },

        /**
         * Handle sorting
         * @param {string} field - Field to sort by
         */
        sort(field) {
            this.debugLog('Sorting:', { field });
            
            if (this.filters.sort === field) {
                this.filters.direction = this.filters.direction === 'asc' ? 'desc' : 'asc';
            } else {
                this.filters.sort = field;
                this.filters.direction = 'asc';
            }

            this.applyFilters();
        },

        /**
         * Check for active filters
         */
        get hasActiveFilters() {
            return Object.entries(this.filters).some(([key, value]) => 
                value && key !== 'sort' && key !== 'direction'
            );
        },

        /**
         * Get active filters
         */
        get activeFilters() {
            return Object.fromEntries(
                Object.entries(this.filters)
                    .filter(([key, value]) => value && !['sort', 'direction'].includes(key))
            );
        },

        /**
         * Format filter label
         * @param {string} key - Filter key
         * @param {string} value - Filter value
         */
        formatFilterLabel(key, value) {
            const labels = {
                search: 'Ricerca',
                status: 'Stato',
                date_from: 'Data da',
                date_to: 'Data a'
            };

            if (key === 'status') {
                return `${labels[key]}: ${value === 'active' ? 'Attivo' : 'Non attivo'}`;
            }

            return `${labels[key]}: ${value}`;
        },

        /**
         * Remove specific filter
         * @param {string} key - Filter key
         */
        removeFilter(key) {
            this.debugLog('Removing filter:', key);
            this.filters[key] = '';
            this.applyFilters();
        },

        /**
         * Export data with current filters
         */
        async exportData() {
            this.debugLog('Starting export');
            this.loading = true;

            try {
                const queryParams = new URLSearchParams(this.filters).toString();
                window.location.href = `/publishers/export?${queryParams}`;
            } catch (error) {
                this.debugLog('Export error:', error);
                this.showError('Errore durante l\'esportazione');
            } finally {
                this.loading = false;
            }
        },

        /**
         * Show error message
         * @param {string} message - Error message
         */
        showError(message) {
            const errorContainer = document.createElement('div');
            errorContainer.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50';
            errorContainer.role = 'alert';
            errorContainer.innerHTML = message;
            
            document.body.appendChild(errorContainer);
            this.debugLog('Error shown:', message);

            setTimeout(() => {
                errorContainer.remove();
            }, this.config.errorDuration);
        },

        /**
         * Debug logging
         * @param {string} message - Log message
         * @param {Object} [data] - Additional data to log
         */
        debugLog(message, data = null) {
            if (this.config.debugMode) {
                if (data) {
                    console.log(`[Publishers] ${message}`, data);
                } else {
                    console.log(`[Publishers] ${message}`);
                }
            }
        }
    };
}