<?php
// Check if AI assistant is globally enabled
$isAiEnabled = !isset($settings['ai_enabled']) || $settings['ai_enabled'] === '1' || $settings['ai_enabled'] === 'true' || $settings['ai_enabled'] === 1;
if (!$isAiEnabled) {
    return; // Don't render the widget if AI is disabled!
}

// Retrieve Dynamic Phone Number for Floating Actions
$aiRawPhone = $settings['contact_phone1'] ?? '919946020716';
$aiWaPhone = preg_replace('/[^0-9]/', '', $aiRawPhone);
if (strlen($aiWaPhone) === 10) {
    $aiWaPhone = '91' . $aiWaPhone;
}
if (empty($aiWaPhone)) {
    $aiWaPhone = '919946020716';
}
$aiBotName = !empty($settings['ai_bot_name']) ? $settings['ai_bot_name'] : 'Nova';
$aiGreetingTitle = !empty($settings['ai_greeting_title']) ? $settings['ai_greeting_title'] : 'Hi! I am <span class="ai-highlight-name">' . htmlspecialchars($aiBotName) . '</span> 👋';
$aiGreetingDesc = !empty($settings['ai_greeting_desc']) ? $settings['ai_greeting_desc'] : 'How can I help you today?';
?>
<!-- Perumalil Creative - Animated AI Assistant Companion (Nova) -->
<div class="ai-assistant-widget" id="aiAssistantWidget" data-wa-phone="<?php echo htmlspecialchars($aiWaPhone); ?>" aria-label="Studio AI Assistant">

    <!-- Interactive Speech Bubble Introduction -->
    <div class="ai-speech-bubble" id="aiSpeechBubble" role="dialog" aria-label="AI Assistant Greeting">
        <div class="ai-bubble-header">
            <div class="ai-bubble-tag">
                <span class="ai-bubble-pulse"></span>
                <span class="ai-bubble-sparkle">✦</span>
                <span class="ai-bubble-role"><?php echo strtoupper(htmlspecialchars($aiBotName)); ?> AI</span>
            </div>
            <div class="ai-bubble-actions">
                <button type="button" class="ai-voice-trigger-btn" id="aiVoiceTriggerBtn" aria-label="Listen to Nova Speak" title="Hear Nova speak aloud">
                    <svg class="ai-voice-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <path class="voice-wave wave-1" d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                        <path class="voice-wave wave-2" d="M19.07 4.93a10 10 0 0 1 0 14.14"></path>
                    </svg>
                    <span class="voice-label" id="aiVoiceLabel">Listen</span>
                </button>
                <button type="button" class="ai-bubble-close-btn" id="aiBubbleCloseBtn" aria-label="Dismiss message" title="Dismiss">&times;</button>
            </div>
        </div>

        <div class="ai-bubble-content">
            <h4 class="ai-bubble-title">
                <?php echo $aiGreetingTitle; ?>
            </h4>
            <p class="ai-bubble-desc" id="aiBubbleDesc">
                <?php echo htmlspecialchars($aiGreetingDesc); ?>
            </p>
        </div>

        <div class="ai-bubble-chips">
            <button type="button" class="ai-quick-chip ai-chip-accent" onclick="window.openNovaAiAssistant()">
                <span class="chip-icon">💬</span> Chat with Me
            </button>
        </div>
        <div class="ai-bubble-arrow"></div>
    </div>

    <!-- Floating Character Avatar Trigger -->
    <div class="ai-character-anchor" id="aiCharAnchor">
        <button type="button" class="ai-character-btn" id="aiCharacterBtn" aria-label="Open Nova AI Assistant" title="Meet Nova, your AI Assistant">
            <span class="ai-aura-ring aura-1"></span>
            <span class="ai-aura-ring aura-2"></span>

            <!-- Master Animated SVG Character: Nova the AI Assistant -->
            <svg class="ai-character-svg" id="aiCharacterSvg" viewBox="0 0 120 130" width="76" height="84" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <!-- Holographic & Cyber Gradients -->
                    <linearGradient id="aiGradHelmet" x1="20" y1="20" x2="100" y2="100" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#1e293b"/>
                        <stop offset="60%" stop-color="#0f172a"/>
                        <stop offset="100%" stop-color="#020617"/>
                    </linearGradient>

                    <linearGradient id="aiGradAccent" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#38bdf8"/>
                        <stop offset="100%" stop-color="#0066ff"/>
                    </linearGradient>

                    <linearGradient id="aiGradVisor" x1="40" y1="36" x2="80" y2="70" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#050814"/>
                        <stop offset="50%" stop-color="#071126"/>
                        <stop offset="100%" stop-color="#030712"/>
                    </linearGradient>

                    <linearGradient id="aiGradCore" x1="50" y1="84" x2="70" y2="100" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#38bdf8"/>
                        <stop offset="50%" stop-color="#0066ff"/>
                        <stop offset="100%" stop-color="#a855f7"/>
                    </linearGradient>

                    <radialGradient id="aiGlowOrb" cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.9"/>
                        <stop offset="60%" stop-color="#0066ff" stop-opacity="0.4"/>
                        <stop offset="100%" stop-color="#0066ff" stop-opacity="0"/>
                    </radialGradient>

                    <!-- Neon Filters -->
                    <filter id="aiCyanGlow" x="-20%" y="-20%" width="140%" height="140%">
                        <feGaussianBlur in="SourceGraphic" stdDeviation="2.5" result="blur"/>
                        <feMerge>
                            <feMergeNode in="blur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                    <filter id="aiIntenseGlow" x="-30%" y="-30%" width="160%" height="160%">
                        <feGaussianBlur in="SourceGraphic" stdDeviation="4" result="blur2"/>
                        <feMerge>
                            <feMergeNode in="blur2"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>

                <!-- Levitating Soft Ground Shadow -->
                <ellipse class="ai-ground-shadow" cx="60" cy="122" rx="26" ry="5" fill="#000000" fill-opacity="0.45"/>

                <!-- Animated Floating Character Group -->
                <g class="ai-floating-body" id="aiFloatingBody">

                    <!-- Thruster Energy Jet / Levitation Core -->
                    <g class="ai-thruster-jet">
                        <ellipse cx="60" cy="104" rx="14" ry="4" fill="#38bdf8" filter="url(#aiCyanGlow)"/>
                        <path d="M52 104 C56 114, 64 114, 68 104 Z" fill="url(#aiGradAccent)" opacity="0.75" filter="url(#aiCyanGlow)"/>
                    </g>

                    <!-- Torso Pod -->
                    <path d="M42 78 C42 72, 78 72, 78 78 L74 98 C74 103, 46 103, 46 98 Z" fill="url(#aiGradHelmet)" stroke="#38bdf8" stroke-width="1.2" stroke-opacity="0.6"/>

                    <!-- Chest AI Reactor Core -->
                    <circle class="ai-core-reactor" cx="60" cy="88" r="6.5" fill="url(#aiGradCore)" filter="url(#aiCyanGlow)"/>
                    <circle cx="60" cy="88" r="2.5" fill="#ffffff" opacity="0.9"/>

                    <!-- Left Arm & Floating Hand -->
                    <g class="ai-hand-left">
                        <path d="M40 80 Q32 86, 30 92" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round" opacity="0.8"/>
                        <circle cx="29" cy="93" r="4.5" fill="url(#aiGradHelmet)" stroke="#38bdf8" stroke-width="1.2"/>
                        <circle cx="29" cy="93" r="1.8" fill="#38bdf8" opacity="0.9"/>
                    </g>

                    <!-- Right Arm & Interactive Waving Hand -->
                    <g class="ai-hand-right" id="aiWaveHand">
                        <path d="M80 80 Q88 84, 91 88" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round" opacity="0.8"/>
                        <g class="ai-waving-palm" id="aiWavingPalm">
                            <circle cx="92" cy="89" r="4.8" fill="url(#aiGradHelmet)" stroke="#38bdf8" stroke-width="1.2"/>
                            <circle cx="92" cy="89" r="1.8" fill="#38bdf8" opacity="0.9"/>
                            <!-- Energy spark fingers -->
                            <path d="M92 84 L92 82 M95 86 L98 84 M96 90 L99 91" stroke="#38bdf8" stroke-width="1.5" stroke-linecap="round" filter="url(#aiCyanGlow)"/>
                        </g>
                    </g>

                    <!-- Head & Visor Assembly -->
                    <!-- Top Antenna & Energy Node -->
                    <line x1="60" y1="26" x2="60" y2="14" stroke="#38bdf8" stroke-width="2" stroke-linecap="round"/>
                    <circle class="ai-antenna-orb" cx="60" cy="12" r="4" fill="#38bdf8" filter="url(#aiIntenseGlow)"/>
                    <ellipse class="ai-halo-pulse-ring" cx="60" cy="12" rx="10" ry="3.5" fill="none" stroke="#38bdf8" stroke-width="1" opacity="0.7"/>

                    <!-- Ear Pods / Audio Receptors -->
                    <rect x="29" y="44" width="5" height="14" rx="2.5" fill="#1e293b" stroke="#38bdf8" stroke-width="1.2"/>
                    <rect x="86" y="44" width="5" height="14" rx="2.5" fill="#1e293b" stroke="#38bdf8" stroke-width="1.2"/>
                    <circle cx="31.5" cy="51" r="1.5" fill="#38bdf8" filter="url(#aiCyanGlow)"/>
                    <circle cx="88.5" cy="51" r="1.5" fill="#38bdf8" filter="url(#aiCyanGlow)"/>

                    <!-- Helmet Shell -->
                    <rect x="33" y="24" width="54" height="50" rx="25" fill="url(#aiGradHelmet)" stroke="#38bdf8" stroke-width="1.5"/>
                    <path d="M37 32 C46 27, 74 27, 83 32" stroke="rgba(255,255,255,0.3)" stroke-width="1.2" stroke-linecap="round"/>

                    <!-- Futuristic Glass Visor -->
                    <rect class="ai-visor-screen" x="38" y="34" width="44" height="32" rx="14" fill="url(#aiGradVisor)" stroke="#1e3a5f" stroke-width="1.4"/>
                    <!-- Visor Sheen Curved Reflection -->
                    <path d="M42 38 C52 35, 68 35, 78 38" stroke="rgba(255,255,255,0.35)" stroke-width="1.5" stroke-linecap="round"/>

                    <!-- Expressive Cyber Glowing Eyes -->
                    <g class="ai-eyes-group" id="aiEyesGroup">
                        <!-- Standard Pill Eyes -->
                        <rect class="ai-eye ai-eye-left" id="aiEyeLeft" x="46" y="44" width="8.5" height="10" rx="4.25" fill="#38bdf8" filter="url(#aiCyanGlow)"/>
                        <rect class="ai-eye ai-eye-right" id="aiEyeRight" x="65.5" y="44" width="8.5" height="10" rx="4.25" fill="#38bdf8" filter="url(#aiCyanGlow)"/>

                        <!-- Pupillary Glint Dots -->
                        <circle class="ai-pupil-glint glint-left" cx="48.5" cy="46.5" r="1.5" fill="#ffffff"/>
                        <circle class="ai-pupil-glint glint-right" cx="68" cy="46.5" r="1.5" fill="#ffffff"/>

                        <!-- Happy Eyes (Arcs shown during greeting / speaking) -->
                        <path class="ai-eye-happy happy-left" d="M46 51 C48 45, 53 45, 55 51" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round" fill="none" opacity="0" filter="url(#aiCyanGlow)"/>
                        <path class="ai-eye-happy happy-right" d="M65.5 51 C67.5 45, 72.5 45, 74.5 51" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round" fill="none" opacity="0" filter="url(#aiCyanGlow)"/>
                    </g>

                    <!-- Neon Speech Visualizer Equalizer (Animates when Speaking) -->
                    <g class="ai-mouth-equalizer" id="aiMouthEqualizer">
                        <rect class="eq-bar bar-1" x="51" y="58" width="2" height="3" rx="1" fill="#38bdf8"/>
                        <rect class="eq-bar bar-2" x="55" y="57" width="2" height="5" rx="1" fill="#38bdf8"/>
                        <rect class="eq-bar bar-3" x="59" y="56" width="2" height="7" rx="1" fill="#38bdf8"/>
                        <rect class="eq-bar bar-4" x="63" y="57" width="2" height="5" rx="1" fill="#38bdf8"/>
                        <rect class="eq-bar bar-5" x="67" y="58" width="2" height="3" rx="1" fill="#38bdf8"/>
                    </g>
                </g>
            </svg>

            <!-- Holographic Badge Indicator -->
            <span class="ai-badge-indicator">
                <span class="indicator-dot"></span>
                <span>AI</span>
            </span>
        </button>

        <!-- Floating Tooltip on Hover / Focus -->
        <span class="ai-character-tooltip">Nova • Studio AI Assistant</span>
    </div>

    <!-- Mobile Backdrop Blur Overlay -->
    <div class="ai-chat-backdrop" id="aiChatBackdrop" onclick="window.closeNovaAiAssistant()"></div>

    <!-- Expanded Interactive AI Assistant Chat Drawer / Modal -->
    <div class="ai-chat-drawer" id="aiChatDrawer" role="dialog" aria-modal="true" aria-label="Nova AI Assistant Interface">
        <!-- Drawer Header -->
        <div class="ai-drawer-header">
            <div class="ai-drawer-title-wrap">
                <div class="ai-drawer-avatar-preview">
                    <span class="drawer-status-dot"></span>
                    <svg viewBox="0 0 120 130" width="30" height="32" fill="none">
                        <use href="#aiFloatingBody"/>
                    </svg>
                </div>
                <div class="ai-drawer-titles">
                    <h3 class="ai-assistant-name">Nova <span class="ai-pill-role">AI ASSISTANT</span></h3>
                    <p class="ai-assistant-status">Perumalil Creative Intelligence • Online</p>
                </div>
            </div>

            <div class="ai-drawer-controls">
                <button type="button" class="ai-control-btn ai-clear-chat-btn" id="aiClearChatBtn" title="Clear Chat History" aria-label="Clear Chat History" onclick="window.clearNovaChat()">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
                <button type="button" class="ai-control-btn ai-voice-toggle-btn" id="aiVoiceToggleBtn" title="Toggle Voice Speech" aria-label="Toggle Voice Speech">
                    <svg class="speaker-svg" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <path class="speaker-wave-full" d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                </button>
                <button type="button" class="ai-drawer-close-btn" id="aiDrawerCloseBtn" aria-label="Close Assistant">&times;</button>
            </div>
        </div>

        <!-- Chat Notification Toast -->
        <div class="ai-chat-toast" id="aiChatToast">✓ Chat cleared</div>

        <!-- Chat Conversation Stream -->
        <div class="ai-chat-body" id="aiChatBody">
            <!-- Initial Welcome Card from Nova -->
            <div class="ai-message ai-message-bot intro-bot-msg">
                <div class="ai-msg-avatar">
                    <span class="avatar-icon">✦</span>
                </div>
                <div class="ai-msg-bubble">
                    <p class="ai-msg-p">
                        <strong>Hi, I'm Nova!</strong> 👋 How can I help you today?
                    </p>
                </div>
            </div>
        </div>

        <!-- Floating Scroll to Bottom / Latest Messages Button -->
        <button type="button" class="ai-chat-scroll-btn" id="aiChatScrollBtn" title="Scroll to bottom" aria-label="Scroll to bottom" onclick="window.scrollNovaChatToBottom()">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
            <span>Scroll to bottom</span>
        </button>

        <!-- Interactive Chat Input Form -->
        <form class="ai-chat-input-bar" id="aiChatForm" onsubmit="window.handleNovaSubmit(event)">
            <input type="text" class="ai-chat-input" id="aiChatInput" placeholder="Ask Nova anything about Perumalil Creative..." autocomplete="off" required>
            <button type="submit" class="ai-chat-send-btn" id="aiChatSendBtn" aria-label="Send Message" title="Send">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </form>
    </div>

</div>
