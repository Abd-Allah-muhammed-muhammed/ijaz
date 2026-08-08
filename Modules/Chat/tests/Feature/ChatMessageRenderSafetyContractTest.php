<?php

/**
 * Frontend render-safety contracts (static source assertions).
 *
 * Intent of the (missing) JS unit/browser suite this freezes:
 * - Message bubbles always escape user content before dangerouslySetInnerHTML,
 *   both when search highlighting is active and when it is not.
 * - highlightSearchTerm escapes HTML entities first, then optionally wraps matches
 *   in <mark> — never injects raw user HTML.
 * - unreadMessageIndex must be instance-scoped React state/ref, never a module-level
 *   mutable array shared across ConversationContent mounts.
 * - Admin Tickets/Show must use shared ConversationContent (no forked Echo/unread logic).
 */
test('message content with HTML characters renders escaped, never as raw HTML, in both search and non-search display paths', function () {
    $highlight = file_get_contents(resource_path('js/shared/components/chat/components/chat-search-highlight.ts'));
    $messageIn = file_get_contents(resource_path('js/shared/components/chat/components/message-in.tsx'));
    $messageOut = file_get_contents(resource_path('js/shared/components/chat/components/message-out.tsx'));

    expect($highlight)->toContain("replace(/&/g, '&amp;')")
        ->and($highlight)->toContain("replace(/</g, '&lt;')")
        ->and($highlight)->toContain("replace(/>/g, '&gt;')")
        ->and($highlight)->toContain('if (!needle)')
        ->and($highlight)->toContain('return escaped');

    // Both bubbles must route ALL content through highlightSearchTerm — never a
    // raw String(content) branch that skips escaping when search is inactive.
    foreach (['message-in' => $messageIn, 'message-out' => $messageOut] as $label => $source) {
        expect($source)->toContain('highlightSearchTerm(')
            ->and($source)->toContain('dangerouslySetInnerHTML')
            ->and($source)->not->toMatch('/__html:\s*highlightTerm\s*\?/')
            ->and($source)->not->toContain(': String(conversationMessage.content)')
            ->and($source)->toContain('highlightSearchTerm(String(conversationMessage.content), highlightTerm)');
    }
});

test('unread message indexes for presence joining are instance-scoped refs, not module-level mutable state', function () {
    $conversationContent = file_get_contents(
        resource_path('js/shared/components/chat/components/conversation-content.tsx')
    );

    expect($conversationContent)->not->toMatch('/^let unreadMessageIndex\b/m')
        ->and($conversationContent)->not->toContain('let unreadMessageIndex: number[] = []')
        ->and($conversationContent)->toContain('unreadMessageIdsRef')
        ->and($conversationContent)->toContain('useRef')
        ->and($conversationContent)->toContain('.joining(');
});

test('admin Tickets Show uses shared ConversationContent instead of a forked Echo chat implementation', function () {
    $ticketsShow = file_get_contents(resource_path('js/apps/admin/pages/Tickets/Show.tsx'));

    expect($ticketsShow)->toContain('ConversationContent')
        ->and($ticketsShow)->toContain('endpoints={endpoints}')
        ->and($ticketsShow)->toContain('showHeader={false}')
        ->and($ticketsShow)->toContain("t('support_ticket')")
        ->and($ticketsShow)->not->toContain('headerTitle')
        ->and($ticketsShow)->not->toContain('headerSubtitle')
        ->and($ticketsShow)->not->toContain('showAvatar')
        ->and($ticketsShow)->not->toContain('unreadMessageIdsRef')
        ->and($ticketsShow)->not->toMatch('/^let unreadMessageIndex\b/m')
        ->and($ticketsShow)->not->toContain('window.Echo.join')
        ->and($ticketsShow)->not->toContain('ChatEventEnum')
        ->and($ticketsShow)->not->toContain('MessageIn')
        ->and($ticketsShow)->not->toContain('MessageOut')
        ->and($ticketsShow)->not->toContain('ChatComposer')
        ->and($ticketsShow)->not->toContain('useForm')
        ->and($ticketsShow)->not->toContain('messageForm.submit');
});

test('ConversationContent only exposes showHeader boolean — no per-context title/avatar props', function () {
    $conversationContent = file_get_contents(
        resource_path('js/shared/components/chat/components/conversation-content.tsx')
    );

    expect($conversationContent)->toContain('showHeader?: boolean')
        ->and($conversationContent)->toContain('showHeader = true')
        ->and($conversationContent)->not->toContain('headerTitle')
        ->and($conversationContent)->not->toContain('headerSubtitle')
        ->and($conversationContent)->not->toContain('showAvatar');
});

test('SupportChatController send always returns JsonResponse with no Inertia redirect branch', function () {
    $controller = file_get_contents(
        base_path('Modules/Support/Http/Controllers/Dashboard/SupportChatController.php')
    );

    expect($controller)->toContain('function send(')
        ->and($controller)->toContain(': JsonResponse')
        ->and($controller)->not->toContain('RedirectResponse')
        ->and($controller)->not->toContain("redirect()->route('dashboard.support.tickets.show'")
        ->and($controller)->not->toContain('expectsJson()');
});
