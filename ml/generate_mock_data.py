import pandas as pd
import random

data = []
# We will test with just IT and CS for now to prove the concept works
degrees = ['BSIT', 'BSCS']

print("Generating 200 mock alumni records...")

for i in range(200):
    degree = random.choice(degrees)
    
    # Base academic and profile stats (scaled 75-100 to match your PHP logic)
    row = {
        "Degree": degree,
        "Age": random.randint(21, 25),
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
        # IT specific professions and skills
        row["Profession"] = random.choice(["Web Developer", "Network Administrator", "Database Analyst", "IT Support Specialist"])
        row["Database Management Skills"] = random.randint(80, 100)
        row["Java Programming Skills"] = random.randint(80, 100)
        row["Networking Skills"] = random.randint(80, 100)
        row["Python Programming Skills"] = random.randint(80, 100)
        row["System Design Skills"] = random.randint(80, 100)
        row["Web Development Skills"] = random.randint(80, 100)
        row["Cybersecurity Skills"] = random.randint(80, 100)
    else: 
        # CS specific professions and skills
        row["Profession"] = random.choice(["Software Engineer", "AI Researcher", "Data Scientist", "Systems Analyst"])
        row["Cloud Computing Skills"] = random.randint(80, 100)
        row["Data Structures & Algorithms"] = random.randint(80, 100)
        row["Machine Learning Skills"] = random.randint(80, 100)
        row["Programming Logic Skills"] = random.randint(80, 100)
        row["Software Engineering Skills"] = random.randint(80, 100)
        row["Artificial Intelligence Skills"] = random.randint(80, 100)
        
    data.append(row)

# Create DataFrame and fill missing skills across programs with 0
df = pd.DataFrame(data)
df.fillna(0, inplace=True)

# Save to the CSV file that your training script will look for
df.to_csv("alumni_data.csv", index=False)
print("Success! 'alumni_data.csv' has been created.")