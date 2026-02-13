<?php

namespace Oro\UpgradeToolkit\Rector\Replacement\ValueObject\Contract;

/**
 * Describes a single argument replacement for a call-like node (method/static call/attribute)
 *
 * Implementations provide the target class+method+argument name
 * and the old/new values used to decide and perform the rewrite
 */
interface ArgumentReplacementInterface
{
    public function getClass(): string;

    public function getMethod(): string;

    public function getArgName(): string;

    public function getOldValue(): mixed;

    public function getNewValue(): mixed;
}
