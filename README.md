# EndexAgents

EndexAgents is a multi-agent AI system designed to execute business workflows end-to-end, from lead discovery to outreach, with structured orchestration and human-in-the-loop control.

## Short executive summary

EndexAgents is a public showcase of a multi-agent AI system for business development automation. It demonstrates how lead operations can be executed as a staged, auditable workflow across discovery, analysis, scoring, outreach preparation, compliance review, and final human decision points.

This repository is intentionally sanitized for portfolio use: sensitive integrations and proprietary decision logic are removed, while the engineering architecture and orchestration model are preserved.

## What EndexAgents is

EndexAgents is a queue-driven orchestration platform built with Laravel and a React/Inertia frontend. Instead of a single assistant endpoint, it models work as coordinated agent stages with explicit handoffs, structured artifacts, and review-aware progression.

This system is not a chatbot or single-agent interface. It is a coordinated multi-agent execution system where each agent operates within a defined stage of a business workflow.

The system design reflects production-oriented concerns:

- stage-specific agent responsibilities
- deterministic workflow transitions
- domain models for traceability
- service and action layers for operational logic
- frontend workspace for operator visibility and review workflows

## Business problem it solves

Business development teams often run fragmented processes across lead research, qualification, messaging, and approval. That creates inconsistent outputs, weak auditability, and unclear ownership at each step.

EndexAgents addresses this by:

- breaking lead operations into explicit, role-based stages
- standardizing outputs generated at each stage
- routing work through queue-backed jobs and orchestrators
- enforcing review and compliance checkpoints before outreach

This is broader than chatbot behavior: the system coordinates multi-step workflow execution across backend agents, jobs, services, and human-in-the-loop review.

## Why this matters

Most AI implementations in business remain limited to isolated chat interfaces or disconnected automations.

EndexAgents demonstrates how AI can be embedded into operational workflows, enabling structured execution, traceability, and measurable outcomes across multiple stages of a business process.

## Core capabilities

- Multi-stage lead workflow orchestration from discovery to review handoff
- Role-separated agent execution with explicit stage outputs
- Queue-backed processing for staged workflow execution
- Structured domain persistence for lead state, activity, scoring, feedback, messaging, and review artifacts
- Operational actions and service abstractions for business logic and integrations
- Operator-facing frontend workspace for campaign and review operations

## Architecture overview

The architecture in this repository is organized around clear backend boundaries:

- `app/Agents`: domain-specific agents grouped by responsibility (compliance, contact, contracts, proposal, prospecting, scoring, memory)
- `app/Agents/Orchestrators`: orchestration components that coordinate stage progression and handoffs
- `app/Agents/DTOs`: structured data contracts passed between stages/components
- `app/Jobs`: queue-driven execution flow for lead pipeline work
- `app/Services`: reusable technical/business services (AI, commercial, leads)
- `app/Actions`: action classes for application use cases
- `app/Models`: persistent domain entities for campaigns, leads, scoring, reviews, messaging, and status history
- `database/migrations` and `database/factories`: schema evolution and realistic data modeling support
- `resources/js` and Inertia routes/pages: frontend operator workspace

Supporting documentation:

- `ARCHITECTURE.md`
- `SYSTEM_FLOW.md`

## Agent pipeline overview

The pipeline is organized into named stages:

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

## Technology stack

- Laravel (PHP, backend architecture and orchestration)
- Inertia.js + React (TypeScript, operator-facing UI)
- Queue/job-based execution model
- Eloquent ORM and relational data modeling

## Public showcase scope

This repository is a public portfolio representation, not a production deployment package.

Intentionally removed or replaced:

- proprietary prompts and prompt composition logic
- internal scoring and prioritization heuristics
- private provider/integration implementations
- sensitive operational and deployment artifacts
- confidential discovery/scraping internals

Placeholder notes such as `Implementation removed for confidentiality` indicate intentionally omitted private logic.

## Repository structure

High-level structure:

- `app/Agents`: agent units, orchestrators, DTOs, and memory/review flows
- `app/Jobs`: asynchronous workflow execution
- `app/Services`: shared service layer
- `app/Actions`: application-level actions
- `app/Models`: domain entities and workflow state
- `app/Http`: controllers, middleware, request validation
- `database`: migrations, factories, seed scaffolding
- `routes`: web, console, and settings routes
- `resources/js`: frontend workspace and operator-facing pages
- `config`: environment and subsystem configuration

## Notes

This project demonstrates:

- multi-agent orchestration
- AI-assisted business workflow automation
- role separation across agents
- integration-oriented design

The goal of this showcase is to present system design and implementation patterns for AI/automation engineering roles while preserving confidentiality constraints.
