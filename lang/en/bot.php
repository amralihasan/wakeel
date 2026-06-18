<?php

return [
    'title' => 'Bot & AI Settings',
    'bot_name' => 'Bot Name',
    'dialect' => 'Preferred Dialect',
    'working_hours' => 'Working Hours',
    'bot_active' => 'Bot is active and replying automatically',
    'bot_inactive' => 'Bot is currently paused',
    'thresholds' => 'Escalation & Score Thresholds',
    'lead_score_threshold' => 'Hot Lead Score Threshold (out of 100)',
    'unproductive_messages_threshold' => 'Max Unproductive Messages before human handoff',
    'sandbox' => 'Bot Testing Sandbox',
    'test_placeholder' => 'Type a message to test the bot...',
    'send' => 'Send',
    'save' => 'Save Settings',

    // Additional keys for settings UI
    'assistant_settings_title' => 'Smart Assistant Settings',
    'assistant_name_label' => 'Assistant Name (Bot)',
    'preferred_dialect_label' => 'Preferred dialect for conversation',
    'working_hours_label' => 'Company working hours',
    'bot_active_checkbox' => 'Activate assistant (Bot is active and responds to customers)',
    'escalation_rules_heading' => 'Human Representative Handoff Rules',
    'lead_score_threshold_label' => 'Required interest score for handoff (Lead Score)',
    'unproductive_messages_threshold_label' => 'Max unproductive messages before handoff',
    'followup_rules_heading' => 'Automatic Follow-Up Settings',
    'followups_enabled_checkbox' => 'Enable automatic follow-ups (re-engage inactive leads)',
    'max_followups_label' => 'Max follow-up messages per lead',
    'test_assistant_heading' => 'Test the Assistant',
    'test_assistant_desc' => 'Chat with the bot to test its tone and responses (no actual WhatsApp messages will be sent).',
    'test_assistant_placeholder' => 'Type a message to start testing the assistant.',
    'test_message_input_placeholder' => 'Type a message...',
    'always_active' => 'All day 24/7',
    'specific_hours' => 'Specific working hours (9 AM - 9 PM)',
    'success_saved' => 'Bot settings saved successfully',

    // Fallbacks & Error greetings
    'fallback_error' => 'Sorry, a minor error occurred. Could you repeat your message?',
    'default_followup_message' => 'Hello, just wanted to check if you are still interested in our real estate offers? If you have any questions, I am here to help.',
    'lead_hot_notification' => 'Alert: The lead has become hot and is ready for human handoff.',
    'followup_instruction' => 'The lead has not replied for 23 hours since our last message. Write a short, friendly, and personalized follow-up message based on their interests and the conversation context to re-engage them. Do not use any tools, create links, or guess non-existent details.',

    // Dialects
    'friendly_egyptian' => 'Egyptian Arabic (Colloquial)',
    'formal_arabic' => 'Modern Standard Arabic (Formal)',
    'gulf_arabic' => 'Gulf Arabic',
];
