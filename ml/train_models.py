import pandas as pd
import joblib
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import StandardScaler

# 1. Load the mock dataset
df = pd.read_csv('alumni_data.csv')

degrees = df['Degree'].unique()

print("Starting training process...\n")

for degree in degrees:
    print(f"--- Training model for {degree} ---")
    
    # 2. Isolate data for this specific degree
    program_data = df[df['Degree'] == degree].copy()
    
    # Drop the Degree column (we don't need it for the math)
    program_data = program_data.drop('Degree', axis=1)
    
    # 3. Separate the target (Profession) from the features
    X = program_data.drop('Profession', axis=1)
    y = program_data['Profession']
    
    # THE FIX: Convert text columns ('Male', 'Yes', 'No') into numbers (1s and 0s)
    X = pd.get_dummies(X, dtype=int)
    
    # Fill any blank data points with 0
    X = X.fillna(0)
    
    # 4. Scale the features
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    
    # 5. Train the Random Forest
    model = RandomForestClassifier(n_estimators=100, random_state=42)
    model.fit(X_scaled, y)
    
    # 6. Save the segregated brain files
    joblib.dump(model, f"{degree}_rf_model.joblib")
    joblib.dump(scaler, f"{degree}_scaler.joblib")
    joblib.dump(X.columns.tolist(), f"{degree}_model_columns.joblib")
    
    print(f"Success: Saved files for {degree}\n")

print("All models trained successfully! You are ready to test.")