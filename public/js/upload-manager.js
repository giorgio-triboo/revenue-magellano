function uploadManager() {
    return {
        // Stato dell'interfaccia
        isUploading: false,
        showPublishModal: false,
        showEmailModal: false,
        showExportModal: false,
        showErrorModal: false,
        showInfoModal: false,
        errorDetails: '',
        currentErrorUpload: null,
        currentInfoUpload: null,
        selectedUpload: null,
        selectedFile: null,
        pollingInterval: null,

        // Sistema di notifiche
        notifications: {
            show: false,
            type: null,
            message: '',
            timeout: null
        },

        // Form data
        form: {
            year: '',
            month: ''
        },

        // Data collections
        years: Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i),
        months: [
            'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
            'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'
        ],


        // Initialization
        init() {
            this.startPolling();
        },

        showNotification(type, message, autoDismiss = false) {
            if (this.notifications.timeout) {
                clearTimeout(this.notifications.timeout);
                this.notifications.timeout = null;
            }

            this.notifications = {
                show: true,
                type,
                message,
                timeout: null
            };

            // Se il tipo è success o error, imposta un timeout per il refresh della pagina
            if (type === 'success' || type === 'error') {
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            }

            if (autoDismiss) {
                this.notifications.timeout = setTimeout(() => {
                    this.notifications.show = false;
                }, 3000);
            }
        },

        handleFileChange(event) {
            this.selectedFile = event.target.files[0];
            this.notifications.show = false;
        },



        // Polling per gli aggiornamenti di stato
        startPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
            }
            this.pollingInterval = setInterval(async () => {
                await this.updateProcessingUploads();
            }, 5000);
        },

        stopPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
        },


        async updateProcessingUploads() {
            try {
                const response = await fetch('/uploads/list');
                if (!response.ok) {
                    throw new Error('Errore nell\'aggiornamento degli upload');
                }
                const processingUploads = await response.json();

                processingUploads.forEach(processingUpload => {
                    const uploadRow = document.querySelector(`tr[data-upload-id="${processingUpload.id}"]`);
                    if (uploadRow) {
                        const statusCell = uploadRow.querySelector('.status-cell');
                        if (statusCell) {
                            const statusBadge = statusCell.querySelector('.status-badge');
                            if (statusBadge) {
                                statusBadge.textContent = this.getStatusText(processingUpload.status);
                                statusBadge.className = 'status-badge px-2.5 py-0.5 inline-flex text-xs leading-5 font-medium rounded-xl';
                                const newClasses = this.getStatusClass(processingUpload.status);
                                Object.keys(newClasses).forEach(className => {
                                    if (newClasses[className]) {
                                        statusBadge.classList.add(className);
                                    }
                                });
                            }
                        }

                        if (processingUpload.status === 'processing') {
                            const progressBar = uploadRow.querySelector('.progress-bar');
                            const progressText = uploadRow.querySelector('.progress-text');
                            if (progressBar && progressText) {
                                progressBar.style.width = `${processingUpload.progress_percentage}%`;
                                progressText.textContent = `${Math.round(processingUpload.progress_percentage)}%`;
                            }
                        }

                        const recordsCell = uploadRow.querySelector('.records-cell');
                        if (recordsCell && processingUpload.status !== 'pending') {
                            recordsCell.textContent = `${processingUpload.processed_records} / ${processingUpload.total_records}`;
                        }
                    }
                });

                if (processingUploads.length === 0) {
                    this.stopPolling();
                }
            } catch (error) {
                console.error('Errore nell\'aggiornamento degli upload:', error);
            }
        },



        async uploadFile() {
            if (!this.selectedFile || !this.form.year || !this.form.month) {
                this.showNotification('error', 'Seleziona anno, mese e file prima di procedere');
                return;
            }

            this.isUploading = true;

            try {
                // Recupera il token CSRF dall'elemento meta o dal cookie
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                    document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN'))?.split('=')[1];

                if (!csrfToken) {
                    window.location.reload(); // Ricarica la pagina se non trova il token
                    return;
                }

                const formData = new FormData();
                formData.append('file', this.selectedFile);
                formData.append('year', this.form.year);
                formData.append('month', this.form.month);
                formData.append('_token', csrfToken);

                // Verifica che il token sia incluso nei dati
                console.log('Sending request with CSRF token:', csrfToken);

                const response = await fetch('/uploads', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'include', // Importante: include i cookies nella richiesta
                    body: formData
                });

                // Se riceviamo un 419 (CSRF token mismatch) o 401/403, ricarica la pagina
                if (response.status === 419 || response.status === 401 || response.status === 403) {
                    console.error(`Errore HTTP ${response.status}: Accesso negato o token CSRF non valido.`);
                    const errorDetails = await response.text();
                    console.error('Dettagli della risposta:', errorDetails);
                    // Rimuovi il refresh automatico
                    // window.location.reload();
                    return;
                }


                if (!response.ok) {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Errore durante il caricamento del file');
                    } else {
                        throw new Error('Errore durante il caricamento del file');
                    }
                }

                const data = await response.json();

                this.selectedFile = null;
                this.resetFileInput();
                this.showNotification('success', 'File caricato con successo. Inizio validazione...');
                this.startPolling();

            } catch (error) {
                console.error('Upload error:', error);
                this.showNotification('error', error.message);
            } finally {
                this.isUploading = false;
            }
        },


        resetFileInput() {
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.value = '';
            }
        },

        // Gestione modali
        confirmPublish(upload) {
            this.selectedUpload = upload;
            this.showPublishModal = true;
        },

        confirmSendEmail(upload) {
            this.selectedUpload = upload;
            this.showEmailModal = true;
        },

        openExportModal(upload) {
            this.selectedUpload = upload;
            this.showExportModal = true;
        },

        showInfo(upload) {
            this.currentInfoUpload = upload;
            this.showInfoModal = true;
        },

        closeInfoModal() {
            this.showInfoModal = false;
            this.currentInfoUpload = null;
        },

        // Azioni sugli upload
        async publishUpload() {
            if (!this.selectedUpload) return;

            try {
                const response = await fetch(`/uploads/${this.selectedUpload.id}/publish`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.message);

                this.showPublishModal = false;
                this.selectedUpload = null;
                this.showNotification('success', data.message || 'File pubblicato con successo');
                window.location.reload();
            } catch (error) {
                this.showNotification('error', error.message);
            }
        },

        async unpublishUpload() {
            if (!this.currentInfoUpload) return;

            try {
                const response = await fetch(`/uploads/${this.currentInfoUpload.id}/unpublish`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.message);

                this.closeInfoModal();
                this.showNotification('success', data.message || 'File rimosso dalla pubblicazione con successo');
                window.location.reload();
            } catch (error) {
                this.showNotification('error', error.message);
            }
        },

        async deleteUpload() {
            if (!this.currentInfoUpload) return;

            try {
                const response = await fetch(`/uploads/${this.currentInfoUpload.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.message);

                this.closeInfoModal();
                this.showNotification('success', data.message || 'File eliminato con successo');
                window.location.reload();
            } catch (error) {
                this.showNotification('error', error.message);
            }
        },

        async downloadExport(uploadId) {
            try {
                const response = await fetch(`/uploads/${uploadId}/export`);

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Errore durante il download del file AX');
                }

                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    throw new Error(data.message || 'Errore durante il download del file AX');
                }

                const contentDisposition = response.headers.get('content-disposition');
                let filename = 'export.tsv';
                if (contentDisposition) {
                    const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                    if (filenameMatch && filenameMatch[1]) {
                        filename = filenameMatch[1].replace(/['"]/g, '');
                    }
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                this.showExportModal = false;
            } catch (error) {
                this.showNotification('error', error.message);
            }
        },

        // async uploadToSftp(uploadId) {
        //     if (!uploadId) return;

        //     const timeout = 60000; // Timeout di 60 secondi
        //     const controller = new AbortController();
        //     const id = setTimeout(() => controller.abort(), timeout);

        //     try {
        //         const response = await fetch(`/uploads/${uploadId}/upload-sftp`, {
        //             method: 'POST',
        //             headers: {
        //                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        //                 'Accept': 'application/json',
        //                 'Content-Type': 'application/json'
        //             },
        //             signal: controller.signal // Aggiunto il controller per gestire il timeout
        //         });

        //         clearTimeout(id);

        //         if (!response.ok) {
        //             const contentType = response.headers.get('content-type');
        //             if (contentType && contentType.includes('text/html')) {
        //                 throw new Error('Risposta non valida dal server (HTML invece di JSON)');
        //             }
        //         }


        //         const data = await response.json();
        //         this.showNotification('success', data.message || 'File caricato su SFTP con successo');
        //         window.location.reload();
        //     } catch (error) {
        //         if (error.name === 'AbortError') {
        //             console.error('Timeout: Nessuna risposta dal server entro il tempo previsto.');
        //             this.showNotification('error', 'Timeout: Nessuna risposta dal server.');
        //         } else {
        //             console.error('Errore nella richiesta SFTP', error);
        //             this.showNotification('error', error.message);
        //         }
        //     }
        // },

        async uploadToSftp(uploadId) {
            if (!uploadId) return;

            const timeout = 30000; // Timeout di 30 secondi
            const controller = new AbortController();
            const id = setTimeout(() => controller.abort(), timeout);

            try {
                const response = await fetch(`/uploads/${uploadId}/upload-sftp`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    signal: controller.signal // Aggiunto il controller per gestire il timeout
                });

                clearTimeout(id);

                if (!response.ok) {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('text/html')) {
                        throw new Error('Risposta non valida dal server (HTML invece di JSON)');
                    } else if (contentType && contentType.includes('application/json')) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Errore durante l\'upload');
                    } else {
                        throw new Error('Errore durante l\'upload');
                    }
                }

                const data = await response.json();
                this.showNotification('success', data.message || 'File caricato su SFTP con successo');
                window.location.reload();
            } catch (error) {
                if (error.name === 'AbortError') {
                    console.error('Timeout: Nessuna risposta dal server entro il tempo previsto.');
                    this.showNotification('error', 'Timeout: Nessuna risposta dal server.');
                } else {
                    console.error('Errore nella richiesta SFTP', error);
                    this.showNotification('error', error.message);
                }
            }
        },

        async sendTestEmail() {
            if (!this.selectedUpload) return;

            try {
                const response = await fetch(`/uploads/${this.selectedUpload.id}/send-test-email`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.message);

                this.showEmailModal = false;
                this.selectedUpload = null;
                this.showNotification('success', data.message || 'Email di test inviata con successo');
            } catch (error) {
                this.showNotification('error', error.message);
            }
        },

        async sendEmail() {
            if (!this.selectedUpload) return;

            try {
                const response = await fetch(`/uploads/${this.selectedUpload.id}/send-email`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.message);

                this.showEmailModal = false;
                this.selectedUpload = null;
                this.showNotification('success', data.message || 'Email inviata con successo');
            } catch (error) {
                this.showNotification('error', error.message);
            }
        },

        // Utility functions
        getStatusClass(status) {
            return {
                'bg-yellow-100 text-yellow-800': status === 'processing',
                'bg-green-100 text-green-800': status === 'completed',
                'bg-blue-100 text-blue-800': status === 'published',
                'bg-red-100 text-red-800': status === 'error',
                'bg-gray-100 text-gray-800': status === 'pending'
            };
        },


        getAxExportStatusClass(upload) {
            if (!upload) return 'bg-gray-100 text-gray-800';
            if (!upload.ax_export_status) return 'bg-gray-100 text-gray-800';

            if (upload.ax_export_status === 'processing') return 'bg-yellow-100 text-yellow-800';
            if (upload.ax_export_status === 'completed') return 'bg-green-100 text-green-800';
            if (upload.ax_export_status === 'error') return 'bg-red-100 text-red-800';
            if (upload.status === 'error') return 'bg-red-100 text-red-800';
            return 'bg-gray-100 text-gray-800';
        },

        getSftpStatusClass(upload) {
            if (!upload) return 'bg-gray-100 text-gray-800';
            if (!upload.sftp_status) return 'bg-gray-100 text-gray-800';

            switch (upload.sftp_status) {
                case 'processing':
                    return 'bg-yellow-100 text-yellow-800';
                case 'completed':
                    return 'bg-green-100 text-green-800';
                case 'error':
                    return 'bg-red-100 text-red-800';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        },

        getAxExportStatusText(upload) {
            if (!upload) return 'N/D';
            if (!upload.ax_export_status) return 'In attesa';

            if (upload.ax_export_status === 'processing') return 'In elaborazione';
            if (upload.ax_export_path) return 'Completato';
            if (upload.ax_export_status === 'error') return 'Errore';
            if (upload.status === 'error') return 'Errore';
            return 'In attesa';
        },

        getSftpStatusText(upload) {
            if (!upload) return 'N/D';
            if (!upload.sftp_status) return 'Non caricato';

            const statusMap = {
                'processing': 'In caricamento',
                'completed': 'Caricato',
                'error': 'Errore'
            };
            return statusMap[upload.sftp_status] || upload.sftp_status;
        },

        canDownloadAxExport(upload) {
            return upload.ax_export_path && upload.status === 'completed';
        },

        getStatusText(status) {
            const statusMap = {
                'pending': 'In validazione',
                'processing': 'In elaborazione',
                'completed': 'Completato',
                'published': 'Pubblicato',
                'error': 'Errore'
            };
            return statusMap[status] || status;
        }
        ,

        formatDateTime(dateString) {
            if (!dateString) return 'N/D';
            return new Date(dateString).toLocaleString('it-IT', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatErrorDetails() {
            // Se non ci sono informazioni sull'upload corrente
            if (!this.currentInfoUpload) {
                return {
                    errorCount: 0,
                    errorLines: [],
                    generalError: 'Nessuna informazione disponibile'
                };
            }

            let errorDetails = {
                errorCount: 0,
                errorLines: [],
                generalError: null
            };

            // Gestione errore generale dal messaggio di errore principale
            if (this.currentInfoUpload.error_message) {
                errorDetails.generalError = this.currentInfoUpload.error_message;
                errorDetails.errorCount++;
                errorDetails.errorLines.push({
                    type: 'general',
                    line: 'Generale',
                    message: this.currentInfoUpload.error_message
                });
            }

            // Gestione errori specifici dai processing_stats
            if (this.currentInfoUpload.processing_stats?.error_details?.length > 0) {
                this.currentInfoUpload.processing_stats.error_details.forEach(error => {
                    errorDetails.errorCount++;
                    errorDetails.errorLines.push({
                        type: 'specific',
                        line: error.line,
                        message: error.error || error.message
                    });
                });
            }

            // Se non sono stati trovati errori ma lo stato è 'error'
            if (errorDetails.errorCount === 0 && this.currentInfoUpload.status === 'error') {
                errorDetails.generalError = 'Si è verificato un errore durante l\'elaborazione del file. Contattare il supporto tecnico.';
                errorDetails.errorCount = 1;
                errorDetails.errorLines.push({
                    type: 'general',
                    line: 'Generale',
                    message: 'Errore non specificato'
                });
            }

            return errorDetails;
        },
    };
}