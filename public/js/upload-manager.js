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

            if (autoDismiss) {
                this.notifications.timeout = setTimeout(() => {
                    this.notifications.show = false;
                }, 5000);
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
                this.showNotification('error', 'Seleziona anno, mese e file prima di procedere', true);
                return;
            }

            this.isUploading = true;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                console.log('CSRF Token:', csrfToken);

                if (!csrfToken) {
                    throw new Error('Token di sicurezza non trovato. Ricarica la pagina.');
                }

                const formData = new FormData();
                formData.append('file', this.selectedFile);
                formData.append('year', this.form.year);
                formData.append('month', this.form.month);
                formData.append('_token', csrfToken);

                console.log('Request data:', {
                    file: this.selectedFile.name,
                    year: this.form.year,
                    month: this.form.month
                });

                const response = await fetch('/uploads', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData,
                    credentials: 'same-origin'
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', Object.fromEntries(response.headers));

                if (!response.ok) {
                    const contentType = response.headers.get('content-type');

                    if (response.status === 403) {
                        throw new Error('Non sei autorizzato ad effettuare questa operazione. Ricarica la pagina e riprova.');
                    }

                    if (contentType && contentType.includes('text/html')) {
                        const text = await response.text();
                        console.error('Server returned HTML:', text.substring(0, 200));
                        throw new Error('Si è verificato un errore. Ricarica la pagina e riprova.');
                    }

                    if (contentType && contentType.includes('application/json')) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Errore durante il caricamento del file');
                    }

                    throw new Error('Si è verificato un errore imprevisto. Riprova più tardi.');
                }

                const data = await response.json();
                console.log('Response data:', data);

                this.selectedFile = null;
                this.resetFileInput();
                this.showNotification('success', data.message || 'File caricato con successo', true);
                this.startPolling();

                setTimeout(() => {
                    window.location.reload();
                }, 1000);

            } catch (error) {
                console.error('Upload error details:', {
                    message: error.message,
                    stack: error.stack
                });
                this.showNotification('error', error.message || 'Si è verificato un errore durante il caricamento');
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

        async uploadToSftp(uploadId) {
            if (!uploadId) return;

            try {
                const response = await fetch(`/uploads/${uploadId}/upload-sftp`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.message);

                this.showExportModal = false;
                this.selectedUpload = null;
                this.showNotification('success', data.message || 'File caricato su SFTP con successo');
                window.location.reload();
            } catch (error) {
                console.error('Errore nella richiesta SFTP', error);
                this.showNotification('error', error.message);
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
                'pending': 'In attesa',
                'processing': 'In elaborazione',
                'completed': 'Completato',
                'published': 'Pubblicato',
                'error': 'Errore'
            };
            return statusMap[status] || status;
        },

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
            if (!this.currentInfoUpload?.processing_stats?.error_details?.length) {
                return 'Nessun dettaglio errore disponibile';
            }

            const errors = this.currentInfoUpload.processing_stats.error_details;
            return {
                errorCount: errors.length,
                errorLines: errors.map(e => ({
                    line: e.line,
                    message: e.error
                }))
            };
        }
    };
}