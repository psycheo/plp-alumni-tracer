"""
Synthetic alumni rows for career prediction. Aligns with:
- alumni/process_prediction.php (Likert -> 0-100 scales)
- includes/career_ml_config.php (program-specific skill names)
- Trains one RandomForest per Degree code with full soft/hard + industry skill columns.
"""
import random
import pandas as pd

random.seed(42)

N_PER_DEGREE = 400

# Profession title must match database `professions.title` where possible for UI consistency.
# Each entry: (profession_title, skill_column_to_boost)
DEGREE_CONFIG = {
    "BSIT": [
        ("Software Engineer", "Java Programming Skills"),
        ("Network Administrator", "Networking Skills"),
        ("IT Support Specialist", "Networking Skills"),
        ("Web Developer", "Web Development Skills"),
        ("Cybersecurity Analyst", "Cybersecurity Skills"),
    ],
    "BSCS": [
        ("Data Analyst", "Machine Learning Skills"),
        ("Software Engineer", "Software Engineering Skills"),
        ("QA / Test Engineer", "Programming Logic Skills"),
        ("Machine Learning Engineer", "Machine Learning Skills"),
        ("Backend Developer", "Data Structures & Algorithms"),
    ],
    "BSA": [
        ("Junior Accountant", "Financial Accounting Skills"),
        ("Audit Associate", "Auditing Skills"),
        ("Tax Associate", "Taxation Skills"),
        ("Financial Analyst", "Budgeting & Analysis Skills"),
        ("Bookkeeper", "Financial Accounting Skills"),
    ],
    "BSBA-Marketing": [
        ("Marketing Associate", "Marketing Skills"),
        ("Digital Marketing Specialist", "Marketing Skills"),
        ("Sales Executive", "Sales Management Skills"),
        ("Brand Coordinator", "Strategic Planning Skills"),
        ("Market Research Analyst", "Consumer Behavior Analysis"),
    ],
    "BSBA-Entrepreneurship": [
        ("Business Development Associate", "Leadership & Decision-Making Skills"),
        ("Operations Coordinator", "Strategic Planning Skills"),
        ("Project Coordinator", "Leadership & Decision-Making Skills"),
        ("Product Associate", "Innovation & Business Planning Skills"),
        ("Small Business Owner", "Innovation & Business Planning Skills"),
    ],
    "BSHM": [
        ("Front Office Associate", "Front Office & Reservations Skills"),
        ("Hotel Operations Supervisor", "Housekeeping Standards Skills"),
        ("Events Coordinator", "Events & Banquet Coordination Skills"),
        ("Food & Beverage Supervisor", "Food & Beverage Operations Skills"),
        ("Guest Relations Officer", "Customer Experience & Guest Relations Skills"),
    ],
    "BSN": [
        ("Registered Nurse", "Clinical & Patient Care Skills"),
        ("Company Nurse", "Community Health & Education Skills"),
        ("Clinical Nurse (Ward)", "Clinical & Patient Care Skills"),
        ("Public Health Nurse", "Community Health & Education Skills"),
        ("Nursing Assistant", "Nursing Assessment & Documentation Skills"),
    ],
    "BSECE": [
        ("Junior Electronics Engineer", "Circuit Analysis & Electronics Skills"),
        ("Telecommunications Engineer", "Network & Telecom Skills"),
        ("Network Field Engineer", "Network & Telecom Skills"),
        ("RF Engineer (Entry Level)", "RF & Wireless Basics Skills"),
        ("Electronics Technician", "Technical Troubleshooting Skills"),
    ],
    "BSEd-English": [
        ("Secondary School Teacher (English)", "English Communication & Writing Skills"),
        ("ESL Teacher", "English Communication & Writing Skills"),
        ("Academic Tutor", "Teaching Skills"),
        ("Curriculum Assistant", "Curriculum Development Skills"),
        ("Content Writer (Education)", "English Communication & Writing Skills"),
    ],
    "BSEd-Math": [
        ("Secondary School Teacher (Mathematics)", "Mathematics Instruction & Reasoning Skills"),
        ("Math Tutor", "Mathematics Instruction & Reasoning Skills"),
        ("Academic Tutor", "Teaching Skills"),
        ("Curriculum Assistant", "Curriculum Development Skills"),
        ("Education Data Assistant", "Mathematics Instruction & Reasoning Skills"),
    ],
    "BSEd-Filipino": [
        ("Secondary School Teacher (Filipino)", "Filipino Communication & Writing Skills"),
        ("Academic Tutor", "Teaching Skills"),
        ("Curriculum Assistant", "Curriculum Development Skills"),
        ("Content Writer (Education)", "Filipino Communication & Writing Skills"),
        ("Training Facilitator", "Classroom Management Skills"),
    ],
    "BSEd-Elementary": [
        ("Elementary School Teacher", "Teaching Skills"),
        ("Teaching Assistant", "Classroom Management Skills"),
        ("Child Development Assistant", "Child Development & Learning Skills"),
        ("Curriculum Assistant", "Curriculum Development Skills"),
        ("Education Program Coordinator (Entry Level)", "Foundational Literacy & Numeracy Skills"),
    ],
    "GENERIC": [
        ("General Professional Track A", "Technical Knowledge in Degree"),
        ("General Professional Track B", "Technical Knowledge in Degree"),
        ("General Professional Track C", "Technical Knowledge in Degree"),
    ],
}


SPECIFIC_SKILLS_BY_DEGREE = {
    "BSIT": [
        "Database Management Skills",
        "Java Programming Skills",
        "Networking Skills",
        "Python Programming Skills",
        "System Design Skills",
        "Web Development Skills",
        "Cybersecurity Skills",
    ],
    "BSCS": [
        "Cloud Computing Skills",
        "Data Structures & Algorithms",
        "Machine Learning Skills",
        "Programming Logic Skills",
        "Software Engineering Skills",
        "Artificial Intelligence Skills",
    ],
    "BSA": [
        "Auditing Skills",
        "Budgeting & Analysis Skills",
        "Financial Accounting Skills",
        "Taxation Skills",
        "Risk Management Skills",
    ],
    "BSBA-Marketing": [
        "Financial Management Skills",
        "Leadership & Decision-Making Skills",
        "Marketing Skills",
        "Strategic Planning Skills",
        "Consumer Behavior Analysis",
        "Sales Management Skills",
    ],
    "BSBA-Entrepreneurship": [
        "Financial Management Skills",
        "Leadership & Decision-Making Skills",
        "Marketing Skills",
        "Strategic Planning Skills",
        "Innovation & Business Planning Skills",
    ],
    "BSHM": [
        "Food & Beverage Operations Skills",
        "Front Office & Reservations Skills",
        "Housekeeping Standards Skills",
        "Events & Banquet Coordination Skills",
        "Customer Experience & Guest Relations Skills",
    ],
    "BSN": [
        "Clinical & Patient Care Skills",
        "Pharmacology & Medication Skills",
        "Community Health & Education Skills",
        "Infection Control & Safety Skills",
        "Nursing Assessment & Documentation Skills",
    ],
    "BSECE": [
        "Circuit Analysis & Electronics Skills",
        "Embedded Systems Skills",
        "Network & Telecom Skills",
        "RF & Wireless Basics Skills",
        "Technical Troubleshooting Skills",
    ],
    "BSEd-English": [
        "Classroom Management Skills",
        "Curriculum Development Skills",
        "Educational Technology Skills",
        "Teaching Skills",
        "English Communication & Writing Skills",
    ],
    "BSEd-Math": [
        "Classroom Management Skills",
        "Curriculum Development Skills",
        "Educational Technology Skills",
        "Teaching Skills",
        "Mathematics Instruction & Reasoning Skills",
    ],
    "BSEd-Filipino": [
        "Classroom Management Skills",
        "Curriculum Development Skills",
        "Educational Technology Skills",
        "Teaching Skills",
        "Filipino Communication & Writing Skills",
    ],
    "BSEd-Elementary": [
        "Classroom Management Skills",
        "Child Development & Learning Skills",
        "Educational Technology Skills",
        "Teaching Skills",
        "Foundational Literacy & Numeracy Skills",
    ],
    "GENERIC": ["Technical Knowledge in Degree"],
}


def build_row(degree: str, profession: str, boost_skill: str) -> dict:
    row = {
        "Degree": degree,
        "Profession": profession,
        "Age": random.randint(21, 29),
        "Gender": random.choice(["Male", "Female"]),
        "Leadership POS": random.choice(["Yes", "No"]),
        "Act Member POS": random.choice(["Yes", "No"]),
        "CGPA": round(random.uniform(1.25, 3.2), 2),
        "Average Prof Grade": round(random.uniform(82, 96), 1),
        "Average Elec Grade": round(random.uniform(82, 96), 1),
        "OJT Grade": round(random.uniform(78, 99), 1),
    }

    # Six soft-skill dimensions (0-100), same semantics as prediction form Likert scaled
    ss_vals = [random.uniform(52, 88) for _ in range(6)]
    # Slight lift for communication-heavy professions
    if "Teacher" in profession or "Tutor" in profession or "Nurse" in profession:
        ss_vals[0] = random.uniform(75, 95)
        ss_vals[3] = random.uniform(75, 95)
    for i in range(6):
        row[f"SS_{i + 1}"] = round(ss_vals[i], 2)

    # Six universal hard-skill dimensions
    hs_vals = [random.uniform(55, 90) for _ in range(6)]
    for i in range(6):
        row[f"HS_{i + 1}"] = round(hs_vals[i], 2)

    row["Soft Skills Ave"] = round(sum(ss_vals) / 6, 2)
    row["Hard Skills Ave"] = round(sum(hs_vals) / 6, 2)

    skill_cols = SPECIFIC_SKILLS_BY_DEGREE[degree]
    for col in skill_cols:
        row[col] = round(random.uniform(58, 86), 2)

    # Strong signal: boosted skill aligns with profession
    if boost_skill in row:
        row[boost_skill] = round(random.uniform(90, 100), 2)

    # Secondary: correlate OJT and GPA slightly with success
    if row["CGPA"] < 2.0:
        row["OJT Grade"] = round(min(99, row["OJT Grade"] + random.uniform(0, 4)), 1)

    return row


def main():
    rows = []
    for degree, prof_list in DEGREE_CONFIG.items():
        for _ in range(N_PER_DEGREE):
            profession, boost = random.choice(prof_list)
            rows.append(build_row(degree, profession, boost))

    df = pd.DataFrame(rows)
    df.fillna(0, inplace=True)
    df.to_csv("alumni_data.csv", index=False)
    print(f"Wrote alumni_data.csv with {len(df)} rows and {len(df.columns)} columns.")


if __name__ == "__main__":
    main()
