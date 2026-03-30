# EndexAgents

EndexAgents is a production-inspired AI lead orchestration system presented as a public portfolio showcase.

## What It Is

A staged, queue-driven platform that models how lead discovery, enrichment, scoring, message generation, compliance checks, and human review can be orchestrated end to end.

## Business Problem It Solves

Commercial teams often struggle with fragmented lead qualification and inconsistent follow-up.

EndexAgents addresses this by:
- structuring lead processing into explicit stages,
- generating stage artifacts for auditability,
- centralizing review decisions before outreach,
- reducing operational ambiguity in sales workflows.

## Architecture Overview

The repository preserves a real-system architecture:
- `app/Agents`: stage-specific processing units.
- `app/Services`: supporting technical services.
- `app/Jobs`: queue-based execution flow.
- `app/Models`: domain entities and persistent state.
- `routes`: operational endpoints and review actions.
- `resources/js/pages/endex`: operator-facing workflow pages.

See:
- `ARCHITECTURE.md`
- `SYSTEM_FLOW.md`

## Agent Orchestration Overview

The pipeline runs through these stages:
1. `Argos` (discovery)
2. `Hefesto` (audit)
3. `Tique` (enrichment)
4. `Minos` (scoring)
5. `Temis` (offer classification)
6. `Hermes` (contact extraction)
7. `Caliope` (message generation)
8. `Nestor` (proposal)
9. `Hestia` (compliance)
10. `Mnemosine` (memory/review handoff)

## Stack

- Laravel (PHP)
- Inertia.js + React (TypeScript)
- Queue/job pipeline architecture
- Eloquent domain models

## Confidentiality and Omitted Components

This is not a production deployable repository.

The following were intentionally sanitized:
- proprietary prompts and prompt composition,
- internal scoring and prioritization heuristics,
- private integration/provider execution logic,
- live scraping and discovery internals,
- sensitive operational/deployment artifacts.

Placeholder comments such as:
- `Implementation removed for confidentiality`

indicate where private logic originally existed.

## Public Showcase Intent

This repo is designed to communicate system design quality, architectural thinking, and workflow orchestration patterns without exposing confidential IP or production credentials.
