#!/usr/bin/env python3
"""Grade webreact-theme-scaffold eval answers against assertions using explicit patterns.

Each assertion is checked with any_of / none_of regex sets against the answer text,
mirroring evals/webreact-cloudflare/grade.py: it walks eval-*/ dirs, grades
{with_skill,without_skill}/outputs/answer.md, and writes each run's grading.json.
Deterministic and re-runnable: the same answer always grades the same way.

Usage: python3 grade.py <iteration-dir>
"""
import json
import re
import sys
from pathlib import Path

# (any_of, none_of) — all regexes are case-insensitive.
CHECKS = {
    0: [
        ({r"figma (url|link|project|file)|share.{0,30}(url|link)|provide.{0,30}(url|link)"}, set()),
        ({r"no (output|css|variable|file).{0,40}(generat|creat|writ|produc)|ask.{0,40}(url|link) first|nothing.{0,20}(generat|writ)"}, set()),
        ({r"(not|without|before).{0,40}(call|invoking).{0,40}get_variable_defs|ask.{0,20}url.{0,20}first|url.{0,20}required"}, set()),
    ],
    1: [
        ({r"(no|without).{0,30}(figma url|url|link).{0,30}(needed|required|ask)|json.{0,20}(sufficient|enough|works)"}, set()),
        ({r"--color-blue:\s*#382279|--color-white:\s*#ffffff"}, set()),
        ({r"\.text-blue.{0,20}var\(--color-blue\)|\.bg-blue.{0,20}var\(--color-blue\)"}, set()),
        ({r"approval|confirm|list.{0,30}(file|output)|before (writing|generat)"}, set()),
        ({r"button\.blade\.php|badge\.blade\.php|heading\.blade\.php|text\.blade\.php"},
         {r"ui-kit\.blade\.php.{0,100}@props.{0,200}@props"}),
    ],
    2: [
        ({r"(list|planned|following).{0,40}(file|output)|will (generate|create|write).{0,40}file"}, set()),
        ({r"confirm|approval|proceed|approve|yes|go ahead"}, set()),
        ({r"(before|until).{0,30}(approv|confirm|proceed)|wait.{0,20}(your|user)"}, set()),
    ],
    3: [
        ({r"0\.875\s*rem|14\s*(px)?.{0,10}0\.875"}, set()),
        ({r"--h1.{0,20}var\(--font-size-5xl\)|h1.{0,20}5xl.{0,20}(largest|biggest|first)"}, set()),
        ({r"--h2.{0,20}var\(--font-size-2xl\)"}, set()),
        ({r"(--h[456]|h4|h5|h6).{0,40}var\(--font-size-sm\).{0,40}(reuse|only 4|fewer)|reuse.{0,40}sm.{0,40}(h[456]|heading)"}, set()),
        ({r"--body.{0,20}var\(--font-size-base\)|body.{0,20}1\s*rem.{0,20}base"}, set()),
        ({r"(--small|--xs).{0,20}var\(--font-size-sm\)|small.{0,20}xs.{0,20}sm"}, set()),
    ],
    4: [
        ({r"resources/views/components/button\.blade\.php"}, set()),
        ({r"resources/views/components/badge\.blade\.php"}, set()),
        ({r"resources/views/components/heading\.blade\.php"}, set()),
        ({r"resources/views/components/text\.blade\.php"}, set()),
        ({r"(each|every|separate|individual).{0,30}(file|component).{0,30}@props|one @props.{0,30}(per|each) file"},
         set()),
        (set(), {r"ui-kit\.blade\.php.{0,400}@props.{0,400}@props"}),
    ],
}


def check(text, patterns):
    any_of, none_of = patterns
    ok = True
    ev = []
    if any_of:
        hit = None
        for p in any_of:
            m = re.search(p, text, re.I | re.S)
            if m:
                hit = (p, m)
                break
        if hit:
            p, m = hit
            ev.append(f"matched /{p[:38]}/: \"{text[max(0, m.start() - 30):m.end() + 30].strip()[:100]}\"")
        else:
            ok = False
            ev.append("no required pattern matched")
    for p in none_of or set():
        m = re.search(p, text, re.I | re.S)
        if m:
            ok = False
            ev.append(f"forbidden /{p[:38]}/ present: \"{text[max(0, m.start() - 30):m.end() + 30].strip()[:100]}\"")
    return ok, " | ".join(ev) or "n/a"


def main():
    if len(sys.argv) != 2:
        print(__doc__)
        sys.exit(1)
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
