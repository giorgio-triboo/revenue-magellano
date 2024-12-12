document.addEventListener("alpine:init", () => {
    Alpine.data("registrationForm", () => ({
        currentStep: 1,
        totalSteps: 6,
        publisherExists: false,
        formData: {
            country_code: "IT",
            vat_number: "",
            company_name: "",
            legal_name: "",
            county: "",
            city: "",
            postal_code: "",
            iban: "",
            swift: "",
            first_name: "",
            last_name: "",
            email: "",
            password: "",
            password_confirmation: "",
            privacy_accepted: false,
        },
        errors: {},
        showErrors: false,
        showPassword: false,
        showPasswordConfirm: false,
        isLoading: false,
        passwordChecks: {
            minLength: false,
            uppercase: false,
            lowercase: false,
            number: false,
            special: false,
        },

        get passwordsMatch() {
            if (!this.formData.password_confirmation) return true;
            return this.formData.password === this.formData.password_confirmation;
        },

        vatRules: {
            'IT': { length: 11, pattern: /^\d{11}$/ },
            'FR': { length: 11, pattern: /^[A-Z0-9]{2}\d{9}$/ },
            'DE': { length: 9, pattern: /^\d{9}$/ },
            'ES': { length: 9, pattern: /^[A-Z0-9]\d{7}[A-Z0-9]$/ },
            'CY': { length: 9, pattern: /^\d{9}$/ },
            'IE': { length: 7, pattern: /^\d{7}[A-Z]{1,2}$/ },
            'AA': { max: 30, pattern: /^[A-Za-z0-9]{1,30}$/ },
        },

        init() {
            this.$watch("currentStep", () => {
                this.errors = {};
                this.showErrors = false;
            });

            this.$watch('formData.password', () => {
                this.checkPasswordStrength();
            });

            this.$watch('formData.password_confirmation', () => {
                if (this.formData.password_confirmation && !this.passwordsMatch) {
                    this.errors.password_confirmation = "Le password non coincidono";
                } else {
                    delete this.errors.password_confirmation;
                }
            });
        },

        validateVatNumber(countryCode, number) {
            const rule = this.vatRules[countryCode];
            if (!rule) return false;
        
            const cleanNumber = number.replace(/[\s\-\.]/g, '');
            
            if (countryCode === 'AA') {
                return cleanNumber.length <= rule.max && rule.pattern.test(cleanNumber);
            }
        
            return cleanNumber.length === rule.length && rule.pattern.test(cleanNumber);
        },

        validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        validatePostalCode(cap) {
            return /^\d{5}$/.test(cap);
        },

        validateIban(iban) {
            return /^[A-Z0-9]{1,27}$/.test(iban);
        },

        validateSwift(swift) {
            return /^[A-Z0-9]{8,11}$/.test(swift);
        },

        validateTextOnly(text) {
            return /^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/.test(text);
        },

        checkPasswordStrength() {
            const password = this.formData.password;
            this.passwordChecks = {
                minLength: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password),
            };
        },

        allPasswordChecksPass() {
            return Object.values(this.passwordChecks).every(check => check === true);
        },

        async validateStep(step, isNextClick = false) {
            if (!isNextClick) return true;

            this.errors = {};
            this.showErrors = true;

            switch (step) {
                case 1:
                    if (!this.formData.country_code) {
                        this.errors.country_code = "Il codice paese è obbligatorio";
                        return false;
                    }
                    if (!this.formData.vat_number) {
                        this.errors.vat_number = "La partita IVA è obbligatoria";
                        return false;
                    }
                    if (!this.validateVatNumber(this.formData.country_code, this.formData.vat_number)) {
                        const rule = this.vatRules[this.formData.country_code];
                        this.errors.vat_number = `Il numero deve essere di ${rule.length} caratteri per ${this.formData.country_code}`;
                        return false;
                    }

                    try {
                        this.isLoading = true;
                        const response = await fetch("/api/check-vat", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                country_code: this.formData.country_code,
                                vat_number: this.formData.vat_number,
                            }),
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();
                        this.publisherExists = false;

                        if (data.exists) {
                            this.publisherExists = true;
                            if (data.publisher) {
                                Object.assign(this.formData, {
                                    company_name: data.publisher.company_name,
                                    legal_name: data.publisher.legal_name,
                                    county: data.publisher.county,
                                    city: data.publisher.city,
                                    postal_code: data.publisher.postal_code,
                                    iban: data.publisher.iban,
                                    swift: data.publisher.swift,
                                });
                                this.currentStep = 4;
                            }
                        }
                        return true;
                    } catch (error) {
                        console.error("Errore durante il controllo della partita IVA:", error);
                        this.errors.vat_number = "Errore durante la verifica della partita IVA";
                        return false;
                    } finally {
                        this.isLoading = false;
                    }
                    break;

                case 2:
                    if (!this.formData.company_name) {
                        this.errors.company_name = "Il nome azienda è obbligatorio";
                        return false;
                    }
                    if (!this.formData.legal_name) {
                        this.errors.legal_name = "La ragione sociale è obbligatoria";
                        return false;
                    }
                    break;

                case 3:
                    if (!this.formData.county) {
                        this.errors.county = "La provincia è obbligatoria";
                        return false;
                    }
                    if (!this.validateTextOnly(this.formData.county)) {
                        this.errors.county = "La provincia può contenere solo lettere";
                        return false;
                    }
                    if (!this.formData.city) {
                        this.errors.city = "La città è obbligatoria";
                        return false;
                    }
                    if (!this.validateTextOnly(this.formData.city)) {
                        this.errors.city = "La città può contenere solo lettere";
                        return false;
                    }
                    if (!this.formData.postal_code) {
                        this.errors.postal_code = "Il CAP è obbligatorio";
                        return false;
                    }
                    if (!this.validatePostalCode(this.formData.postal_code)) {
                        this.errors.postal_code = "Il CAP deve essere di 5 cifre numeriche";
                        return false;
                    }
                    break;

                case 4:
                    if (!this.formData.iban) {
                        this.errors.iban = "L'IBAN è obbligatorio";
                        return false;
                    }
                    if (!this.validateIban(this.formData.iban)) {
                        this.errors.iban = "L'IBAN deve essere di 27 caratteri";
                        return false;
                    }
                    if (!this.formData.swift) {
                        this.errors.swift = "Il codice SWIFT è obbligatorio";
                        return false;
                    }
                    if (!this.validateSwift(this.formData.swift)) {
                        this.errors.swift = "Il codice SWIFT deve essere tra 8 e 11 caratteri";
                        return false;
                    }
                    break;

                case 5:
                    if (!this.formData.first_name) {
                        this.errors.first_name = "Il nome è obbligatorio";
                        return false;
                    }
                    if (!this.formData.last_name) {
                        this.errors.last_name = "Il cognome è obbligatorio";
                        return false;
                    }
                    break;

                case 6:
                    if (!this.formData.email) {
                        this.errors.email = "L'email è obbligatoria";
                        return false;
                    }
                    if (!this.validateEmail(this.formData.email)) {
                        this.errors.email = "Inserire un indirizzo email valido";
                        return false;
                    }
                    if (!this.formData.password) {
                        this.errors.password = "La password è obbligatoria";
                        return false;
                    }
                    if (!this.allPasswordChecksPass()) {
                        this.errors.password = "La password non soddisfa i requisiti minimi";
                        return false;
                    }
                    if (!this.formData.password_confirmation) {
                        this.errors.password_confirmation = "La conferma password è obbligatoria";
                        return false;
                    }
                    if (!this.passwordsMatch) {
                        this.errors.password_confirmation = "Le password non coincidono";
                        return false;
                    }
                    if (!this.formData.privacy_accepted) {
                        this.errors.privacy = "Devi accettare l'informativa sulla privacy";
                        return false;
                    }
                    break;
            }

            return true;
        },

        async nextStep() {
            const isValid = await this.validateStep(this.currentStep, true);
            if (isValid) {
                if (this.currentStep < this.totalSteps) {
                    this.currentStep++;
                }
            }
        },

        prevStep() {
            if (this.currentStep === 1) {
                window.location.href = "/login";
            } else if (this.publisherExists && this.currentStep === 5) {
                this.currentStep = 1;
                this.showErrors = false;
            } else {
                this.currentStep--;
                this.showErrors = false;
            }
        },

        async submitForm() {
            const isValid = await this.validateStep(this.currentStep, true);
            if (!isValid) return;

            this.isLoading = true;
            try {
                const response = await fetch("/register", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.formData),
                });

                const data = await response.json();

                if (!response.ok) {
                    if (data.errors) {
                        Object.keys(data.errors).forEach((key) => {
                            this.errors[key] = data.errors[key][0];
                        });
                        return;
                    }
                    this.errors.general = data.message || "Si è verificato un errore";
                    return;
                }

                if (data.success) {
                    sessionStorage.setItem('registration_message', data.message);
                    const redirectUrl = new URL(data.redirect, window.location.origin);
                    redirectUrl.searchParams.append('registration_message', encodeURIComponent(data.message));
                    window.location.href = redirectUrl.toString();
                }

            } catch (error) {
                console.error("Errore durante la registrazione:", error);
                this.errors.general = "Si è verificato un errore durante la registrazione";
            } finally {
                this.isLoading = false;
            }
        },

        getStepStatus(step) {
            if (this.currentStep === step) return "current";
            if (this.currentStep > step) return "completed";
            return "upcoming";
        },

        get isFormValid() {
            return this.validateStep(this.currentStep);
        },

        get isLastStep() {
            return this.currentStep === this.totalSteps;
        },
    }));
});