# Edumia physical-device acceptance

**Current result: NOT TESTED ON PHYSICAL DEVICES.** Headless browser emulation is not a substitute. Perform the checklist on a dedicated staging installation with disposable users, no customer data and payments disabled.

Record for **each** device: hardware model, operating system, browser/version, viewport orientation, staging URL, tested Git SHA, reviewer/date, actual result, and redacted screenshot or video. Every row remains NOT TESTED until evidence is reviewed.

| ID | Physical device | Action | Evidence | State |
| --- | --- | --- | --- | --- |
| D01 | iPhone Safari | Home over Wi-Fi and cellular; no horizontal overflow, missing media or fonts | Portrait + landscape screenshots | NOT TESTED |
| D02 | iPhone Safari | Mobile navigation open, close, outside tap, link navigation, language switch | Screen recording | NOT TESTED |
| D03 | iPhone Safari | Search keyboard, form validation, theme toggle and reload persistence | Recording | NOT TESTED |
| D04 | Android Chrome | Portrait/landscape layout and responsive menus | Screenshots + recording | NOT TESTED |
| D05 | Android Chrome | Search query retained across language switching; dark theme; browser back | Recording and URLs | NOT TESTED |
| D06 | Physical tablet Safari/Chrome | Menus and layout at 768–1024px portrait/landscape; no horizontal overflow | Screenshots | NOT TESTED |
| D07 | Tablet | Sign in with disposable learner account; dashboard, preferences, course and orders | Redacted video | NOT TESTED |
| D08 | Physical device with keyboard | Tab, Shift+Tab, Enter, Space, Escape, focus and menu state | Video and notes | NOT TESTED |
| D09 | iOS VoiceOver / Android TalkBack | Menu announcements, field names, errors and heading order | Accessibility observations | NOT TESTED |
| D10 | Physical phone on weak cellular | Reload key pages; check stalled assets, network errors, and usability | Recording and notes | NOT TESTED |

**Testing protocol:** Use an actual physical device, a dedicated staging account, and a separately backed-up disposable MySQL database. Record observed PASS, FAIL or BLOCKED with evidence for each ID. A FAIL/BLOCKED item prevents approval. Do not process a real payment or expose credentials in screenshots.

**Sign-off:** Staging URL/SHA: NOT PROVIDED; iPhone: NOT TESTED; Android: NOT TESTED; Tablet: NOT TESTED; Reviewer: NOT ASSIGNED. Device acceptance gate: **OPEN**.

The GitHub workflow's Chromium, Firefox and WebKit screenshots certify only automated browser emulation, not these tests.
