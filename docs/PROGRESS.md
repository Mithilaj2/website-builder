# Development Progress

| Phase | Status | Notes |
|---|---|---|
| 0 Architecture | ✅ Draft complete — awaiting approval | See `docs/ARCHITECTURE.md` |
| 1 Core platform | ⏳ Not started | |
| 2 Template engine | ⏳ Not started | |
| 3 Website generator | ⏳ Not started | |
| 4 Generated site CMS | ⏳ Not started | |
| 5 Payment integration | ⏳ Not started | No gateway is marked complete until a sandbox payment is verified on the server |
| 6 Export & deployment | ⏳ Not started | |
| 7 Template library | ⏳ Not started | Order: Business, Education, Hospital, E-commerce, then the rest |

## Log

### 2026-11-06 — Phase 0
- Requirements reviewed; 30 open questions answered by the Channel Softech team.
- Database decision: one builder database with a table prefix per site; a separate DB per site only on the client host (created by the installer).
- Architecture document written.
- Repository initialised with docs and logo.
