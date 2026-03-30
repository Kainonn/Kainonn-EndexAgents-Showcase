<?php

namespace App\Agents\Contracts;

use App\Agents\DTOs\AgentContext;
use App\Agents\DTOs\AgentResult;

interface AgentInterface
{
    public function handle(AgentContext $context): AgentResult;
}
