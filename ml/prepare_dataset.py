"""
Prepare (re-structure) a raw alumni dataset into the exact feature schema used by this project.

Why this exists:
- The dataset you receive (e.g., from an instructor) often does NOT match the prediction form fields 1:1.
- Our web app prediction expects a stable feature set (same column names as training).
- This script auto-maps common column names, fills missing dimensions, and outputs a training-ready CSV.

Usage (PowerShell):
  python prepare_dataset.py --input path\\to\\sir_dataset.csv --output sir_structured.csv

Then train:
  python train_models.py --data sir_structured.csv
"""

from __future__ import annotations

import argparse
import re
from pathlib import Path
from typing import Dict, Iterable, List, Tuple

import pandas as pd


EXPECTED_COLUMNS: List[str] = [
    "Degree",
    "Profession",
    "Age",
    "Gender",
    "Leadership POS",
    "Act Member POS",
    "CGPA",
    "Average Prof Grade",
    "Average Elec Grade",
    "OJT Grade",
    "SS_1",
    "SS_2",
    "SS_3",
    "SS_4",
    "SS_5",
    "SS_6",
    "HS_1",
    "HS_2",
    "HS_3",
    "HS_4",
    "HS_5",
    "HS_6",
    "Soft Skills Ave",
    "Hard Skills Ave",
]


# Keep this aligned with includes/career_ml_config.php and alumni/prediction_form.php dynamic skill names
ALL_SKILL_COLUMNS: List[str] = [
    "Database Management Skills",
    "Java Programming Skills",
    "Networking Skills",
    "Python Programming Skills",
    "System Design Skills",
    "Web Development Skills",
    "Cybersecurity Skills",
    "Cloud Computing Skills",
    "Data Structures & Algorithms",
    "Machine Learning Skills",
    "Programming Logic Skills",
    "Software Engineering Skills",
    "Artificial Intelligence Skills",
    "Auditing Skills",
    "Budgeting & Analysis Skills",
    "Financial Accounting Skills",
    "Taxation Skills",
    "Risk Management Skills",
    "Financial Management Skills",
    "Leadership & Decision-Making Skills",
    "Marketing Skills",
    "Strategic Planning Skills",
    "Consumer Behavior Analysis",
    "Sales Management Skills",
    "Innovation & Business Planning Skills",
    "Food & Beverage Operations Skills",
    "Front Office & Reservations Skills",
    "Housekeeping Standards Skills",
    "Events & Banquet Coordination Skills",
    "Customer Experience & Guest Relations Skills",
    "Clinical & Patient Care Skills",
    "Pharmacology & Medication Skills",
    "Community Health & Education Skills",
    "Infection Control & Safety Skills",
    "Nursing Assessment & Documentation Skills",
    "Circuit Analysis & Electronics Skills",
    "Embedded Systems Skills",
    "Network & Telecom Skills",
    "RF & Wireless Basics Skills",
    "Technical Troubleshooting Skills",
    "Classroom Management Skills",
    "Curriculum Development Skills",
    "Educational Technology Skills",
    "Teaching Skills",
    "English Communication & Writing Skills",
    "Mathematics Instruction & Reasoning Skills",
    "Filipino Communication & Writing Skills",
    "Child Development & Learning Skills",
    "Foundational Literacy & Numeracy Skills",
    "Technical Knowledge in Degree",
]


def _norm(s: str) -> str:
    return re.sub(r"[^a-z0-9]+", "", str(s).strip().lower())


def _first_present(df: pd.DataFrame, candidates: Iterable[str]) -> str | None:
    norm_map = {_norm(c): c for c in df.columns}
    for cand in candidates:
        key = _norm(cand)
        if key in norm_map:
            return norm_map[key]
    return None


def _coerce_percent(series: pd.Series) -> pd.Series:
    s = pd.to_numeric(series, errors="coerce")
    # If the column looks like 0-1, upscale to 0-100
    if s.dropna().between(0, 1).mean() > 0.85:
        s = s * 100.0
    return s.clip(lower=0, upper=100)


def _coerce_gpa(series: pd.Series) -> pd.Series:
    s = pd.to_numeric(series, errors="coerce")
    # support either 1-5 or 0-4 (convert 0-4 into 1-5-ish)
    if s.dropna().between(0, 4).mean() > 0.85 and s.dropna().between(1, 5).mean() < 0.6:
        s = 1.0 + (s / 4.0) * 4.0
    return s.clip(lower=1.0, upper=5.0)


def _default_categoricals(n: int) -> Dict[str, List]:
    return {
        "Gender": ["Female"] * n,
        "Leadership POS": ["No"] * n,
        "Act Member POS": ["No"] * n,
    }


def prepare(df_raw: pd.DataFrame) -> Tuple[pd.DataFrame, List[str]]:
    notes: List[str] = []
    df = df_raw.copy()
    n = len(df)

    # Mandatory label fields
    degree_col = _first_present(df, ["Degree", "Program", "Program Code", "Program/Degree", "Course"])
    prof_col = _first_present(df, ["Profession", "Job Title", "Current Position", "Position", "Work Title"])
    if degree_col is None:
        notes.append("Missing Degree column; created 'Degree' as GENERIC.")
        df["Degree"] = "GENERIC"
    else:
        df["Degree"] = df[degree_col].astype(str)
    if prof_col is None:
        raise ValueError("Missing Profession/Job Title column. Provide a job label column to train a classifier.")
    df["Profession"] = df[prof_col].astype(str)

    # Age
    age_col = _first_present(df, ["Age"])
    if age_col is None:
        notes.append("Missing Age; filled with 22.")
        df["Age"] = 22
    else:
        df["Age"] = pd.to_numeric(df[age_col], errors="coerce").fillna(22).clip(18, 60).astype(int)

    # Gender + org indicators
    for k, default in _default_categoricals(n).items():
        col = _first_present(df, [k])
        if col is None:
            notes.append(f"Missing {k}; filled with defaults.")
            df[k] = default
        else:
            df[k] = df[col].astype(str).replace({"M": "Male", "F": "Female"}).fillna(default[0])

    # GPA / CGPA
    gpa_col = _first_present(df, ["CGPA", "GPA", "Final GPA", "General Weighted Average", "GWA"])
    if gpa_col is None:
        notes.append("Missing GPA/CGPA; filled with 2.50.")
        df["CGPA"] = 2.50
    else:
        df["CGPA"] = _coerce_gpa(df[gpa_col]).fillna(2.50)

    # OJT
    ojt_col = _first_present(df, ["OJT Grade", "OJT", "OJT Percentage", "Practicum Grade", "Internship Grade"])
    if ojt_col is None:
        notes.append("Missing OJT Grade; filled with 85.")
        df["OJT Grade"] = 85.0
    else:
        df["OJT Grade"] = _coerce_percent(df[ojt_col]).fillna(85.0)

    # Optional course grades
    prof_grade_col = _first_present(df, ["Average Prof Grade", "Professional Grade", "Major Average", "Major Grade"])
    elec_grade_col = _first_present(df, ["Average Elec Grade", "Elective Average", "Elective Grade"])
    if prof_grade_col is None:
        df["Average Prof Grade"] = (80 + (5.0 - df["CGPA"]) * 4.5).round(1)
        notes.append("Missing Average Prof Grade; approximated from CGPA.")
    else:
        df["Average Prof Grade"] = _coerce_percent(df[prof_grade_col]).fillna(88.0)
    if elec_grade_col is None:
        df["Average Elec Grade"] = (80 + (5.0 - df["CGPA"]) * 4.5).round(1)
        notes.append("Missing Average Elec Grade; approximated from CGPA.")
    else:
        df["Average Elec Grade"] = _coerce_percent(df[elec_grade_col]).fillna(88.0)

    # Soft/hard averages
    ss_avg_col = _first_present(df, ["Soft Skills Ave", "Soft Skills Average", "soft_skills_avg", "SoftSkillsAvg"])
    hs_avg_col = _first_present(df, ["Hard Skills Ave", "Hard Skills Average", "hard_skills_avg", "HardSkillsAvg"])
    if ss_avg_col is None:
        df["Soft Skills Ave"] = (df["OJT Grade"] - 3).clip(40, 98)
        notes.append("Missing Soft Skills Ave; approximated from OJT Grade.")
    else:
        df["Soft Skills Ave"] = _coerce_percent(df[ss_avg_col]).fillna(70.0)
    if hs_avg_col is None:
        df["Hard Skills Ave"] = (df["OJT Grade"] - 5).clip(40, 98)
        notes.append("Missing Hard Skills Ave; approximated from OJT Grade.")
    else:
        df["Hard Skills Ave"] = _coerce_percent(df[hs_avg_col]).fillna(70.0)

    # Dimensions: if missing, duplicate the average across 6 dims (keeps schema stable)
    for i in range(1, 7):
        ss_col = _first_present(df, [f"SS_{i}", f"ss{i}"])
        hs_col = _first_present(df, [f"HS_{i}", f"hs{i}"])
        if ss_col is None:
            df[f"SS_{i}"] = df["Soft Skills Ave"]
        else:
            df[f"SS_{i}"] = _coerce_percent(df[ss_col]).fillna(df["Soft Skills Ave"])
        if hs_col is None:
            df[f"HS_{i}"] = df["Hard Skills Ave"]
        else:
            df[f"HS_{i}"] = _coerce_percent(df[hs_col]).fillna(df["Hard Skills Ave"])

    # Program-specific/industry skills: keep all columns; fill missing with Hard Skills Ave (or 0)
    for col in ALL_SKILL_COLUMNS:
        src = _first_present(df, [col])
        if src is None:
            df[col] = df["Hard Skills Ave"]
        else:
            df[col] = _coerce_percent(df[src]).fillna(df["Hard Skills Ave"])

    # Final column order: expected + skills
    ordered = EXPECTED_COLUMNS + ALL_SKILL_COLUMNS
    out = df[ordered].copy()

    # Clean strings
    out["Degree"] = out["Degree"].astype(str).str.strip()
    out["Profession"] = out["Profession"].astype(str).str.strip()

    return out, notes


def main() -> None:
    p = argparse.ArgumentParser()
    p.add_argument("--input", required=True, help="Path to raw dataset (CSV).")
    p.add_argument("--output", default="sir_structured.csv", help="Output CSV path.")
    args = p.parse_args()

    inp = Path(args.input)
    if not inp.exists():
        raise SystemExit(f"Input not found: {inp}")

    df_raw = pd.read_csv(inp)
    out, notes = prepare(df_raw)
    out.to_csv(args.output, index=False)
    print(f"Wrote {args.output} with {len(out)} rows and {len(out.columns)} columns.")
    if notes:
        print("Notes:")
        for n in notes:
            print(f"- {n}")


if __name__ == "__main__":
    main()

