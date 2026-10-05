<?php

return [
    // CLAUDE.md just imports AGENTS.md, so keep Boost from also writing its
    // guidelines into CLAUDE.md (it does when the file exists).
    'agents' => [
        'claude_code' => [
            'guidelines_path' => 'AGENTS.md',
        ],
    ],
];
