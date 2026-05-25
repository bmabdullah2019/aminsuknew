<?php

namespace App\Listeners\Accounts;

use App\Events\Accounts\AccountHeadUpdated;
use App\Models\Accounts\AccountHead;

class RebuildAccountTree
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AccountHeadUpdated $event): void
    {
        AccountHead::rebuildTree();
    }
}
