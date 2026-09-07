# Briefs

These files are **specifications, not content**. Nothing loads them at runtime
and nothing seeds them.

`home-page-brief.json` describes what the home page must show and, for each
figure on the credibility bar, where the number has to come from — every item
carries a `note` saying "generated, not typed". It lived in
`modules/Site/Database/Seeds/data/` until it was moved here, which was the
wrong place twice over: nothing read it, and `spark check:placeholders` treated
its `{course_count}` markers as draft copy about to be shown to a visitor.

`Modules\Site\Controllers\Home::facts()` is the implementation. It counts
published courses, courses carrying a certification alignment, the seat cap the
booking system actually enforces, and the delivery modes on sale — and it omits
"learners trained" entirely, exactly as the brief asks, until there is a booking
record to count it from.
