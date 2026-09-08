<?php

namespace App\Enums;

enum LetterResponseDocumentKind: string
{
    case TechnicalMaterial = 'TECHNICAL_MATERIAL';
    case AssistantProposal = 'ASSISTANT_PROPOSAL';
    case ExecutiveConsolidation = 'EXECUTIVE_CONSOLIDATION';
}
