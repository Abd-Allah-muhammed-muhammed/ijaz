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
 *   mutable array shared across ConversationContent / Tickets Show mounts.
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
    $ticketsShow = file_get_contents(resource_path('js/apps/admin/pages/Tickets/Show.tsx'));

    foreach (['conversation-content' => $conversationContent, 'Tickets/Show' => $ticketsShow] as $label => $source) {
        expect($source)->not->toMatch('/^let unreadMessageIndex\b/m')
            ->and($source)->not->toContain('let unreadMessageIndex: number[] = []')
            ->and($source)->toContain('unreadMessageIdsRef')
            ->and($source)->toContain('useRef')
            ->and($source)->toContain('.joining(');
    }
});
