<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Git;

final readonly class CommitInfo
{
    public string $subject;

    public function __construct(
        public string $hash,
        public string $shortHash,
        public string $author,
        public string $email,
        public string $date,
        public string $body,
    ) {
        $lines = explode("\n", trim($body));
        $this->subject = $lines[0];
    }
}
