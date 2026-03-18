import pandas as pd
import random

data = []
degrees = ['BSIT', 'BSCS']

print("Generating 500 highly intelligent mock alumni records...")

for _ in range(500):
    degree = random.choice(degrees)
    
    # Base academic stats (scaled 75-100)
    row = {
        "Degree": degree,
        "Age": random.randint(21, 26),
        "Gender": random.choice(["Male", "Female"]),
        "Leadership POS": random.choice(["Yes", "No"]),
        "Act Member POS": random.choice(["Yes", "No"]),
        "CGPA": round(random.uniform(1.25, 2.5), 2), 
        "Average Prof Grade": round(random.uniform(80, 95), 1),
        "Average Elec Grade": round(random.uniform(80, 95), 1),
        "OJT Grade": round(random.uniform(85, 99), 1),
        "Soft Skills Ave": round(random.uniform(80, 95), 1),
        "Hard Skills Ave": round(random.uniform(80, 95), 1)
    }
    
    if degree == 'BSIT':
        # Map specific professions to the specific skills from your PHP file
        it_mapping = {
            "Web Developer": "Web Development Skills",
            "Network Administrator": "Networking Skills",
            "Database Administrator": "Database Management Skills",
            "Software Engineer": "Java Programming Skills",
            "Cybersecurity Analyst": "Cybersecurity Skills",
            "Systems Analyst": "System Design Skills"
        }
        
        # 1. Pick a random profession for this mock student
        prof = random.choice(list(it_mapping.keys()))
        row["Profession"] = prof
        
        # 2. Set ALL their IT skills to an "Average" baseline (75 to 85)
        for skill in it_mapping.values():
            row[skill] = random.randint(75, 85)
        row["Python Programming Skills"] = random.randint(75, 85) # Extra skill
            
        # 3. Boost ONLY the skill that matches their profession to "Excellent" (90 to 100)
        target_skill = it_mapping[prof]
        row[target_skill] = random.randint(90, 100)

    else: 
        # BSCS Mapping
        cs_mapping = {
            "AI/ML Engineer": "Artificial Intelligence Skills",
            "Data Scientist": "Machine Learning Skills",
            "Cloud Architect": "Cloud Computing Skills",
            "Software Developer": "Data Structures & Algorithms"
        }
        
        # 1. Pick Profession
        prof = random.choice(list(cs_mapping.keys()))
        row["Profession"] = prof
        
        # 2. Set average baseline
        for skill in cs_mapping.values():
            row[skill] = random.randint(75, 85)
        row["Programming Logic Skills"] = random.randint(75, 85)
        row["Software Engineering Skills"] = random.randint(75, 85)
            
        # 3. Boost target skill
        target_skill = cs_mapping[prof]
        row[target_skill] = random.randint(90, 100)
        
    data.append(row)

# Create DataFrame and fill any blank spaces with 0 to prevent errors
df = pd.DataFrame(data)
df.fillna(0, inplace=True)

# Save to CSV
df.to_csv("alumni_data.csv", index=False)
print("Success! 'alumni_data.csv' has been created with perfect skill-to-profession correlations.")