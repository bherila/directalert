<?php

return [
    // Claude Code reads AGENTS.md directly (there is no CLAUDE.md), so keep
    // Boost writing its guidelines into AGENTS.md rather than a new CLAUDE.md.
    'agents' => [
        'claude_code' => [
            'guidelines_path' => 'AGENTS.md',
        ],
    ],
];
