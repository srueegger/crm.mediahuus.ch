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
            // For IMEI scanning, use live barcode scanner instead of photo capture
            if (target === 'imei') {
                this.modal.classList.remove('hidden');
                this.captureBtn.style.display = 'none'; // Hide capture button for barcode mode
                await this.startLiveBarcodeScanner();
                return;
            }

            // Request camera with optimal settings for document scanning
            const constraints = {
                video: {
                    facingMode: 'environment', // Use back camera
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }
            };

            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.video.srcObject = this.stream;
            this.captureBtn.style.display = 'block'; // Show capture button for photo mode
            this.modal.classList.remove('hidden');
        } catch (error) {
            console.error('Camera access error:', error);
            alert('Kamera-Zugriff fehlgeschlagen. Bitte überprüfen Sie die Berechtigungen.');
        }
    }

    async startLiveBarcodeScanner() {
        const imeiInput = document.getElementById('imei');
        const scanText = document.getElementById('scanImeiText');
        const barcodeScanArea = document.getElementById('barcodeScanArea');

        console.log('=== Starting IMEI Barcode Scanner ===');
        console.log('Quagga available:', typeof Quagga !== 'undefined');

        if (scanText) scanText.textContent = 'Scanne Barcode...';
        if (barcodeScanArea) barcodeScanArea.style.display = 'block';

        // Check if Quagga is loaded
        if (typeof Quagga === 'undefined') {
            console.error('QuaggaJS not loaded!');
            alert('Barcode-Scanner Bibliothek nicht geladen.\n\nBitte laden Sie die Seite neu.');
            this.closeCamera();
            return;
        }

        try {
            // Start camera stream first
            const constraints = {
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }
            };

            console.log('Requesting camera access...');
            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.video.srcObject = this.stream;

            // Wait for video to be ready
            console.log('Waiting for video to be ready...');
            await new Promise((resolve) => {
                this.video.onloadedmetadata = () => {
                    console.log('Video ready, dimensions:', this.video.videoWidth, 'x', this.video.videoHeight);
                    this.video.play();
                    resolve();
                };
            });

            console.log('Initializing QuaggaJS...');
            // Initialize QuaggaJS
            await Quagga.init({
                inputStream: {
                    type: 'LiveStream',
                    target: this.video,
                    constraints: {
                        facingMode: 'environment'
                    }
                },
                frequency: 10,
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
                        'i2of5_reader',
                        '2of5_reader'
                    ],
                    debug: {
                        drawBoundingBox: true,
                        showFrequency: false,
                        drawScanline: true,
                        showPattern: false
                    }
                },
                locate: true,
                locator: {
                    patchSize: 'medium',
                    halfSample: true
                }
            });

            console.log('QuaggaJS initialized, starting scanner...');
            Quagga.start();

            // Listen for barcode detection
            Quagga.onDetected((result) => {
                if (result && result.codeResult && result.codeResult.code) {
                    const code = result.codeResult.code;
                    console.log('Barcode detected:', code);

                    const numbers = code.replace(/\D/g, '');

                    // Check if it's a valid IMEI (15 digits) or similar
                    if (numbers.length >= 14) {
                        const imei = numbers.substring(0, 15);

                        // Stop scanning immediately
                        Quagga.stop();

                        imeiInput.value = imei;

                        // Visual feedback
                        imeiInput.classList.add('border-emerald-500', 'border-2');
                        setTimeout(() => {
                            imeiInput.classList.remove('border-emerald-500', 'border-2');
                        }, 2000);

                        // Close camera
                        this.closeCamera();

                        // Show success message
                        alert(`IMEI erkannt: ${imei}\n\nBitte überprüfen Sie die Nummer.`);

                        // Scroll to input
                        imeiInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => imeiInput.focus(), 500);
                    }
                }
            });

            // Debug: Log processing events
            Quagga.onProcessed((result) => {
                if (result && result.boxes) {
                    console.log('Processing frame, boxes found:', result.boxes.length);
                }
            });

        } catch (error) {
            console.error('Barcode scanner error:', error);
            alert('Barcode-Scanner konnte nicht gestartet werden.\n\nBitte geben Sie die IMEI manuell ein.');
            this.closeCamera();

            if (scanText) scanText.textContent = 'IMEI-Barcode scannen';
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

        // Handle ID document capture
        this.handleIdDocumentCapture(imageDataUrl);

        this.closeCamera();
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
        // Stop Quagga if running
        try {
            if (typeof Quagga !== 'undefined') {
                Quagga.offDetected();
                Quagga.offProcessed();
                Quagga.stop();
                console.log('Quagga stopped');
            }
        } catch (e) {
            console.log('Quagga cleanup error:', e);
        }

        // Stop regular video stream
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        this.video.srcObject = null;
        this.modal.classList.add('hidden');
        this.currentTarget = null;

        // Hide barcode scan area
        const barcodeScanArea = document.getElementById('barcodeScanArea');
        if (barcodeScanArea) barcodeScanArea.style.display = 'none';

        // Reset button text and visibility
        const scanText = document.getElementById('scanImeiText');
        if (scanText) scanText.textContent = 'IMEI-Barcode scannen';

        if (this.captureBtn) this.captureBtn.style.display = 'block';
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
