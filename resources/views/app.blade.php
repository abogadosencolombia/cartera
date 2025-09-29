<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    {{-- Exponer rutas a Ziggy (esto ya lo tenías y es correcto) --}}
    @routes

    {{-- Carga de assets con Vite --}}
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    {{-- El punto de montaje para tu aplicación Inertia/Vue --}}
    @inertia

    {{-- ======================================================= --}}
    {{-- ========= INICIO: CÓDIGO DEL CHAT AÑADIDO ========= --}}
    {{-- ======================================================= --}}
    @auth
        <x-chat-panel :user="Auth::user()" />
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('chatPanel', (config) => ({
                    // ... (todas las propiedades se mantienen igual)
                    isOpen: false,
                    isLoading: false, // Esta propiedad controla el bloqueo
                    newMessage: '',
                    userId: config.userId,
                    userName: config.userName,
                    sendMessageUrl: route('chatbot.send'),
                    file: null,
                    fileName: '',

                    init() {
                        window.Echo.private(`chat.${this.userId}`)
                            .listen('ChatbotResponseReceived', (e) => {
                                this.addMessage(e.message, 'bot');
                                
                                // --- CAMBIO #1: DESBLOQUEAMOS EL CHAT AQUÍ ---
                                // El bot ha respondido, así que volvemos a activar el input.
                                this.isLoading = false; 
                            });
                    },

                    // ... (las funciones toggle, selectFile, handleFileSelect, removeFile se mantienen igual)
                    toggle() { this.isOpen = !this.isOpen; },
                    selectFile() { this.$refs.fileInput.click(); },
                    handleFileSelect(event) {
                        if (event.target.files.length > 0) {
                            this.file = event.target.files[0];
                            this.fileName = this.file.name;
                        }
                    },
                    removeFile() {
                        this.file = null;
                        this.fileName = '';
                        this.$refs.fileInput.value = '';
                    },

                    sendMessage() {
                        if (this.newMessage.trim() === '' && !this.file) return;

                        // Bloqueamos el input en cuanto se envía el mensaje
                        this.isLoading = true;

                        const formData = new FormData();
                        formData.append('message', this.newMessage);
                        
                        if (this.file) {
                            formData.append('file', this.file);
                            this.addMessage(`${this.newMessage} <br><small class="opacity-75">📎 Archivo: ${this.fileName}</small>`, 'user');
                        } else {
                            this.addMessage(this.newMessage, 'user');
                        }

                        axios.post(this.sendMessageUrl, formData, {
                            headers: { 'Content-Type': 'multipart/form-data' },
                        }).catch(error => {
                            console.error('Error al enviar mensaje:', error);
                            this.addMessage('Hubo un error al enviar tu mensaje.', 'error');
                            // Si hay un error, también desbloqueamos el input para que pueda intentarlo de nuevo.
                            this.isLoading = false; 
                        })
                        // --- CAMBIO #2: HEMOS ELIMINADO EL .finally() DE AQUÍ ---
                        // Ya no desbloqueamos el input solo porque el mensaje se envió,
                        // ahora esperamos la respuesta del bot.
                        
                        this.newMessage = '';
                        this.removeFile();
                    },

                    addMessage(text, type) {
                        // ... (esta función se mantiene exactamente igual)
                        const messageContainer = this.$refs.messages;
                        const messageWrapper = document.createElement('div');
                        const messageBubble = document.createElement('div');
                        messageBubble.innerHTML = `<p>${text}</p>`;
                        if (type === 'user') {
                            messageWrapper.className = 'flex justify-end';
                            messageBubble.className = 'bg-blue-500 text-white p-3 rounded-lg max-w-xs';
                        } else if (type === 'bot') {
                            messageWrapper.className = 'flex';
                            messageBubble.className = 'bg-gray-200 text-gray-800 p-3 rounded-lg max-w-xs';
                        } else {
                            messageWrapper.className = 'flex';
                            messageBubble.className = 'bg-red-200 text-red-800 p-3 rounded-lg max-w-xs';
                        }
                        messageWrapper.appendChild(messageBubble);
                        messageContainer.appendChild(messageWrapper);
                        messageContainer.scrollTop = messageContainer.scrollHeight;
                    }
                }));
            });
        </script>
    @endauth
    {{-- ======================================================= --}}
    {{-- =========== FIN: CÓDIGO DEL CHAT AÑADIDO ============ --}}
    {{-- ======================================================= --}}
</body>
</html>