# EndexAgents System Flow (Public Showcase)

## Pipeline Overview

This repository demonstrates a staged pipeline from lead intake to human-reviewed action.

## Stage Flow

1. Lead Discovery (`Argos`)
- Identifies candidate viability and initial signals.
- Showcase mode uses placeholder stage logic.

2. Web/Presence Audit (`Hefesto`)
- Evaluates digital presence readiness.
- Showcase mode does not perform live web audit.

3. Opportunity Enrichment (`Tique`)
- Produces structured opportunity hypothesis.
- Showcase mode emits placeholder enrichment output.

4. Scoring (`Minos`)
- Produces normalized scoring payload.
- Proprietary scoring internals are removed; contract is preserved.

5. Offer Classification (`Temis`)
- Selects offer framing and pricing range output shape.
- Showcase mode returns generic placeholder offer data.

6. Contact Extraction (`Hermes`)
- Determines best contact channel payload.
- Showcase mode avoids real extraction and returns placeholders.

7. Message Generation (`Caliope`)
- Generates outreach draft payload.
- Showcase mode returns safe generic message variants.

8. Proposal Generation (`Nestor`)
- Produces proposal summary payload.
- Showcase mode returns placeholder proposal structure.

9. Compliance Check (`Hestia`)
- Validates message/readiness flags before review.
- Showcase mode returns placeholder compliance result.

10. Memory Snapshot (`Mnemosine`)
- Stores final stage snapshot and marks lead ready for review.
- Showcase mode preserves status contract only.

## Human Review

After automated stages complete, the lead enters human review.

Reviewer capabilities (high-level):
- inspect artifacts,
- edit message state,
- update commercial status,
- approve/discard/reprocess actions.

## Outreach in Showcase Mode

Production delivery integrations are intentionally disabled.

Any send/contact actions in this version are demonstrational and should not be considered production-safe dispatch.

## What Was Intentionally Sanitized

- provider-specific execution logic,
- private prompts and prompt composition,
- scoring and decision heuristics,
- live scraping/discovery implementations,
- confidential operational details and deployment artifacts.
