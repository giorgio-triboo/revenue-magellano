document.addEventListener('alpine:init', () => {
    Alpine.data('passwordResetForm', () => ({
        password: '',
        passwordConfirmation: '',
        showPassword: false,
        showPasswordConfirm: false,
        isLoading: false,
        errors: {},
        passwordChecks: {
            minLength: false,
            uppercase: false,
            lowercase: false,
            number: false,
            special: false
        },

        init() {
            // Inizializza i watchers
            this.$watch('password', () => {
                this.checkPasswordStrength();
                this.validatePasswordMatch();
            });
            
            this.$watch('passwordConfirmation', () => {
                this.validatePasswordMatch();
            });
        },

        checkPasswordStrength() {
            const password = this.password;
            this.passwordChecks = {
                minLength: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };
        },

        validatePasswordMatch() {
            if (this.passwordConfirmation && this.password !== this.passwordConfirmation) {
                this.errors.password_confirmation = 'Le password non coincidono';
            } else {
                delete this.errors.password_confirmation;
            }
        },

        allPasswordChecksPass() {
            return Object.values(this.passwordChecks).every(check => check === true);
        },

        async validateForm(event) {
            this.errors = {};
            
            // Validazione password
            if (!this.password) {
                this.errors.password = 'La password è obbligatoria';
            } else if (!this.allPasswordChecksPass()) {
                this.errors.password = 'La password non soddisfa i requisiti minimi';
            }

            // Validazione conferma password
            if (!this.passwordConfirmation) {
                this.errors.password_confirmation = 'La conferma password è obbligatoria';
            } else if (this.password !== this.passwordConfirmation) {
                this.errors.password_confirmation = 'Le password non coincidono';
            }

            // Se ci sono errori, ferma la submission
            if (Object.keys(this.errors).length > 0) {
                event.preventDefault();
                return;
            }

            // Attiva loading state
            this.isLoading = true;

            try {
                const form = event.target;
                const formData = new FormData(form);

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok) {
                    this.errors = data.errors || { general: data.message };
                    event.preventDefault();
                    return;
                }

                // Redirect in caso di successo
                if (data.redirect) {
                    window.location.href = data.redirect;
                }

            } catch (error) {
                console.error('Errore durante il reset della password:', error);
                this.errors = { general: 'Si è verificato un errore durante il reset della password' };
                event.preventDefault();
            } finally {
                this.isLoading = false;
            }
        },

        get isValid() {
            return this.allPasswordChecksPass() && 
                   this.password === this.passwordConfirmation &&
                   this.password.length > 0 &&
                   this.passwordConfirmation.length > 0;
        }
    }));
});