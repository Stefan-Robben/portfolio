#!/usr/bin/env python3
"""Grade eval answers against assertions using explicit patterns.

Each assertion is checked with any_of / none_of / all_of regex sets against the
answer text. Deterministic and re-runnable across iterations.

Usage: python3 grade.py <iteration-dir>
"""
import json
import re
import sys
from pathlib import Path

# (any_of, none_of, all_of) — all regexes are case-insensitive.
CHECKS = {
    0: [
        ({r"figma.*(mcp|connector|plugin)|get_metadata"}, set(), set()),
        ({r"figma desktop|dev mode|preferences.*figma|figma.*preferences"}, set(), set()),
        ({r"atlassian|jira.*(auth|connect|oauth)|getaccessible"}, set(), set()),
        (set(), {r"which project|project (key|selection)|select.*project"}, set()),
    ],
    1: [
        ({r"active (file|document)|currently (open|active)|reads (the )?active"}, set(), set()),
        ({r"confirm|make sure|ensure|verify"}, set(), set()),
        (set(), {r"url.*(enough|sufficient|scopes?)|scoped? (to|by) (the )?url"}, set()),
    ],
    2: [
        ({r"desktop.*component.*section|three pages|only.*(desktop|components|sections)"}, set(), set()),
        ({r"skip|ignor|exclud|not (process|extract)"}, set(), set()),
        ({r"component / button"}, set(), set()),
        ({r"section / section-hero"}, set(), set()),
        ({r"template / 1\.0 / homepagina"}, set(), set()),
        ({r"template / 9\.1 / oplossingen"}, set(), set()),
    ],
    3: [
        (set(), {r"skip(ping)? (the )?approval|without (showing|the list)|no (need|list)"}, set()),
        ({r"approval|confirm|review (the )?list|show (you )?the list|present.*(issue|list)"}, set(), set()),
        (set(), {r"createjiraissue|create.*(issue|ticket).*(now|immediately|directly)"}, set()),
    ],
}


def check(text, spec):
    any_of, none_of, all_of = spec
    ev = []
    ok = True
    if any_of:
        hits = [p for p in any_of if re.search(p, text, re.I | re.M)]
        if hits:
            m = re.search(hits[0], text, re.I | re.M)
            ev.append(f"matched /{hits[0][:45]}/: \"{text[max(0, m.start()-40):m.end()+40].strip()[:110]}\"")
        else:
            ok = False
            ev.append(f"no match for any of: {sorted(p[:38] for p in any_of)}")
    for p in all_of or set():
        if re.search(p, text, re.I | re.M):
            ev.append(f"contains /{p[:38]}/")
        else:
            ok = False
            ev.append(f"MISSING required /{p[:38]}/")
    for p in none_of or set():
        m = re.search(p, text, re.I | re.M)
        if m:
            ok = False
            ev.append(f"forbidden /{p[:38]}/ present: \"{text[max(0, m.start()-30):m.end()+30].strip()[:100]}\"")
    return ok, " | ".join(ev) or "n/a"


def main():
    it = Path(sys.argv[1])
    for d in sorted(it.glob("eval-*")):
        meta = json.loads((d / "eval_metadata.json").read_text())
        eid = meta["eval_id"]
        for cfg in ("with_skill", "without_skill"):
            run = d / cfg
            ans = run / "outputs" / "answer.md"
            if not ans.is_file():
                continue
            text = ans.read_text()
            results = []
            for idx, a in enumerate(meta["assertions"]):
                ok, ev = check(text, CHECKS[eid][idx])
                results.append({"text": a, "passed": bool(ok), "evidence": ev})
            passed = sum(r["passed"] for r in results)
            (run / "grading.json").write_text(json.dumps({
                "eval_id": eid, "eval_name": meta["eval_name"], "configuration": cfg,
                "expectations": results,
                "passed_count": passed, "total_count": len(results),
                "pass_rate": round(passed / len(results), 3) if results else 0.0,
            }, indent=2) + "\n")
            print(f"{d.name:42} {cfg:15} {passed}/{len(results)}")


if __name__ == "__main__":
    main()
