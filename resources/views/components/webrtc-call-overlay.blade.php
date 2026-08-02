{{-- resources/views/components/webrtc-call-overlay.blade.php --}}
<div id="webrtcCallWidget" class="fixed bottom-6 right-24 z-40">
    <!-- Floating Directory Toggle Button -->
    <button id="callDirectoryBtn" type="button" class="w-12 h-12 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center shadow-lg transition-all transform hover:scale-105 outline-none focus-visible:ring-4 focus-visible:ring-blue-300" aria-label="Open Call Directory" title="Call Contacts">
        <i class="fas fa-phone-alt text-lg"></i>
    </button>
</div>

<!-- Call Directory Modal -->
<div id="callDirectoryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden backdrop-blur-sm">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden border border-gray-100 animate-slide-in">
        <div class="bg-blue-600 px-6 py-4 text-white flex justify-between items-center">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i class="fas fa-address-book"></i> Call Directory
            </h3>
            <button id="closeDirectoryBtn" type="button" class="text-white/80 hover:text-white transition outline-none" aria-label="Close Directory" title="Close">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="p-4 bg-gray-50 border-b border-gray-100">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="contactSearch" class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Search contacts...">
            </div>
        </div>

        <div class="max-h-96 overflow-y-auto p-4 space-y-3" id="contactsList">
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-circle-notch animate-spin text-2xl mb-2 text-blue-500 block"></i>
                Loading contacts...
            </div>
        </div>

        <!-- Missed Calls History section inside directory -->
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Recent Call Logs</h4>
            <div id="callHistoryList" class="space-y-2 max-h-32 overflow-y-auto text-xs">
                <div class="text-center text-gray-400 py-2">No call history</div>
            </div>
        </div>
    </div>
</div>

<!-- Active Call Signaling Overlay (Ringing / Calling / Connected) -->
<div id="activeCallOverlay" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/95 hidden">
    <div class="relative w-full h-full max-w-4xl max-h-[600px] md:rounded-2xl shadow-2xl overflow-hidden bg-slate-800 flex flex-col">

        <!-- Video Containers -->
        <div class="relative flex-1 bg-slate-950 flex items-center justify-center overflow-hidden">
            <!-- Remote Video (Full Screen) -->
            <video id="remoteVideo" class="w-full h-full object-cover hidden" autoplay playsinline></video>

            <!-- Local Video Preview (Floating PIP) -->
            <div id="localVideoContainer" class="absolute bottom-4 right-4 w-40 h-52 bg-slate-900 rounded-xl overflow-hidden border-2 border-white/20 shadow-2xl z-10 hidden">
                <video id="localVideo" class="w-full h-full object-cover" autoplay playsinline muted></video>
                <div class="absolute bottom-2 left-2 bg-black/60 text-white text-[10px] px-2 py-0.5 rounded font-medium">You</div>
            </div>

            <!-- Call Screen States Placeholder (When Audio or Calling/Ringing) -->
            <div id="callStatePlaceholder" class="flex flex-col items-center text-center p-6 text-white z-20">
                <div class="w-24 h-24 rounded-full bg-blue-600/20 border-2 border-blue-500 flex items-center justify-center mb-6 animate-pulse" id="avatarCircle">
                    <i class="fas fa-user text-4xl text-blue-400" id="placeholderIcon"></i>
                </div>
                <h2 id="callUserName" class="text-2xl font-bold mb-1">Connecting...</h2>
                <p id="callUserRole" class="text-xs uppercase tracking-widest text-slate-400 mb-3"></p>
                <p id="callStatusLabel" class="text-sm font-semibold tracking-wide text-blue-400 animate-pulse">Calling...</p>
                <div id="callTimerLabel" class="text-lg font-mono font-bold mt-4 hidden">00:00</div>
            </div>
        </div>

        <!-- Bottom Controls Bar -->
        <div class="bg-slate-900 px-6 py-5 flex justify-center items-center gap-6 border-t border-slate-800">
            <!-- Call Ringing Actions (Accept / Decline) -->
            <div id="ringingControls" class="flex gap-6 hidden">
                <button id="declineCallBtn" type="button" class="w-14 h-14 bg-red-600 hover:bg-red-700 text-white rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-105 outline-none focus-visible:ring-4 focus-visible:ring-red-400" aria-label="Decline Call" title="Decline Call">
                    <i class="fas fa-phone-slash text-xl"></i>
                </button>
                <button id="acceptCallBtn" type="button" class="w-14 h-14 bg-green-600 hover:bg-green-700 text-white rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-105 outline-none focus-visible:ring-4 focus-visible:ring-green-400 animate-bounce" aria-label="Accept Call" title="Accept Call">
                    <i class="fas fa-phone text-xl"></i>
                </button>
            </div>

            <!-- Active Call Controls -->
            <div id="activeControls" class="flex gap-4 items-center">
                <!-- Toggle Mute Audio -->
                <button id="toggleAudioBtn" type="button" class="w-12 h-12 bg-slate-800 hover:bg-slate-700 text-white rounded-full flex items-center justify-center transition outline-none focus-visible:ring-2 focus-visible:ring-blue-400" aria-label="Mute Microphone" title="Mute Microphone">
                    <i class="fas fa-microphone text-lg"></i>
                </button>
                <!-- Toggle Video Camera -->
                <button id="toggleVideoBtn" type="button" class="w-12 h-12 bg-slate-800 hover:bg-slate-700 text-white rounded-full flex items-center justify-center transition outline-none focus-visible:ring-2 focus-visible:ring-blue-400 hidden" aria-label="Turn Camera Off" title="Turn Camera Off">
                    <i class="fas fa-video text-lg"></i>
                </button>
                <!-- Switch Camera (Multiple cameras option) -->
                <button id="switchCameraBtn" type="button" class="w-12 h-12 bg-slate-800 hover:bg-slate-700 text-white rounded-full flex items-center justify-center transition outline-none focus-visible:ring-2 focus-visible:ring-blue-400 hidden" aria-label="Switch Camera" title="Switch Camera">
                    <i class="fas fa-camera-rotate text-lg"></i>
                </button>
                <!-- Toggle Screen Share -->
                <button id="toggleScreenShareBtn" type="button" class="w-12 h-12 bg-slate-800 hover:bg-slate-700 text-white rounded-full flex items-center justify-center transition outline-none focus-visible:ring-2 focus-visible:ring-blue-400 hidden" aria-label="Share Screen" title="Share Screen">
                    <i class="fas fa-desktop text-lg"></i>
                </button>
                <!-- PIP Toggle -->
                <button id="togglePipBtn" type="button" class="w-12 h-12 bg-slate-800 hover:bg-slate-700 text-white rounded-full flex items-center justify-center transition outline-none focus-visible:ring-2 focus-visible:ring-blue-400 hidden" aria-label="Picture in Picture" title="Picture in Picture">
                    <i class="fas fa-images text-lg"></i>
                </button>
                <!-- Full Screen Toggle -->
                <button id="toggleFullScreenBtn" type="button" class="w-12 h-12 bg-slate-800 hover:bg-slate-700 text-white rounded-full flex items-center justify-center transition outline-none focus-visible:ring-2 focus-visible:ring-blue-400 hidden" aria-label="Toggle Full Screen" title="Toggle Full Screen">
                    <i class="fas fa-expand text-lg"></i>
                </button>
                <!-- End Call / Cancel Call Button -->
                <button id="endCallBtn" type="button" class="w-14 h-14 bg-red-600 hover:bg-red-700 text-white rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-105 outline-none focus-visible:ring-4 focus-visible:ring-red-400" aria-label="End Call" title="End Call">
                    <i class="fas fa-phone-slash text-xl"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Audio element for calling sounds (replaced broken external URL with programmatic tone) -->
<audio id="ringtoneAudio" loop class="hidden" preload="auto"></audio>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    // Global AJAX Setup for CSRF Token
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // DOM Elements
    const callDirectoryBtn = document.getElementById('callDirectoryBtn');
    const callDirectoryModal = document.getElementById('callDirectoryModal');
    const closeDirectoryBtn = document.getElementById('closeDirectoryBtn');
    const contactSearch = document.getElementById('contactSearch');
    const contactsList = document.getElementById('contactsList');
    const callHistoryList = document.getElementById('callHistoryList');

    const activeCallOverlay = document.getElementById('activeCallOverlay');
    const remoteVideo = document.getElementById('remoteVideo');
    const localVideo = document.getElementById('localVideo');
    const localVideoContainer = document.getElementById('localVideoContainer');
    const callUserName = document.getElementById('callUserName');
    const callUserRole = document.getElementById('callUserRole');
    const callStatusLabel = document.getElementById('callStatusLabel');
    const callTimerLabel = document.getElementById('callTimerLabel');
    const ringtoneAudio = document.getElementById('ringtoneAudio');

    const ringingControls = document.getElementById('ringingControls');
    const activeControls = document.getElementById('activeControls');
    const acceptCallBtn = document.getElementById('acceptCallBtn');
    const declineCallBtn = document.getElementById('declineCallBtn');
    const endCallBtn = document.getElementById('endCallBtn');
    const toggleAudioBtn = document.getElementById('toggleAudioBtn');
    const toggleVideoBtn = document.getElementById('toggleVideoBtn');
    const switchCameraBtn = document.getElementById('switchCameraBtn');
    const toggleScreenShareBtn = document.getElementById('toggleScreenShareBtn');
    const togglePipBtn = document.getElementById('togglePipBtn');
    const toggleFullScreenBtn = document.getElementById('toggleFullScreenBtn');

    // WebRTC & Call State Variables
    let currentCall = null;
    let peerConnection = null;
    let localStream = null;
    let isMuted = false;
    let isCameraOff = false;
    let isScreenSharing = false;
    let currentFacingMode = 'user';
    let callDurationTimer = null;
    let secondsElapsed = 0;
    let callTimeout = null;
    let currentUserId = {{ Auth::id() ?? 'null' }};
    let currentCallType = 'audio';
    let isCaller = false;

    // Directory list data cached
    let allContacts = [];

    // WebRTC Configuration - STUN servers
    const rtcConfig = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' }
        ]
    };

    // Initialize Laravel Echo Connection with CDN fallbacks
    async function initializeEcho() {
        if (window.Echo) {
            return window.Echo;
        }

        console.log("window.Echo not found. Attempting inline fallback initialization...");
        
        // Load Pusher CDN if not available
        if (typeof window.Pusher === 'undefined') {
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://js.pusher.com/8.6.0/pusher.min.js';
                script.onload = () => {
                    window.Pusher = Pusher;
                    resolve();
                };
                script.onerror = () => reject(new Error('Failed to load Pusher CDN'));
                document.head.appendChild(script);
            });
        }

        // Load Laravel Echo CDN if not available
        if (typeof window.Echo === 'undefined') {
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js';
                script.onload = () => {
                    resolve();
                };
                script.onerror = () => reject(new Error('Failed to load Laravel Echo CDN'));
                document.head.appendChild(script);
            });
        }

        if (typeof Echo !== 'undefined') {
            const isRipple = '{{ config('broadcasting.default') }}' === 'ripple';
            const key = isRipple ? '{{ config('ripple.key') }}' : '{{ config('broadcasting.connections.reverb.key') }}';
            const port = isRipple ? {{ config('ripple.port', 8080) }} : {{ config('broadcasting.connections.reverb.options.port', 8080) }};
            const scheme = isRipple ? '{{ config('ripple.scheme', 'http') }}' : '{{ config('broadcasting.connections.reverb.options.scheme', 'http') }}';
            const broadcaster = isRipple ? 'pusher' : 'reverb';

            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: '{{ env('VITE_REVERB_APP_KEY', env('REVERB_APP_KEY')) }}',
                wsHost: window.location.hostname,
                wsPort: {{ env('VITE_REVERB_PORT', env('REVERB_PORT', 8080)) }},
                wssPort: {{ env('VITE_REVERB_PORT', env('REVERB_PORT', 8080)) }},
                forceTLS: false,
                enabledTransports: ['ws'],
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }
            });
            console.log("✅ Echo fallback initialized successfully on wsHost:", reverbHost || window.location.hostname, "| secure:", isSecurePage);
            return window.Echo;
        }
        
        throw new Error('Echo class not found after CDN load');
    }

    // Initialize and Subscribe
    initializeEcho().then((echoInstance) => {
        if (echoInstance && currentUserId) {
            const channelName = `user.${currentUserId}`;

            console.log("Echo connection state:", echoInstance.connector.pusher.connection.state);
            console.log(`Subscribing to private-${channelName} channel...`);

            echoInstance.private(channelName)
                // === STEP 2: Verify Subscription ===
                .subscribed(() => {
                    console.log(`✅ Subscribed to private-${channelName} successfully!`);
                })
                // === STEP 3: Catch Auth Errors ===
                .error((error) => {
                    console.error(`❌ Channel subscription error for private-${channelName}:`, error);
                })
                .listen('.IncomingCall', (e) => {
                    console.log("Echo: IncomingCall received:", e);
                    handleIncomingCallEvent(e);
                })
                .listen('.CallAccepted', (e) => {
                    console.log("Echo: CallAccepted received:", e);
                    handleCallAcceptedEvent(e);
                })
                .listen('.CallRejected', (e) => {
                    console.log("Echo: CallRejected received:", e);
                    handleCallRejectedEvent(e);
                })
                .listen('.CallEnded', (e) => {
                    console.log("Echo: CallEnded received:", e);
                    handleCallEndedEvent(e);
                })
                .listen('.OfferCreated', (e) => {
                    console.log("Echo: OfferCreated received:", e);
                    handleOfferCreatedEvent(e);
                })
                .listen('.AnswerCreated', (e) => {
                    console.log("Echo: AnswerCreated received:", e);
                    handleAnswerCreatedEvent(e);
                })
                .listen('.IceCandidate', (e) => {
                    console.log("Echo: IceCandidate received:", e);
                    handleIceCandidateEvent(e);
                })
                .listen('.UserBusy', (e) => {
                    console.log("Echo: UserBusy received:", e);
                    handleUserBusyEvent(e);
                });
        } else {
            console.warn("Echo initialized, but currentUserId is null. UserID:", currentUserId);
        }
    }).catch((err) => {
        console.error("Failed to initialize Laravel Echo:", err);
    });

    // Directory Buttons and Filters
    callDirectoryBtn?.addEventListener('click', () => {
        callDirectoryModal?.classList.remove('hidden');
        loadContacts();
        loadCallHistory();
    });

    closeDirectoryBtn?.addEventListener('click', () => {
        callDirectoryModal?.classList.add('hidden');
    });

    contactSearch?.addEventListener('input', (e) => {
        filterContacts(e.target.value);
    });

    // Load Contacts from Controller
    function loadContacts() {
        $.get('/calls/contacts', (response) => {
            if (response.success) {
                allContacts = response.contacts;
                renderContacts(allContacts);
            } else {
                contactsList.innerHTML = `<div class="text-center py-4 text-red-500">Failed to load contacts.</div>`;
            }
        }).fail(() => {
            contactsList.innerHTML = `<div class="text-center py-4 text-red-500">Failed to fetch contacts.</div>`;
        });
    }

    function renderContacts(contacts) {
        if (contacts.length === 0) {
            contactsList.innerHTML = `<div class="text-center text-gray-500 py-6">No matching contacts found.</div>`;
            return;
        }

        let html = '';
        contacts.forEach(contact => {
            const isOnline = contact.online_status === 'online';
            const statusColor = isOnline ? 'bg-green-500' : 'bg-gray-400';
            const statusText = isOnline ? 'Online' : 'Offline';

            html += `
                <div class="flex items-center justify-between p-3 bg-white border border-gray-100 rounded-xl hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                                ${contact.name.substring(0,2).toUpperCase()}
                            </div>
                            <span class="absolute bottom-0 right-0 w-3 h-3 ${statusColor} border-2 border-white rounded-full" title="${statusText}"></span>
                        </div>
                        <div>
                            <p class="font-bold text-gray-800 text-sm leading-none">${contact.name}</p>
                            <p class="text-xs text-gray-400 mt-1 uppercase font-semibold tracking-wider">${contact.role}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="startCall(${contact.id}, 'audio')" class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center transition outline-none" aria-label="Audio Call ${contact.name}" title="Audio Call">
                            <i class="fas fa-phone-alt text-sm"></i>
                        </button>
                        <button type="button" onclick="startCall(${contact.id}, 'video')" class="w-8 h-8 rounded-full bg-green-50 text-green-600 hover:bg-green-600 hover:text-white flex items-center justify-center transition outline-none" aria-label="Video Call ${contact.name}" title="Video Call">
                            <i class="fas fa-video text-sm"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        contactsList.innerHTML = html;
    }

    function filterContacts(query) {
        const filtered = allContacts.filter(contact =>
            contact.name.toLowerCase().includes(query.toLowerCase()) ||
            contact.role.toLowerCase().includes(query.toLowerCase())
        );
        renderContacts(filtered);
    }

    // Load Call History Logs
    function loadCallHistory() {
        $.get('/calls/history', (response) => {
            if (response.success && response.calls.length > 0) {
                let html = '';
                response.calls.slice(0, 5).forEach(log => {
                    const isCallerLog = log.caller_id === currentUserId;
                    const otherUser = isCallerLog ? log.receiver : log.caller;
                    const userName = otherUser ? otherUser.name : 'Unknown';
                    const iconClass = log.status === 'missed' ? 'fa-phone-slash text-red-500' : (log.call_type === 'video' ? 'fa-video text-blue-500' : 'fa-phone-alt text-green-500');
                    const callDate = new Date(log.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                    html += `
                        <div class="flex items-center justify-between py-1 border-b border-gray-100 last:border-b-0">
                            <div class="flex items-center gap-2">
                                <i class="fas ${iconClass}"></i>
                                <span>${isCallerLog ? 'To' : 'From'}: <strong>${userName}</strong></span>
                            </div>
                            <span class="text-gray-400 text-[10px]">${callDate} (${log.status})</span>
                        </div>
                    `;
                });
                callHistoryList.innerHTML = html;
            } else {
                callHistoryList.innerHTML = `<div class="text-center text-gray-400 py-2">No call history</div>`;
            }
        });
    }

    // Initiates RTCPeerConnection and setups track events
    function initiatePeerConnection(otherId) {
        if (peerConnection) {
            console.log("Peer connection already exists. Closing current peer...");
            peerConnection.close();
        }

        console.log("Setting up RTCPeerConnection for other user ID:", otherId);
        peerConnection = new RTCPeerConnection(rtcConfig);

        // ICE candidate callback
        peerConnection.onicecandidate = (event) => {
            if (event.candidate && currentCall) {
                console.log("Transmitting ICE candidate to other user:", otherId);
                $.post('/signals/ice-candidate', {
                    call_id: currentCall.id,
                    candidate: JSON.stringify(event.candidate),
                    recipient_id: otherId
                }).fail((err) => {
                    console.error("Failed to post ICE Candidate:", err);
                });
            }
        };

        // Track received callback
        peerConnection.ontrack = (event) => {
            console.log("Received remote media stream track:", event.streams[0]);
            if (remoteVideo) {
                remoteVideo.srcObject = event.streams[0];
                remoteVideo.classList.remove('hidden');
            }
        };

        // Connection state logging
        peerConnection.onconnectionstatechange = () => {
            console.log("Connection State Changed:", peerConnection.connectionState);
            if (peerConnection.connectionState === 'connected') {
                callStatusLabel.innerText = 'Connected';
                callStatusLabel.classList.remove('animate-pulse');
                callTimerLabel.classList.remove('hidden');
                startCallTimer();

                // Show call controls
                if (currentCallType === 'video') {
                    toggleVideoBtn.classList.remove('hidden');
                    switchCameraBtn.classList.remove('hidden');
                    toggleScreenShareBtn.classList.remove('hidden');
                    togglePipBtn.classList.remove('hidden');
                    toggleFullScreenBtn.classList.remove('hidden');
                }
            }
        };

        // Add local tracks
        if (localStream) {
            console.log("Adding local tracks to PeerConnection.");
            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });
        } else {
            console.warn("No local tracks to stream.");
        }
    }

    // Acquire standard video/audio media with robust error Canvas fallbacks
    async function getLocalStream(type) {
        if (localStream) {
            return localStream;
        }

        const constraints = {
            audio: true,
            video: type === 'video'
        };

        try {
            console.log("Requesting local media devices...");
            localStream = await navigator.mediaDevices.getUserMedia(constraints);
            if (localVideo) {
                localVideo.srcObject = localStream;
                if (type === 'video') {
                    localVideoContainer.classList.remove('hidden');
                } else {
                    localVideoContainer.classList.add('hidden');
                }
            }
            return localStream;
        } catch (err) {
            console.warn("Local media devices access refused, spinning fallback loopback stream:", err);
            localStream = createFallbackStream(type);
            if (localVideo) {
                localVideo.srcObject = localStream;
                if (type === 'video') {
                    localVideoContainer.classList.remove('hidden');
                } else {
                    localVideoContainer.classList.add('hidden');
                }
            }
            return localStream;
        }
    }

    // Creates canvas fallback stream to survive environments without micro/camera
    function createFallbackStream(type) {
        const tracks = [];

        // Try getting silence
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = ctx.createOscillator();
            const dst = ctx.createMediaStreamDestination();
            oscillator.connect(dst);
            oscillator.start();
            const audioTrack = dst.stream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = false;
                tracks.push(audioTrack);
            }
        } catch (e) {
            console.error("Audio fallback failure:", e);
        }

        // Try getting black frame animation
        if (type === 'video') {
            try {
                const canvas = document.createElement('canvas');
                canvas.width = 320;
                canvas.height = 240;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#0f172a';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#ffffff';
                ctx.font = '14px system-ui';
                ctx.textAlign = 'center';
                ctx.fillText('Simulated Stream', 160, 120);

                let angle = 0;
                setInterval(() => {
                    ctx.fillStyle = '#0f172a';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.fillStyle = '#ffffff';
                    ctx.fillText('Simulated Stream Active', 160, 110);

                    angle += 0.15;
                    ctx.fillStyle = `rgba(59, 130, 246, ${0.5 + Math.sin(angle) * 0.5})`;
                    ctx.beginPath();
                    ctx.arc(160, 140, 6, 0, Math.PI * 2);
                    ctx.fill();
                }, 100);

                const stream = canvas.captureStream(10);
                const videoTrack = stream.getVideoTracks()[0];
                if (videoTrack) {
                    tracks.push(videoTrack);
                }
            } catch (e) {
                console.error("Video fallback failure:", e);
            }
        }

        return new MediaStream(tracks);
    }

    // Start Dialing Outgoing Call
    window.startCall = async function(receiverId, type) {
        currentCallType = type;
        isCaller = true;
        callDirectoryModal?.classList.add('hidden');
        activeCallOverlay?.classList.remove('hidden');

        callUserName.innerText = 'Connecting...';
        callUserRole.innerText = '';
        callStatusLabel.innerText = 'Calling...';
        ringingControls.classList.add('hidden');
        activeControls.classList.remove('hidden');

        toggleVideoBtn.classList.add('hidden');
        switchCameraBtn.classList.add('hidden');
        toggleScreenShareBtn.classList.add('hidden');
        togglePipBtn.classList.add('hidden');
        toggleFullScreenBtn.classList.add('hidden');
        localVideoContainer.classList.add('hidden');
        remoteVideo.classList.add('hidden');

        playRingtone();

        // Prepare local media before creating call for smoother setup
        await getLocalStream(type);

        $.ajax({
            url: '/calls/start',
            method: 'POST',
            data: {
                receiver_id: receiverId,
                call_type: type
            },
            success: function(response) {
                if (response.success) {
                    currentCall = response.call;
                    // Fetch the receiver name from the contacts list or set a fallback
                    const contact = allContacts.find(c => c.id === receiverId);
                    callUserName.innerText = contact ? contact.name : 'Recipient';
                    callUserRole.innerText = contact ? contact.role : 'User';
                    callStatusLabel.innerText = 'Ringing...';

                    // Setup outgoing missed call timeout (30 seconds)
                    if (callTimeout) clearTimeout(callTimeout);
                    callTimeout = setTimeout(() => {
                        if (currentCall && currentCall.status === 'calling') {
                            console.log("No answer, marking call as missed.");
                            $.post('/calls/missed', { call_id: currentCall.id }, () => {
                                closeCallOverlay();
                                Swal.fire('No Answer', 'The subscriber did not answer.', 'info');
                            });
                        }
                    }, 30000);
                } else {
                    Swal.fire('Error', response.message || 'Failed to start call.', 'error');
                    closeCallOverlay();
                }
            },
            error: function(xhr, status, error) {
                console.error("POST /calls/start failed:", {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                let errorMsg = 'Could not initiate calling stream.';
                try {
                    const json = JSON.parse(xhr.responseText);
                    if (json.message) errorMsg = json.message;
                    else if (json.error) errorMsg = json.error;
                } catch(e) {
                    if (xhr.responseText) errorMsg = xhr.responseText.substring(0, 200);
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Call Failed',
                    text: errorMsg,
                    footer: 'Check console (F12) for full error details'
                });
                closeCallOverlay();
            }
        });
    }

    // Event Handlers for WebSocket Signalling Events
    function handleIncomingCallEvent(e) {
        if (currentCall) {
            console.log("Already on another call. Signalling busy status.");
            $.post('/calls/busy', { call_id: e.call.id });
            return;
        }

        currentCall = e.call;
        currentCallType = e.call.call_type;
        isCaller = false;

        activeCallOverlay?.classList.remove('hidden');
        callUserName.innerText = e.callerName;
        callUserRole.innerText = 'Call Request';
        callStatusLabel.innerText = `Incoming ${e.call.call_type} Call...`;

        ringingControls.classList.remove('hidden');
        activeControls.classList.add('hidden');

        playRingtone();
    }

    async function handleCallAcceptedEvent(e) {
        console.log("Outgoing Call Accepted! Preparing SDP Offer.");
        if (callTimeout) clearTimeout(callTimeout);
        stopRingtone();

        if (currentCall) {
            currentCall.status = 'connected';
            initiatePeerConnection(currentCall.receiver_id);

            try {
                const offer = await peerConnection.createOffer();
                await peerConnection.setLocalDescription(offer);

                $.post('/signals/offer', {
                    call_id: currentCall.id,
                    offer: JSON.stringify(offer),
                    recipient_id: currentCall.receiver_id
                });
            } catch (err) {
                console.error("Failed to construct/send SDP offer:", err);
            }
        }
    }

    function handleCallRejectedEvent(e) {
        console.log("Call Rejected.");
        stopRingtone();
        if (callTimeout) clearTimeout(callTimeout);
        callStatusLabel.innerText = 'Call Declined';
        setTimeout(() => closeCallOverlay(), 2500);
    }

    function handleUserBusyEvent(e) {
        console.log("User Busy.");
        stopRingtone();
        if (callTimeout) clearTimeout(callTimeout);
        callStatusLabel.innerText = 'User Busy';
        setTimeout(() => closeCallOverlay(), 2500);
    }

    async function handleOfferCreatedEvent(e) {
        console.log("SDP Offer Received from caller. Preparing SDP Answer.");
        if (!currentCall) return;

        // Prepare local media before responding to SDP Offer
        await getLocalStream(currentCallType);

        initiatePeerConnection(currentCall.caller_id);

        try {
            const offerDesc = JSON.parse(e.offer);
            await peerConnection.setRemoteDescription(new RTCSessionDescription(offerDesc));

            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);

            $.post('/signals/answer', {
                call_id: currentCall.id,
                answer: JSON.stringify(answer),
                recipient_id: currentCall.caller_id
            });
        } catch (err) {
            console.error("Failed to construct/send SDP answer:", err);
        }
    }

    async function handleAnswerCreatedEvent(e) {
        console.log("SDP Answer Received from receiver.");
        if (peerConnection) {
            try {
                const answerDesc = JSON.parse(e.answer);
                await peerConnection.setRemoteDescription(new RTCSessionDescription(answerDesc));
            } catch (err) {
                console.error("Failed to set Remote Description:", err);
            }
        }
    }

    async function handleIceCandidateEvent(e) {
        console.log("Remote ICE Candidate Received.");
        if (peerConnection) {
            try {
                const candidateObj = JSON.parse(e.candidate);
                await peerConnection.addIceCandidate(new RTCIceCandidate(candidateObj));
            } catch (err) {
                console.error("Failed to add remote ICE Candidate:", err);
            }
        }
    }

    function handleCallEndedEvent(e) {
        console.log("Call hung up by remote user.");
        callStatusLabel.innerText = 'Call Ended';
        setTimeout(() => closeCallOverlay(), 1500);
    }

    // Interactive Media Controls (Audio, Video, Screen Share, FullScreen, PIP)
    toggleAudioBtn?.addEventListener('click', () => {
        isMuted = !isMuted;
        if (localStream) {
            localStream.getAudioTracks().forEach(track => track.enabled = !isMuted);
        }
        toggleAudioBtn.classList.toggle('bg-red-600', isMuted);
        toggleAudioBtn.classList.toggle('bg-slate-800', !isMuted);
        toggleAudioBtn.innerHTML = isMuted ? '<i class="fas fa-microphone-slash text-lg"></i>' : '<i class="fas fa-microphone text-lg"></i>';
    });

    toggleVideoBtn?.addEventListener('click', () => {
        isCameraOff = !isCameraOff;
        if (localStream) {
            localStream.getVideoTracks().forEach(track => track.enabled = !isCameraOff);
        }
        toggleVideoBtn.classList.toggle('bg-red-600', isCameraOff);
        toggleVideoBtn.classList.toggle('bg-slate-800', !isCameraOff);
        toggleVideoBtn.innerHTML = isCameraOff ? '<i class="fas fa-video-slash text-lg"></i>' : '<i class="fas fa-video text-lg"></i>';
    });

    switchCameraBtn?.addEventListener('click', async () => {
        if (!localStream) return;
        currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
        console.log("Toggling camera facing mode:", currentFacingMode);

        localStream.getVideoTracks().forEach(track => track.stop());

        try {
            const newStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: currentFacingMode },
                audio: !isMuted
            });

            const newVideoTrack = newStream.getVideoTracks()[0];
            if (peerConnection && newVideoTrack) {
                const videoSender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                if (videoSender) {
                    await videoSender.replaceTrack(newVideoTrack);
                }
            }

            localStream = newStream;
            localVideo.srcObject = newStream;
        } catch (e) {
            console.error("Failed to switch facingMode:", e);
        }
    });

    toggleScreenShareBtn?.addEventListener('click', async () => {
        if (isScreenSharing) {
            isScreenSharing = false;
            toggleScreenShareBtn.classList.remove('bg-blue-600');
            toggleScreenShareBtn.classList.add('bg-slate-800');

            localStream.getVideoTracks().forEach(track => track.stop());

            try {
                const cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
                const newVideoTrack = cameraStream.getVideoTracks()[0];
                localStream.addTrack(newVideoTrack);
                localVideo.srcObject = localStream;

                if (peerConnection && newVideoTrack) {
                    const videoSender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (videoSender) {
                        await videoSender.replaceTrack(newVideoTrack);
                    }
                }
            } catch (e) {
                console.error("Could not restore camera track:", e);
            }
        } else {
            try {
                const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                const screenTrack = screenStream.getVideoTracks()[0];

                isScreenSharing = true;
                toggleScreenShareBtn.classList.add('bg-blue-600');
                toggleScreenShareBtn.classList.remove('bg-slate-800');

                screenTrack.onended = () => {
                    if (isScreenSharing) toggleScreenShareBtn.click();
                };

                if (peerConnection && screenTrack) {
                    const videoSender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                    if (videoSender) {
                        await videoSender.replaceTrack(screenTrack);
                    }
                }

                localVideo.srcObject = screenStream;
            } catch (e) {
                console.error("DisplayMedia failed:", e);
            }
        }
    });

    togglePipBtn?.addEventListener('click', async () => {
        try {
            if (document.pictureInPictureElement) {
                await document.exitPictureInPicture();
            } else if (remoteVideo && remoteVideo.readyState >= 2) {
                await remoteVideo.requestPictureInPicture();
            }
        } catch (e) {
            console.error("PIP execution failed:", e);
        }
    });

    toggleFullScreenBtn?.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            activeCallOverlay.requestFullscreen().catch(err => {
                console.error("Fullscreen failed:", err);
            });
        } else {
            document.exitFullscreen();
        }
    });

    // Accept / Decline Actions
    acceptCallBtn?.addEventListener('click', () => {
        if (currentCall) {
            stopRingtone();
            $.post('/calls/accept', { call_id: currentCall.id }, (response) => {
                if (response.success) {
                    ringingControls.classList.add('hidden');
                    activeControls.classList.remove('hidden');
                    callStatusLabel.innerText = 'Connected';
                    callTimerLabel.classList.remove('hidden');
                    startCallTimer();
                }
            });
        }
    });

    declineCallBtn?.addEventListener('click', () => {
        if (currentCall) {
            stopRingtone();
            $.post('/calls/reject', { call_id: currentCall.id }, () => {
                closeCallOverlay();
            });
        }
    });

    endCallBtn?.addEventListener('click', () => {
        if (currentCall) {
            $.post('/calls/end', { call_id: currentCall.id }, () => {
                closeCallOverlay();
            });
        } else {
            closeCallOverlay();
        }
    });

    // Ringtone Play/Stop
    let ringtoneUnlocked = false;
    let ringtoneInterval = null;

    // Unlock audio context on first user interaction (click anywhere)
    function unlockRingtone() {
        if (ringtoneUnlocked) return;
        ringtoneUnlocked = true;
        // Fallback: try using AudioContext to unlock
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            ctx.resume();
        } catch(e) {}
        document.removeEventListener('click', unlockRingtone);
        document.removeEventListener('touchstart', unlockRingtone);
    }
    document.addEventListener('click', unlockRingtone);
    document.addEventListener('touchstart', unlockRingtone);

    // Generate ringtone tone using Web Audio API (no external source needed)
    function startRingtoneTone() {
        stopRingtoneTone();
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const gainNode = audioCtx.createGain();
            gainNode.gain.value = 0.3;
            gainNode.connect(audioCtx.destination);

            let isOn = true;
            ringtoneInterval = setInterval(() => {
                if (isOn) {
                    const osc = audioCtx.createOscillator();
                    osc.type = 'sine';
                    osc.frequency.value = 440;
                    osc.connect(gainNode);
                    osc.start();
                    // Store oscillator so we can stop it
                    if (!window._ringtoneOscs) window._ringtoneOscs = [];
                    window._ringtoneOscs.push(osc);
                    setTimeout(() => {
                        try { osc.stop(); } catch(e) {}
                        // Remove from list
                        if (window._ringtoneOscs) {
                            const idx = window._ringtoneOscs.indexOf(osc);
                            if (idx > -1) window._ringtoneOscs.splice(idx, 1);
                        }
                    }, 500);
                }
                isOn = !isOn;
            }, 600);
        } catch(e) {
            console.log('Web Audio ringtone not available:', e);
        }
    }

    function stopRingtoneTone() {
        if (ringtoneInterval) {
            clearInterval(ringtoneInterval);
            ringtoneInterval = null;
        }
        if (window._ringtoneOscs) {
            window._ringtoneOscs.forEach(osc => {
                try { osc.stop(); } catch(e) {}
            });
            window._ringtoneOscs = [];
        }
    }

    function playRingtone() {
        // First try the <audio> element (if a valid src was set)
        ringtoneAudio.currentTime = 0;
        const playPromise = ringtoneAudio.play();
        if (playPromise !== undefined) {
            playPromise.catch(() => {
                // If audio element fails (no supported source), use Web Audio API
                startRingtoneTone();
            });
        } else {
            startRingtoneTone();
        }
    }

    function stopRingtone() {
        ringtoneAudio.pause();
        ringtoneAudio.currentTime = 0;
        stopRingtoneTone();
    }

    // Call Duration Tracking Timer
    function startCallTimer() {
        secondsElapsed = 0;
        callTimerLabel.innerText = '00:00';

        if (callDurationTimer) clearInterval(callDurationTimer);

        callDurationTimer = setInterval(() => {
            secondsElapsed++;
            const mins = Math.floor(secondsElapsed / 60).toString().padStart(2, '0');
            const secs = (secondsElapsed % 60).toString().padStart(2, '0');
            callTimerLabel.innerText = `${mins}:${secs}`;
        }, 1000);
    }

    function stopCallTimer() {
        if (callDurationTimer) {
            clearInterval(callDurationTimer);
            callDurationTimer = null;
        }
    }

    // Closes and cleans up streams
    function closeCallOverlay() {
        stopRingtone();
        stopCallTimer();
        if (callTimeout) clearTimeout(callTimeout);

        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }

        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            localStream = null;
        }

        activeCallOverlay?.classList.add('hidden');
        currentCall = null;
    }
});
</script>
