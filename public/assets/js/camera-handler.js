/**
 * Camera Handler for Purchase Form
 * Handles camera access, photo capture, and IMEI scanning
 */

class CameraHandler {
    constructor() {
        this.stream = null;
        this.currentTarget = null;
        this.modal = document.getElementById('cameraModal');
        this.video = document.getElementById('cameraVideo');
        this.canvas = document.getElementById('cameraCanvas');
        this.captureBtn = document.getElementById('capturePhotoBtn');
        this.closeBtn = document.getElementById('closeCameraBtn');
        this.modalTitle = document.getElementById('cameraModalTitle');

        this.init();
    }

    init() {
        // ID Document Front
        const previewFront = document.getElementById('preview-front');
        if (previewFront) {
            previewFront.addEventListener('click', () => {
                this.openCamera('front', 'Ausweis Vorderseite fotografieren');
            });
        }

        // ID Document Back
        const previewBack = document.getElementById('preview-back');
        if (previewBack) {
            previewBack.addEventListener('click', () => {
                this.openCamera('back', 'Ausweis Rückseite fotografieren');
            });
        }

        // IMEI Scan Button
        const scanImeiBtn = document.getElementById('scanImeiBtn');
        if (scanImeiBtn) {
            scanImeiBtn.addEventListener('click', () => {
                this.openCamera('imei', 'IMEI fotografieren');
            });
        }

        // Camera controls
        if (this.captureBtn) {
            this.captureBtn.addEventListener('click', () => this.capturePhoto());
        }

        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.closeCamera());
        }

        // Handle retake buttons dynamically
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('retake-button')) {
                const target = e.target.dataset.target;
                const title = e.target.dataset.title;
                this.openCamera(target, title);
            }
        });
    }

    async openCamera(target, title) {
        this.currentTarget = target;
        this.modalTitle.textContent = title;

        try {
            // Request camera with optimal settings for document scanning
            const constraints = {
                video: {
                    facingMode: target === 'imei' ? 'environment' : 'environment', // Use back camera
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }
            };

            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.video.srcObject = this.stream;
            this.modal.classList.remove('hidden');
        } catch (error) {
            console.error('Camera access error:', error);
            alert('Kamera-Zugriff fehlgeschlagen. Bitte überprüfen Sie die Berechtigungen.');
        }
    }

    capturePhoto() {
        if (!this.stream) return;

        // Set canvas dimensions to match video
        this.canvas.width = this.video.videoWidth;
        this.canvas.height = this.video.videoHeight;

        // Draw video frame to canvas
        const ctx = this.canvas.getContext('2d');
        ctx.drawImage(this.video, 0, 0);

        // Convert to base64
        const imageDataUrl = this.canvas.toDataURL('image/jpeg', 0.85);

        // Handle based on target
        if (this.currentTarget === 'imei') {
            this.handleImeiScan(imageDataUrl);
        } else {
            this.handleIdDocumentCapture(imageDataUrl);
        }

        this.closeCamera();
    }

    async handleImeiScan(imageDataUrl) {
        const imeiInput = document.getElementById('imei');
        const scanBtn = document.getElementById('scanImeiBtn');
        const scanText = document.getElementById('scanImeiText');

        try {
            // Show loading state
            if (scanBtn) scanBtn.disabled = true;
            if (scanText) scanText.textContent = 'Scanne Barcode...';

            // Use QuaggaJS for Barcode Scanning
            const result = await this.scanBarcodeFromImage(imageDataUrl);

            if (result) {
                // Extract 15-digit IMEI
                const numbers = result.replace(/\D/g, '');
                const imeiMatch = numbers.match(/\d{15}/);

                if (imeiMatch) {
                    imeiInput.value = imeiMatch[0];

                    // Visual feedback
                    imeiInput.classList.add('border-emerald-500', 'border-2');
                    setTimeout(() => {
                        imeiInput.classList.remove('border-emerald-500', 'border-2');
                    }, 2000);

                    alert(`IMEI erkannt: ${imeiMatch[0]}\n\nBitte überprüfen Sie die Nummer.`);
                } else if (numbers.length >= 14) {
                    // Accept 14-17 digit codes
                    imeiInput.value = numbers.substring(0, 15);
                    alert(`Code erkannt: ${numbers.substring(0, 15)}\n\nBitte überprüfen Sie die Nummer.`);
                } else {
                    throw new Error('Keine gültige IMEI gefunden');
                }
            } else {
                // If no barcode found, allow manual input
                const manualInput = prompt(
                    'Konnte keinen Barcode erkennen.\n\nBitte geben Sie die IMEI manuell ein:'
                );

                if (manualInput && manualInput.trim()) {
                    imeiInput.value = manualInput.trim();
                }
            }

        } catch (error) {
            console.error('Barcode Scan Error:', error);
            alert('Barcode-Erkennung fehlgeschlagen.\n\nBitte geben Sie die IMEI manuell ein.');
        } finally {
            // Reset button state
            if (scanBtn) scanBtn.disabled = false;
            if (scanText) scanText.textContent = 'IMEI mit Kamera scannen';

            // Scroll to and focus input
            imeiInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => imeiInput.focus(), 500);
        }
    }

    scanBarcodeFromImage(imageDataUrl) {
        return new Promise((resolve, reject) => {
            Quagga.decodeSingle({
                src: imageDataUrl,
                numOfWorkers: 0,
                locate: true,
                decoder: {
                    readers: [
                        'code_128_reader',
                        'ean_reader',
                        'ean_8_reader',
                        'code_39_reader',
                        'code_39_vin_reader',
                        'codabar_reader',
                        'upc_reader',
                        'upc_e_reader',
                        'i2of5_reader'
                    ]
                }
            }, (result) => {
                if (result && result.codeResult) {
                    resolve(result.codeResult.code);
                } else {
                    resolve(null);
                }
            });
        });
    }

    handleIdDocumentCapture(imageDataUrl) {
        const targetId = `id_document_${this.currentTarget}`;
        const previewId = `preview-${this.currentTarget}`;

        // Store base64 data in hidden input
        const hiddenInput = document.getElementById(targetId);
        if (hiddenInput) {
            hiddenInput.value = imageDataUrl;
        }

        // Update preview
        const preview = document.getElementById(previewId);
        if (preview) {
            preview.innerHTML = `
                <img src="${imageDataUrl}" alt="Captured ID">
                <button type="button" class="retake-button" data-target="${this.currentTarget}"
                        data-title="${this.currentTarget === 'front' ? 'Ausweis Vorderseite fotografieren' : 'Ausweis Rückseite fotografieren'}">
                    Erneut aufnehmen
                </button>
            `;
        }

        // Visual feedback
        preview.classList.add('ring-4', 'ring-emerald-500');
        setTimeout(() => {
            preview.classList.remove('ring-4', 'ring-emerald-500');
        }, 1000);
    }

    closeCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        this.video.srcObject = null;
        this.modal.classList.add('hidden');
        this.currentTarget = null;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new CameraHandler();
    });
} else {
    new CameraHandler();
}

// Form validation before submit
const purchaseForm = document.getElementById('purchaseForm');
if (purchaseForm) {
    purchaseForm.addEventListener('submit', (e) => {
        const idFront = document.getElementById('id_document_front').value;

        if (!idFront) {
            e.preventDefault();
            alert('Bitte fotografieren Sie mindestens die Vorderseite des Ausweises.');

            // Scroll to ID document section
            const idSection = document.getElementById('preview-front');
            if (idSection) {
                idSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                idSection.classList.add('ring-4', 'ring-red-500');
                setTimeout(() => {
                    idSection.classList.remove('ring-4', 'ring-red-500');
                }, 2000);
            }
        }
    });
}

// Auto-format phone number (Swiss format)
const phoneInput = document.getElementById('customer_phone');
if (phoneInput) {
    phoneInput.addEventListener('blur', (e) => {
        let phone = e.target.value.replace(/\D/g, '');

        // Format Swiss mobile numbers: +41 79 123 45 67
        if (phone.startsWith('0')) {
            phone = '41' + phone.substring(1);
        }
        if (phone.length === 11 && phone.startsWith('41')) {
            e.target.value = '+' + phone.substring(0, 2) + ' ' +
                           phone.substring(2, 4) + ' ' +
                           phone.substring(4, 7) + ' ' +
                           phone.substring(7, 9) + ' ' +
                           phone.substring(9);
        }
    });
}

// Auto-uppercase postal code
const zipInput = document.getElementById('seller_zip');
if (zipInput) {
    zipInput.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
    });
}

// Price formatting
const priceInput = document.getElementById('purchase_price_chf');
if (priceInput) {
    priceInput.addEventListener('blur', (e) => {
        const value = parseFloat(e.target.value);
        if (!isNaN(value)) {
            e.target.value = value.toFixed(2);
        }
    });
}
