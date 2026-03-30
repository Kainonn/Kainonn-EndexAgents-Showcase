# EndexAgents Architecture (Public Showcase)

## Purpose

EndexAgents is structured as a staged, queue-driven pipeline for lead processing and human-reviewed outreach.

This public version keeps the architectural shape while removing proprietary implementation details.

## Core Architectural Layers

- `app/Agents`
  - Stage-specific decision units.
  - Each agent implements a shared contract and returns a normalized result object.
- `app/Services`
  - Technical support services for AI, discovery, and message resolution.
  - In showcase mode, sensitive service internals are replaced with placeholders.
- `app/Jobs`
  - Queue-based orchestration steps for moving a lead through the pipeline.
- `app/Models`
  - Persistent domain entities (`Lead`, `LeadScore`, `LeadMessage`, `LeadReview`, etc.).
- `routes`
  - Endpoints for campaign setup, lead processing views, and review actions.
- `resources/js/pages/endex`
  - Inertia/React pages for campaign operations and lead review workflows.

## Agent Contract Pattern

All pipeline agents follow a shared interface pattern:

- Input: contextual DTO (`AgentContext`) with lead, campaign, and prior stage artifacts.
- Output: normalized DTO (`AgentResult`) including:
  - `agent`
  - `status`
  - `stage`
  - `summary`
  - `evidence`
  - `confidence`
  - `recommendedAction`
  - `payload`

This contract makes orchestration and auditability consistent across heterogeneous stages.

## Orchestration Model

The orchestrator coordinates stage progression by status/stage transitions.

High-level characteristics:
- deterministic stage sequencing,
- queue-safe transitions,
- stage-level artifact persistence,
- explicit handoff to human review.

## Data and Audit Design

The system stores stage outputs as structured artifacts tied to each lead.

Design goals:
- traceability per stage,
- reproducible review context,
- clear separation of generated outputs vs. reviewer actions.

## Showcase Sanitization Notes

The following architectural elements are preserved while implementation is removed:

- class names,
- file layout,
- method signatures,
- DTO contracts,
- stage-to-stage flow contracts.

Sensitive internals (prompts, heuristics, scoring formulas, provider integrations) are replaced with comments:

- `Implementation removed for confidentiality`
